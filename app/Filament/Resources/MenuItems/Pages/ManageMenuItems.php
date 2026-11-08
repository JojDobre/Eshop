<?php

namespace App\Filament\Resources\MenuItems\Pages;

use App\Filament\Resources\MenuItems\MenuItemResource;
use App\Models\MenuItem;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageMenuItems extends ManageRecords
{
    protected static string $resource = MenuItemResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.menus.intro');
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->mutateDataUsing(fn (array $data) => [...$data, 'position' => (int) MenuItem::query()->where('location', $data['location'] ?? '')->max('position') + 1])];
    }
}
