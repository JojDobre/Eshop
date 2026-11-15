<?php

namespace App\Http\Controllers\Admin;

use App\Core\Backup\GoogleDrive;
use App\Filament\Pages\System\Backups;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Návrat z prihlásenia do Google: overí stav požiadavky a uloží obnovovací token pre zálohy na Google Disk.
 */
class GoogleDriveCallbackController
{
    public const STATE_KEY = 'gdrive_oauth_state';

    public function __invoke(Request $request, GoogleDrive $drive): RedirectResponse
    {
        abort_unless($request->user()?->can('system.manage'), 403);

        $state = (string) $request->session()->pull(self::STATE_KEY, '');

        if ($state === '' || ! hash_equals($state, (string) $request->query('state'))) {
            Notification::make()->danger()->title(__('admin.backups.gdrive_connect_failed', ['message' => __('admin.backups.gdrive_state_invalid')]))->persistent()->send();

            return redirect(Backups::getUrl());
        }

        if (filled($request->query('error')) || blank($request->query('code'))) {
            Notification::make()->danger()->title(__('admin.backups.gdrive_connect_failed', ['message' => (string) $request->query('error', '–')]))->persistent()->send();

            return redirect(Backups::getUrl());
        }

        try {
            $drive->connect((string) $request->query('code'));
        } catch (Throwable $e) {
            report($e);
            Notification::make()->danger()->title($e->getMessage())->persistent()->send();

            return redirect(Backups::getUrl());
        }

        Notification::make()->success()->title(__('admin.backups.gdrive_connected'))->send();

        return redirect(Backups::getUrl());
    }
}
