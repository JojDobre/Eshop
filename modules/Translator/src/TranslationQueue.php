<?php

namespace Modules\Translator;

use App\Core\Content\ShopLocales;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Modules\Translator\Models\TranslationQueueItem;
use Throwable;

/**
 * Hromadný preklad: záznamy bez prekladu sa zaradia do frontu a prekladajú sa po dávkach
 * (plánovačom každú minútu alebo priamo na stránke v administrácii, kým je otvorená).
 */
class TranslationQueue
{
    public function __construct(private readonly MachineTranslationService $translator) {}

    /**
     * Záznamy, ktorým v jazyku chýba názov (alebo hlavný text).
     *
     * @param  list<class-string<Model>>  $models
     * @return array{records: int, characters: int}
     */
    public function estimate(array $models, string $target): array
    {
        $records = 0;
        $characters = 0;
        $source = app(ShopLocales::class)->default();

        foreach ($this->missing($models, $target) as $record) {
            $records++;
            $characters += $this->translator->estimate($record, $source);
        }

        return ['records' => $records, 'characters' => $characters];
    }

    /**
     * @param  list<class-string<Model>>  $models
     */
    public function enqueue(array $models, string $target): int
    {
        $source = app(ShopLocales::class)->default();
        $count = 0;

        foreach ($this->missing($models, $target) as $record) {
            TranslationQueueItem::query()->firstOrCreate(
                ['translatable_type' => $record->getMorphClass(), 'translatable_id' => $record->getKey(), 'target_locale' => $target, 'status' => TranslationQueueItem::PENDING],
                ['source_locale' => $source],
            );
            $count++;
        }

        return $count;
    }

    /**
     * Spracuje dávku. Vráti počet preložených záznamov.
     */
    public function process(int $limit = 10): int
    {
        if (! $this->translator->isConfigured()) {
            return 0;
        }

        $done = 0;

        foreach (TranslationQueueItem::query()->where('status', TranslationQueueItem::PENDING)->orderBy('id')->limit($limit)->get() as $item) {
            $record = $item->translatable;

            if ($record === null) {
                $item->update(['status' => TranslationQueueItem::DONE]);

                continue;
            }

            try {
                $this->translator->translateModel($record, $item->source_locale, $item->target_locale);
                $item->update(['status' => TranslationQueueItem::DONE, 'error' => null]);
                $done++;
            } catch (TranslationLimitExceeded $e) {
                // Limit sa minul: zvyšok počká do ďalšieho mesiaca alebo zvýšenia limitu.
                $item->update(['error' => $e->getMessage()]);

                break;
            } catch (Throwable $e) {
                Log::warning('Machine translation failed.', ['item' => $item->id, 'exception' => $e->getMessage()]);
                $item->update(['status' => TranslationQueueItem::FAILED, 'error' => mb_substr($e->getMessage(), 0, 1000)]);
            }
        }

        return $done;
    }

    public function pending(): int
    {
        return TranslationQueueItem::query()->where('status', TranslationQueueItem::PENDING)->count();
    }

    public function lastError(): ?string
    {
        return TranslationQueueItem::query()->whereNotNull('error')->latest('updated_at')->value('error');
    }

    /**
     * @param  list<class-string<Model>>  $models
     * @return iterable<Model>
     */
    private function missing(array $models, string $target): iterable
    {
        foreach ($models as $model) {
            foreach ($model::query()->lazyById(200) as $record) {
                $attribute = in_array('name', $record->getTranslatableAttributes(), true) ? 'name' : ($record->getTranslatableAttributes()[0] ?? null);

                if ($attribute !== null && blank($record->getTranslation($attribute, $target, false))) {
                    yield $record;
                }
            }
        }
    }
}
