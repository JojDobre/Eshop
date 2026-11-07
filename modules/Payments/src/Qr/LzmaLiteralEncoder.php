<?php

namespace Modules\Payments\Qr;

/**
 * Minimálny kompresor LZMA1 (surový prúd, lc=3, lp=0, pb=2) v čistom PHP.
 *
 * PAY by square vyžaduje dáta komprimované LZMA, no webhostingy nemajú rozšírenie xz ani
 * možnosť spustiť program. Kóduje sa iba ako literály (bez hľadania zhôd) – výstup je platný
 * prúd LZMA, ktorý dekóduje ktorýkoľvek dekodér. Pri krátkych platobných údajoch je rozdiel
 * vo veľkosti oproti plnej kompresii zanedbateľný. Na konci je značka konca prúdu.
 */
final class LzmaLiteralEncoder
{
    private const PROB_INIT = 1024;

    private int $low = 0;

    private int $range = 0xFFFFFFFF;

    private int $cache = 0;

    private int $cacheSize = 1;

    private string $output = '';

    public static function compress(string $data): string
    {
        return (new self)->encode($data);
    }

    private function encode(string $data): string
    {
        $isMatch = array_fill(0, 12 * 4, self::PROB_INIT);
        $isRep = array_fill(0, 12, self::PROB_INIT);
        $literal = array_fill(0, 8 * 0x300, self::PROB_INIT);
        $lenChoice = [self::PROB_INIT];
        $lenLow = array_fill(0, 4 * 8, self::PROB_INIT);
        $posSlot = array_fill(0, 4 * 64, self::PROB_INIT);
        $align = array_fill(0, 16, self::PROB_INIT);

        $previous = 0;
        $length = strlen($data);

        // Samé literály: stav kódera ostáva 0.
        for ($position = 0; $position < $length; $position++) {
            $byte = ord($data[$position]);
            $this->bit($isMatch, $position & 3, 0);

            $base = 0x300 * ($previous >> 5);
            $symbol = 1;

            for ($i = 7; $i >= 0; $i--) {
                $bit = ($byte >> $i) & 1;
                $this->bit($literal, $base + $symbol, $bit);
                $symbol = ($symbol << 1) | $bit;
            }

            $previous = $byte;
        }

        // Značka konca: zhoda s dĺžkou 2 a vzdialenosťou 0xFFFFFFFF.
        $posState = $length & 3;
        $this->bit($isMatch, $posState, 1);
        $this->bit($isRep, 0, 0);
        $this->bit($lenChoice, 0, 0);
        $this->tree($lenLow, $posState * 8, 3, 0);
        $this->tree($posSlot, 0, 6, 63);
        $this->direct((1 << 26) - 1, 26);
        $this->reverseTree($align, 4, 15);

        for ($i = 0; $i < 5; $i++) {
            $this->shiftLow();
        }

        return $this->output;
    }

    /**
     * @param  array<int, int>  $probs
     */
    private function bit(array &$probs, int $index, int $bit): void
    {
        $prob = $probs[$index];
        $bound = ($this->range >> 11) * $prob;

        if ($bit === 0) {
            $this->range = $bound;
            $probs[$index] = $prob + ((2048 - $prob) >> 5);
        } else {
            $this->low += $bound;
            $this->range -= $bound;
            $probs[$index] = $prob - ($prob >> 5);
        }

        $this->normalize();
    }

    /**
     * @param  array<int, int>  $probs
     */
    private function tree(array &$probs, int $offset, int $bits, int $value): void
    {
        $m = 1;

        for ($i = $bits - 1; $i >= 0; $i--) {
            $bit = ($value >> $i) & 1;
            $this->bit($probs, $offset + $m, $bit);
            $m = ($m << 1) | $bit;
        }
    }

    /**
     * @param  array<int, int>  $probs
     */
    private function reverseTree(array &$probs, int $bits, int $value): void
    {
        $m = 1;

        for ($i = 0; $i < $bits; $i++) {
            $bit = $value & 1;
            $value >>= 1;
            $this->bit($probs, $m, $bit);
            $m = ($m << 1) | $bit;
        }
    }

    private function direct(int $value, int $bits): void
    {
        for ($i = $bits - 1; $i >= 0; $i--) {
            $this->range >>= 1;

            if ((($value >> $i) & 1) === 1) {
                $this->low += $this->range;
            }

            $this->normalize();
        }
    }

    private function normalize(): void
    {
        while ($this->range < 0x01000000) {
            $this->range = ($this->range << 8) & 0xFFFFFFFF;
            $this->shiftLow();
        }
    }

    private function shiftLow(): void
    {
        if ($this->low < 0xFF000000 || $this->low > 0xFFFFFFFF) {
            $carry = $this->low >> 32;
            $temp = $this->cache;

            do {
                $this->output .= chr(($temp + $carry) & 0xFF);
                $temp = 0xFF;
            } while (--$this->cacheSize !== 0);

            $this->cache = ($this->low >> 24) & 0xFF;
        }

        $this->cacheSize++;
        $this->low = ($this->low & 0x00FFFFFF) << 8;
    }
}
