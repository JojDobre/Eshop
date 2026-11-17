<?php

namespace Modules\Forms\Filament\Resources\Submissions;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Forms\Filament\Resources\Submissions\Pages\ManageFormSubmissions;
use Modules\Forms\Models\Form;
use Modules\Forms\Models\FormSubmission;
use UnitEnum;

class FormSubmissionResource extends Resource
{
    protected static ?string $model = FormSubmission::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?int $navigationSort = 16;

    protected static ?string $slug = 'form-submissions';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Content;
    }

    public static function getModelLabel(): string
    {
        return __('forms::admin.submission');
    }

    public static function getPluralModelLabel(): string
    {
        return __('forms::admin.submissions');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = FormSubmission::query()->unread()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('forms.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        // Vlastné zobrazenie: odosielateľ a kontakty hore, krátke polia v mriežke, text správy v rámčeku.
        return $schema->columns(1)->components(fn (?FormSubmission $record) => [
            View::make('forms::filament.submission')->viewData(['submission' => $record]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key, array $replace = []) => __('forms::admin.'.$key, $replace);

        return $table
            ->columns([
                IconColumn::make('read_at')->label('')->width('1%')
                    ->icon(fn (FormSubmission $record) => $record->read_at ? Heroicon::OutlinedEnvelopeOpen : Heroicon::Envelope)
                    ->color(fn (FormSubmission $record) => $record->read_at ? 'gray' : 'primary'),
                TextColumn::make('sender')->label($t('sender'))
                    ->state(fn (FormSubmission $record) => $record->senderName() ?? $record->email ?? '—')
                    ->description(fn (FormSubmission $record) => $record->senderName() !== null ? $record->email : null)
                    ->weight(fn (FormSubmission $record) => $record->read_at ? null : 'bold'),
                TextColumn::make('form.name')->label($t('form'))->badge()->color('gray'),
                TextColumn::make('summary')->label($t('message'))->state(fn (FormSubmission $record) => $record->preview())
                    ->limit(70)->color(fn (FormSubmission $record) => $record->read_at ? 'gray' : null)->weight(fn (FormSubmission $record) => $record->read_at ? null : 'bold')
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('data', 'like', '%'.$search.'%')),
                TextColumn::make('email')->label($t('email'))->copyable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label($t('received_at'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('form_id')->label($t('form'))
                    ->options(fn () => Form::query()->get()->mapWithKeys(fn (Form $form) => [$form->id => $form->name])->all()),
                TernaryFilter::make('read_at')->label($t('read'))->nullable(),
            ])
            ->recordActions([
                // Otvorením sa správa označí ako prečítaná.
                ViewAction::make()
                    ->modalHeading(fn (FormSubmission $record) => $record->form?->name ?? $t('submission_title'))
                    ->modalDescription(fn (FormSubmission $record) => $t('received_on', ['date' => $record->created_at?->translatedFormat('j. n. Y, H:i')]))
                    ->modalIcon(Heroicon::OutlinedEnvelopeOpen)
                    ->modalWidth('2xl')
                    ->modalCancelActionLabel($t('close'))
                    ->extraModalFooterActions(fn (FormSubmission $record) => [
                        Action::make('replyFromDetail')->label($t('reply'))->icon(Heroicon::OutlinedArrowUturnLeft)
                            ->visible(filled($record->email))->url(self::replyUrl($record)),
                        Action::make('markUnread')->label($t('mark_unread'))->icon(Heroicon::OutlinedEnvelope)->color('gray')
                            ->action(fn () => $record->update(['read_at' => null]))->cancelParentActions(),
                    ])
                    ->mutateRecordDataUsing(function (array $data, FormSubmission $record): array {
                        $record->read_at ?? $record->update(['read_at' => now()]);

                        return $data;
                    }),
                Action::make('reply')->label($t('reply'))->icon(Heroicon::OutlinedArrowUturnLeft)->color('gray')
                    ->visible(fn (FormSubmission $record) => filled($record->email))
                    ->url(fn (FormSubmission $record) => self::replyUrl($record)),
                DeleteAction::make(),
            ])
            ->recordAction('view')
            ->toolbarActions([
                BulkAction::make('markRead')->label($t('mark_read'))->icon(Heroicon::OutlinedEnvelopeOpen)
                    ->action(fn (Collection $records) => FormSubmission::query()->whereKey($records->modelKeys())->update(['read_at' => now()]))
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /** Odpoveď e-mailom s predvyplneným predmetom „Re: názov formulára“. */
    public static function replyUrl(FormSubmission $record): string
    {
        return 'mailto:'.$record->email.'?subject='.rawurlencode('Re: '.($record->form?->name ?? __('forms::admin.submission_title')));
    }

    public static function getPages(): array
    {
        return ['index' => ManageFormSubmissions::route('/')];
    }
}
