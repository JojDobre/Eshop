<?php

namespace App\Core\Privacy;

use App\Settings\PrivacySettings;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Automatické mazanie a anonymizácia osobných údajov (GDPR – minimalizácia uchovávania).
 * Moduly registrujú pravidlá, obchodník v nastaveniach zapína pravidlá a mení lehoty.
 */
class DataRetention
{
    /** @var array<string, RetentionPolicy> */
    private array $policies = [];

    public function register(RetentionPolicy $policy): void
    {
        $this->policies[$policy->key] = $policy;
    }

    /**
     * @return array<string, RetentionPolicy>
     */
    public function all(): array
    {
        return $this->policies;
    }

    public function isEnabled(RetentionPolicy $policy): bool
    {
        return (bool) (app(PrivacySettings::class)->retention[$policy->key]['enabled'] ?? $policy->enabledByDefault);
    }

    public function days(RetentionPolicy $policy): int
    {
        return max($policy->minDays, (int) (app(PrivacySettings::class)->retention[$policy->key]['days'] ?? $policy->defaultDays));
    }

    /**
     * Spustí zapnuté pravidlá.
     *
     * @return array<string, int> počet spracovaných záznamov podľa pravidla
     */
    public function run(): array
    {
        $results = [];

        foreach ($this->policies as $key => $policy) {
            if (! $this->isEnabled($policy)) {
                continue;
            }

            try {
                $results[$key] = ($policy->apply)(now()->subDays($this->days($policy)));
            } catch (Throwable $e) {
                Log::error('Data retention policy failed.', ['policy' => $key, 'exception' => $e]);
            }
        }

        if (array_sum($results) > 0) {
            Log::info('Data retention finished.', $results);
        }

        return $results;
    }
}
