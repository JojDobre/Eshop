<?php

namespace Modules\Forms\Filament\Resources\Submissions;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
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
        return $schema->columns(1)->components(fn (?FormSubmission $record) => [
            TextEntry::make('form.name')->label(__('forms::admin.form')),
            TextEntry::make('created_at')->label(__('forms::admin.received_at'))->dateTime(),
            ...collect($record?->data ?? [])->map(fn (array $row, int $index) => TextEntry::make('row_'.$index)
                ->label($row['label'])->state($row['value'] !== '' ? $row['value'] : '—')
                ->copyable()->extraAttributes(['class' => 'whitespace-pre-line']))->all(),
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
                TextColumn::make('form.name')->label($t('form')),
                TextColumn::make('summary')->label($t('message'))->state(fn (FormSubmission $record) => $record->summary())
                    ->limit(90)->weight(fn (FormSubmission $record) => $record->read_at ? null : 'bold')
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('data', 'like', '%'.$search.'%')),
                TextColumn::make('email')->label($t('email'))->copyable()->toggleable(),
                TextColumn::make('created_at')->label($t('received_at'))->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('form_id')->label($t('form'))
                    ->options(fn () => Form::query()->get()->mapWithKeys(fn (Form $form) => [$form->id => $form->name])->all()),
                TernaryFilter::make('read_at')->label($t('read'))->nullable(),
            ])
            ->recordActions([
                // Otvorením sa správa označí ako prečítaná.
                ViewAction::make()->mutateRecordDataUsing(function (array $data, FormSubmission $record): array {
                    $record->read_at ?? $record->update(['read_at' => now()]);

                    return $data;
                }),
                Action::make('reply')->label($t('reply'))->icon(Heroicon::OutlinedArrowUturnLeft)->color('gray')
                    ->visible(fn (FormSubmission $record) => filled($record->email))
                    ->url(fn (FormSubmission $record) => 'mailto:'.$record->email),
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

    public static function getPages(): array
    {
        return ['index' => ManageFormSubmissions::route('/')];
    }
}
