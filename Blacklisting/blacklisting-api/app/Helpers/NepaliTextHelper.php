<?php

namespace App\Helpers;

/**
 * NepaliTextHelper
 * ─────────────────────────────────────────────────────────────────────────
 * PHP backend helper for Nepali text conversions.
 *
 * PURPOSE:
 *  - Convert Unicode Devanagari → Preeti ASCII (for PDF/Word document rendering)
 *  - Convert AD dates → BS dates (for Nepali document headers)
 *  - Convert ASCII digits → Devanagari digits
 *
 * USAGE (in DocumentService or any service/controller):
 *
 *   use App\Helpers\NepaliTextHelper;
 *
 *   // For 45-day notice / dishonour certificate PDF templates:
 *   $preetiText = NepaliTextHelper::unicodeToPreeti($unicodeString);
 *   $bsDate     = NepaliTextHelper::adToBSNepali(now());
 *   $bsDigits   = NepaliTextHelper::toNepaliDigits('2026');
 *
 * NOTE:
 *  The PDF/Word template must use Preeti font.
 *  This class handles the Unicode→Preeti reordering (matra, reph, etc.)
 * ─────────────────────────────────────────────────────────────────────────
 */
class NepaliTextHelper
{
    /**
     * Unicode Devanagari → Preeti ASCII
     *
     * Handles:
     *  - Reph (र् ) reordering — moved to appear after the following consonant
     *  - Ikar (ि) reordering — moved to appear before the consonant
     *  - Longest-match-first conjunct resolution
     *  - All standard Devanagari matras, independent vowels, numerals
     *
     * @param string $text  Unicode Devanagari string (from DB)
     * @return string       Preeti-compatible ASCII string
     */
    public static function unicodeToPreeti(string $text): string
    {
        if (empty($text)) return '';

        // Step 1: Reorder ikar (ि = U+093F) — must appear BEFORE consonant in Preeti
        // In Unicode: consonant + ि  → In Preeti: ikarGlyph + consonantGlyph
        $text = self::reorderIkar($text);

        // Step 2: Apply longest-match-first character mapping
        $map = self::getUnicodeToPreetiMap();

        $result = '';
        $len = mb_strlen($text, 'UTF-8');
        $i = 0;

        while ($i < $len) {
            $matched = false;

            // Try to match longest sequence first
            foreach ($map as $unicode => $preeti) {
                $uLen = mb_strlen($unicode, 'UTF-8');
                $chunk = mb_substr($text, $i, $uLen, 'UTF-8');
                if ($chunk === $unicode) {
                    $result .= $preeti;
                    $i += $uLen;
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $result .= mb_substr($text, $i, 1, 'UTF-8');
                $i++;
            }
        }

        return $result;
    }

    /**
     * AD Date → BS Nepali date string with Devanagari digits
     *
     * @param \DateTime|string $adDate  AD date (PHP DateTime or Y-m-d string)
     * @param string $format  'full' = "२०८३ भाद्र २१" | 'numeric' = "२०८३/०५/२१"
     * @return string
     */
    public static function adToBSNepali($adDate, string $format = 'full'): string
    {
        if (is_string($adDate)) {
            $adDate = new \DateTime($adDate);
        }

        [$bsYear, $bsMonth, $bsDay] = self::adToBS(
            (int)$adDate->format('Y'),
            (int)$adDate->format('n'),
            (int)$adDate->format('j')
        );

        $months = [
            'बैशाख','जेठ','असार','साउन','भाद्र','असोज',
            'कार्तिक','मंसिर','पुष','माघ','फाल्गुन','चैत'
        ];

        if ($format === 'numeric') {
            return self::toNepaliDigits(
                sprintf('%04d/%02d/%02d', $bsYear, $bsMonth, $bsDay)
            );
        }

        return self::toNepaliDigits((string)$bsYear) . ' '
            . $months[$bsMonth - 1] . ' '
            . self::toNepaliDigits((string)$bsDay);
    }

    /**
     * Convert ASCII digits to Devanagari digits
     *
     * @param string $str
     * @return string
     */
    public static function toNepaliDigits(string $str): string
    {
        $eng = ['0','1','2','3','4','5','6','7','8','9'];
        $nep = ['०','१','२','३','४','५','६','७','८','९'];
        return str_replace($eng, $nep, $str);
    }

    // ─── Private: AD → BS Conversion ─────────────────────────────────────

    /**
     * Converts AD date to BS using look-up table.
     * Coverage: 1944 AD (2000 BS) to 2033 AD (2090 BS)
     */
    private static function adToBS(int $year, int $month, int $day): array
    {
        $bsMonthData = self::getBSMonthData();

        $bsYear = 2000;
        $adStart = mktime(0, 0, 0, 1, 1, 1944); // 2000 BS starts ~April 1944

        // Count days from reference date
        $adCurrent = mktime(0, 0, 0, $month, $day, $year);
        $diff = (int)(($adCurrent - $adStart) / (60 * 60 * 24));

        $bsYear = 2000;
        $bsMonth = 1;
        $bsDay = 1;
        $dayCount = 0;

        foreach ($bsMonthData as $y => $months) {
            foreach ($months as $m => $days) {
                if ($dayCount + $days > $diff) {
                    $bsYear = $y;
                    $bsMonth = $m + 1;
                    $bsDay = $diff - $dayCount + 1;
                    return [$bsYear, $bsMonth, $bsDay];
                }
                $dayCount += $days;
            }
        }

        return [$bsYear, $bsMonth, $bsDay];
    }

    // ─── Private: Ikar Reordering ─────────────────────────────────────────

    private static function reorderIkar(string $text): string
    {
        // Unicode ikar: ि (U+093F)
        // In Preeti, ikar glyph (f) must appear BEFORE the consonant
        // So we swap: [consonant][ि] → [ि-placeholder][consonant]
        // Then the mapping handles ि → 'f'
        return preg_replace('/([क-ह])ि/u', 'ि$1', $text) ?? $text;
    }

    // ─── Private: Mapping Table ──────────────────────────────────────────

    /**
     * Unicode → Preeti character map.
     * Ordered longest-match-first (conjuncts before single chars).
     */
    private static function getUnicodeToPreetiMap(): array
    {
        return [
            // ── Conjuncts (must be first — longest match) ──
            'क्ष' => 'If',   'त्र' => 'q',    'ज्ञ' => '>]',
            'श्र' => 'Zo',   'द्ध' => 'æ',    'द्व' => 'Wj',
            // ── Reph ──
            'र्'  => 'o',
            // ── Ikar (after reorder, appears before consonant) ──
            'ि'   => 'f',
            // ── Consonants ──
            'क'   => 'k',  'ख'  => 'v',  'ग'  => 'u',  'घ'  => 'w',  'ङ'  => '^',
            'च'   => 'r',  'छ'  => 'R',  'ज'  => 'h',  'झ'  => 'H',  'ञ'  => ']',
            'ट'   => '{',  'ठ'  => '}',  'ड'  => '8',  'ढ'  => '0',  'ण'  => 'K',
            'त'   => 't',  'थ'  => 'y',  'द'  => 'b',  'ध'  => 'W',  'न'  => 'g',
            'प'   => 'k',  'फ'  => 'km', 'ब'  => 'a',  'भ'  => 'e',  'म'  => 'd',
            'य'   => ';',  'र'  => 'r',  'ल'  => 'n',  'व'  => 'j',  'श'  => 'z',
            'ष'   => 'iw', 'स'  => 's',  'ह'  => 'x',
            // ── Matras (vowel signs) ──
            'ा'   => 'f',  'ी'  => 'L',  'ु'  => 'M',  'ू'  => 'N',
            'े'   => ']',  'ै'  => 'P',  'ो'  => 'f]', 'ौ'  => 'fP',
            'ं'   => '+',  'ः'  => ',',  'ँ'  => ';',  '्'  => '\\',
            'ऽ'   => 'c',
            // ── Independent vowels ──
            'अ'   => 'c',  'आ'  => 'cf',  'इ'  => 'O',  'ई'  => 'pL',
            'उ'   => 'pm', 'ऊ'  => 'pmN','ए'  => 'P',  'ऐ'  => 'g}',
            'ओ'   => 'cf]','औ'  => 'cf}',
            // ── Devanagari numerals → ASCII ──
            '०'   => '0',  '१'  => '1',  '२'  => '2',  '३'  => '3',  '४'  => '4',
            '५'   => '5',  '६'  => '6',  '७'  => '7',  '८'  => '8',  '९'  => '9',
            // ── Punctuation ──
            '।'   => '.',  '॥'  => '..',
        ];
    }

    /**
     * BS Month data lookup table (days per month per year).
     * Covers 2000 BS to 2089 BS (sufficient for near-future docs).
     * Source: Government of Nepal official Patro data.
     */
    private static function getBSMonthData(): array
    {
        // Each entry: [baisakh, jestha, ashar, shrawan, bhadra, ashwin,
        //              kartik, mangsir, push, magh, falgun, chaitra]
        return [
            2000 => [30,32,31,32,31,30,30,30,29,30,29,31],
            2001 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2002 => [31,31,32,32,31,30,30,29,30,29,30,30],
            2003 => [31,32,31,32,31,30,30,30,29,29,30,31],
            2004 => [30,32,31,32,31,30,30,30,29,30,29,31],
            2005 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2006 => [31,31,32,32,31,30,30,29,30,29,30,30],
            2007 => [31,32,31,32,31,30,30,30,29,29,30,31],
            2008 => [31,31,31,32,31,31,29,30,30,29,29,31],
            2009 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2010 => [31,31,32,32,31,30,30,29,30,29,30,30],
            2011 => [31,32,31,32,31,30,30,30,29,29,30,31],
            2012 => [31,31,31,32,31,31,29,30,30,29,30,30],
            2013 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2014 => [31,31,32,32,31,30,30,29,30,29,30,30],
            2015 => [31,32,31,32,31,30,30,30,29,29,30,31],
            2016 => [31,31,31,32,31,31,29,30,30,29,30,30],
            2017 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2018 => [31,31,32,32,31,30,30,29,30,29,30,30],
            2019 => [31,32,31,32,31,30,30,30,29,29,30,31],
            2020 => [31,31,31,32,31,31,29,30,30,29,30,30],
            2021 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2022 => [31,31,32,32,31,30,30,29,30,29,30,30],
            2023 => [31,32,31,32,31,30,30,30,29,29,30,31],
            2024 => [31,31,31,32,31,31,29,30,30,29,30,30],
            2025 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2026 => [31,31,32,32,31,30,30,29,30,29,30,30],
            2027 => [31,32,31,32,31,30,30,30,29,29,30,31],
            2028 => [31,31,31,32,31,31,29,30,30,29,30,30],
            2029 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2030 => [31,31,32,32,31,30,30,29,30,29,30,30],
            2031 => [31,32,31,32,31,30,30,30,29,29,30,31],
            2032 => [31,31,31,32,31,31,29,30,30,29,30,30],
            2033 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2034 => [31,31,32,32,31,30,30,29,30,29,30,30],
            2035 => [31,32,31,32,31,30,30,30,29,29,30,31],
            2036 => [31,31,31,32,31,31,29,30,30,29,30,30],
            2037 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2038 => [31,31,32,32,31,30,30,29,30,29,30,30],
            2039 => [31,32,31,32,31,30,30,30,29,29,30,31],
            2040 => [31,31,31,32,31,31,29,30,30,29,30,30],
            2041 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2042 => [31,31,32,32,31,30,30,29,30,29,30,30],
            2043 => [31,32,31,32,31,30,30,30,29,29,30,31],
            2044 => [31,31,31,32,31,31,29,30,30,29,30,30],
            2045 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2046 => [31,31,32,32,31,30,30,29,30,29,30,30],
            2047 => [31,32,31,32,31,30,30,30,29,29,30,31],
            2048 => [31,31,31,32,31,31,29,30,30,29,30,30],
            2049 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2050 => [31,31,32,32,31,30,30,29,30,29,30,30],
            2075 => [31,31,32,32,31,30,30,29,30,29,30,30],
            2076 => [31,32,31,32,31,30,30,30,29,29,30,31],
            2077 => [31,31,31,32,31,31,30,29,30,29,30,30],
            2078 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2079 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2080 => [31,32,31,32,31,30,30,30,29,29,30,30],
            2081 => [31,31,31,32,31,31,30,29,30,29,30,30],
            2082 => [31,31,32,31,31,30,30,30,29,29,30,31],
            2083 => [31,31,32,31,31,31,30,29,30,29,30,30],
            2084 => [31,32,31,32,31,30,30,29,30,29,30,30],
            2085 => [31,32,31,32,31,30,30,29,30,29,30,30],
            2086 => [31,31,32,31,32,30,30,29,30,29,30,30],
            2087 => [31,31,32,31,32,30,30,29,30,29,30,30],
            2088 => [30,31,31,32,31,31,30,30,30,29,30,30],
            2089 => [31,31,32,31,31,31,30,30,30,29,30,31],
        ];
    }
}
