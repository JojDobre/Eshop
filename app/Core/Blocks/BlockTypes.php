<?php

namespace App\Core\Blocks;

use Illuminate\Support\HtmlString;
use Throwable;

/**
 * Register typov blokov a ich vykreslenie v šablóne.
 */
class BlockTypes
{
    /** @var array<string, BlockType> */
    private array $types = [];

    public function register(BlockType $type): void
    {
        $this->types[$type->key] = $type;
    }

    public function find(string $key): ?BlockType
    {
        return $this->types[$key] ?? null;
    }

    /**
     * @return array<string, BlockType>
     */
    public function all(): array
    {
        return $this->types;
    }

    /**
     * Vykreslí bloky šablónou blocks/{typ}. Neznámy typ, skrytý blok alebo blok, ktorý šablóna
     * nepozná, sa vynechá; chyba jedného bloku nezhodí celú stránku.
     *
     * @param  list<array{type?: string, data?: array<string, mixed>}>|null  $blocks
     */
    public function render(?array $blocks): HtmlString
    {
        $html = '';

        foreach ($blocks ?? [] as $index => $block) {
            $type = $this->find((string) ($block['type'] ?? ''));
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            if ($type === null || ($data['hidden'] ?? false) || ! view()->exists('theme::blocks.'.$type->key)) {
                continue;
            }

            try {
                $data = $type->prepare ? ($type->prepare)($data) : $data;
                $html .= view('theme::blocks.'.$type->key, ['block' => $data, 'blockId' => 'block-'.$type->key.'-'.$index])->render();
            } catch (Throwable $e) {
                report($e);
            }
        }

        return new HtmlString($html);
    }
}
