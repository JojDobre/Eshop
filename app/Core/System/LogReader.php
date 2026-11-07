<?php

namespace App\Core\System;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

/**
 * Čítanie logov Laravelu (storage/logs) pre prehliadač v administrácii.
 */
class LogReader
{
    /** Koľko posledných bajtov každého súboru čítať (veľké logy sa nenačítavajú celé). */
    private const TAIL_BYTES = 1024 * 1024;

    /**
     * @return list<array{id: string, file: string, date: ?Carbon, level: string, message: string, details: string}>
     */
    public function entries(int $limit = 300, ?string $level = null): array
    {
        $entries = [];

        foreach ($this->files() as $file) {
            foreach ($this->parse($file) as $entry) {
                if ($level === null || $entry['level'] === $level) {
                    $entries[] = $entry;
                }
            }
        }

        usort($entries, fn (array $a, array $b) => ($b['date']?->timestamp ?? 0) <=> ($a['date']?->timestamp ?? 0));

        return array_slice($entries, 0, $limit);
    }

    /**
     * @return list<string>
     */
    public function files(): array
    {
        $files = glob(storage_path('logs/*.log')) ?: [];
        usort($files, fn (string $a, string $b) => filemtime($b) <=> filemtime($a));

        return $files;
    }

    public function totalSize(): int
    {
        return (int) array_sum(array_map('filesize', $this->files()));
    }

    public function clear(): void
    {
        foreach ($this->files() as $file) {
            File::put($file, '');
        }
    }

    /**
     * @return list<array{id: string, file: string, date: ?Carbon, level: string, message: string, details: string}>
     */
    private function parse(string $file): array
    {
        $size = (int) filesize($file);
        $handle = fopen($file, 'rb');

        if ($handle === false || $size === 0) {
            return [];
        }

        if ($size > self::TAIL_BYTES) {
            fseek($handle, -self::TAIL_BYTES, SEEK_END);
        }

        $content = (string) stream_get_contents($handle);
        fclose($handle);

        $parts = preg_split('/^(?=\[\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})/m', $content) ?: [];
        $entries = [];

        foreach ($parts as $index => $part) {
            if (! preg_match('/^\[(?<date>[^\]]+)\] \w+\.(?<level>[A-Z]+): (?<message>.*)$/s', $part, $match)) {
                continue;
            }

            $lines = explode("\n", trim($match['message']), 2);

            try {
                $date = Carbon::parse($match['date']);
            } catch (\Throwable) {
                $date = null;
            }

            $entries[] = [
                'id' => md5($file.$index.$match['date']),
                'file' => basename($file),
                'date' => $date,
                'level' => strtolower($match['level']),
                'message' => mb_strimwidth($lines[0], 0, 300, '…'),
                'details' => trim($match['message']),
            ];
        }

        return $entries;
    }
}
