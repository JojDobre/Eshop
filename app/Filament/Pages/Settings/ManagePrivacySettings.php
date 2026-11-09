<?php

namespace App\Filament\Pages\Settings;

use App\Core\Privacy\DataRetention;
use App\Settings\PrivacySettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Ochrana osobných údajov: ako dlho sa uchovávajú jednotlivé údaje (GDPR).
 */
class ManagePrivacySettings extends BaseSettingsPage
{
    protected static string $settings = PrivacySettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 45;

    protected static ?string $slug = 'settings/privacy';

    public static function getNavigationLabel(): string
    {
        return __('admin.privacy.title');
    }

    public function getTitle(): string
    {
        return __('admin.privacy.title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.privacy.intro');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $retention = app(DataRetention::class);

        foreach ($retention->all() as $key => $policy) {
            $data['retention'][$key] = ['enabled' => $retention->isEnabled($policy), 'days' => $retention->days($policy)];
        }

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];

        foreach (app(DataRetention::class)->all() as $key => $policy) {
            $sections[] = Section::make(($policy->label)())->description(($policy->description)())->columns(2)->compact()->schema([
                Toggle::make("retention.{$key}.enabled")->label(__('admin.privacy.enabled'))->inline(false),
                TextInput::make("retention.{$key}.days")->label(__('admin.privacy.days'))->integer()->required()
                    ->minValue($policy->minDays)->maxValue(36500)->suffix(__('admin.privacy.days_suffix')),
            ]);
        }

        return $schema->components($sections);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run')
                ->label(__('admin.privacy.run'))
                ->icon(Heroicon::OutlinedPlay)
                ->requiresConfirmation()
                ->modalDescription(__('admin.privacy.run_confirm'))
                ->action(function (): void {
                    $results = app(DataRetention::class)->run();
                    Notification::make()->success()->title(__('admin.privacy.run_done', ['count' => array_sum($results)]))->send();
                }),
        ];
    }
}
