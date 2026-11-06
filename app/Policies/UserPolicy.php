<?php

namespace App\Policies;

use App\Models\User;

/**
 * Oprávnenia na správu používateľov administrácie (štandardná Laravel Policy).
 * Hlavný administrátor má prístup vždy (Gate::before v AppServiceProvider),
 * okrem pravidiel v before(), ktoré platia pre všetkých.
 */
class UserPolicy
{
    public function before(User $user, string $ability, mixed $model = null): ?bool
    {
        if ($ability === 'delete' && $model instanceof User) {
            return $this->canBeDeleted($user, $model) ? null : false;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('users.manage');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('users.manage');
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('users.manage');
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function canBeDeleted(User $user, User $model): bool
    {
        if ($user->is($model)) {
            return false;
        }

        if ($model->isSuperAdmin()) {
            return User::role(User::ROLE_SUPER_ADMIN)->count() > 1;
        }

        return true;
    }
}
