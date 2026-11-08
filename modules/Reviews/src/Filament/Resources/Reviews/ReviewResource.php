<?php

namespace Modules\Reviews\Filament\Resources\Reviews;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Reviews\Filament\Resources\Reviews\Pages\ManageReviews;
use Modules\Reviews\Filament\ReviewTable;
use Modules\Reviews\Models\Review;
use Modules\Reviews\ReviewSettings;
use UnitEnum;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?int $navigationSort = 35;

    protected static ?string $slug = 'reviews';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Catalog;
    }

    public static function getModelLabel(): string
    {
        return __('reviews::admin.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('reviews::admin.plural');
    }

    /** Počet hodnotení čakajúcich na schválenie v menu. */
    public static function getNavigationBadge(): ?string
    {
        $pending = Review::query()->where('status', Review::PENDING)->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function canAccess(): bool
    {
        return ReviewSettings::isEnabled() && (auth()->user()?->can('reviews.manage') ?? false);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components(ReviewTable::form());
    }

    public static function table(Table $table): Table
    {
        return ReviewTable::table($table);
    }

    public static function getPages(): array
    {
        return ['index' => ManageReviews::route('/')];
    }
}
