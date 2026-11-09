<?php

namespace App\Core\Privacy;

use App\Core\Backup\DatabaseDumper;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Pravidlá jadra: staré logy a IP adresy vo všetkých tabuľkách (objednávky, hodnotenia, komentáre…).
 */
final class CorePolicies
{
    public static function register(DataRetention $retention): void
    {
        $t = fn (string $key) => fn () => __('admin.privacy.policies.'.$key);

        $retention->register(new RetentionPolicy('logs', $t('logs'), $t('logs_help'), 90, true, function (Carbon $before): int {
            $deleted = 0;

            foreach (glob(storage_path('logs/*.log')) ?: [] as $file) {
                if (filemtime($file) < $before->getTimestamp()) {
                    File::delete($file);
                    $deleted++;
                }
            }

            return $deleted;
        }, minDays: 7));

        $retention->register(new RetentionPolicy('ip_addresses', $t('ip_addresses'), $t('ip_addresses_help'), 180, true, function (Carbon $before): int {
            $count = 0;
            $prefix = DB::connection()->getTablePrefix();

            foreach (Schema::getTables(DatabaseDumper::schema(DB::connection())) as $table) {
                $name = substr($table['name'], strlen($prefix));

                if ($prefix !== '' && ! str_starts_with($table['name'], $prefix)) {
                    continue;
                }

                if (Schema::hasColumns($name, ['ip_address', 'created_at'])) {
                    $count += DB::table($name)->whereNotNull('ip_address')->where('created_at', '<', $before)->update(['ip_address' => null]);
                }
            }

            return $count;
        }, minDays: 30));
    }
}
