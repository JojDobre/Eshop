<?php

namespace Modules\Catalog\Filament\Resources\Products\RelationManagers;

use App\Filament\Concerns\FillsContentTranslations;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductOption;
use Modules\Catalog\Models\ProductOptionValue;
use Modules\Catalog\VariantGenerator;

/**
 * Možnosti produktu (napr. Veľkosť, Farba) a ich hodnoty. Po uložení sa hneď vytvoria varianty pre nové kombinácie,
 * aby sa možnosti zobrazili na stránke produktu (bez variantov by zákazník nemal z čoho vyberať).
 */
class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('catalog::admin.variants.options');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('catalog::admin.variants.'.$key);

        return $schema->columns(1)->components([
            TextInput::make('name')->label($t('option_name'))->helperText($t('option_name_help'))->required()->maxLength(60),
            Repeater::make('values')->label($t('values'))
                ->relationship('values')
                ->orderColumn('position')
                ->mutateRelationshipDataBeforeFillUsing(fn (array $data) => FillsContentTranslations::contentTranslationsForForm(
                    ProductOptionValue::query()->findOrNew($data['id'] ?? null), $data,
                ))
                ->schema([
                    TextInput::make('value')->label($t('value'))->required()->maxLength(60),
                    ColorPicker::make('color')->label($t('color'))->helperText($t('color_help'))->hex()->regex('/^#[0-9a-fA-F]{6}$/'),
                ])
                ->columns(2)
                ->minItems(1)
                ->addActionLabel($t('add_value'))
                ->defaultItems(1),
        ]);
    }

    public function table(Table $table): Table
    {
        $t = fn (string $key) => __('catalog::admin.variants.'.$key);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('values'))
            ->description($t('options_help'))
            ->columns([
                TextColumn::make('name')->label($t('option_name')),
                TextColumn::make('values_list')->label($t('values'))
                    ->state(fn (ProductOption $record) => $record->values->map->value->implode(', ')),
            ])
            ->reorderable('position')
            ->defaultSort('position')
            ->headerActions([CreateAction::make()->label($t('add_option'))->after(fn () => $this->generateVariants())])
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(fn (array $data, ProductOption $record) => FillsContentTranslations::contentTranslationsForForm($record, $data))
                    ->after(fn () => $this->generateVariants()),
                DeleteAction::make(),
            ])
            ->paginated(false);
    }

    /**
     * Vytvorí varianty pre nové kombinácie hodnôt a oznámi to (existujúce varianty zostanú bez zmeny).
     */
    private function generateVariants(): void
    {
        /** @var Product $product */
        $product = $this->getOwnerRecord();

        try {
            $created = app(VariantGenerator::class)->generate($product);
        } catch (\RuntimeException $exception) {
            Notification::make()->danger()->title($exception->getMessage())->send();

            return;
        }

        if ($created > 0) {
            Notification::make()->success()
                ->title(trans_choice('catalog::admin.variants.generated', $created, ['count' => $created]))
                ->body(__('catalog::admin.variants.auto_generated'))
                ->send();
        }
    }
}
