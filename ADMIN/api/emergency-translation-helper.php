<?php
/**
 * Alertara QC - Shared Emergency Translation Helper for PHP Backend
 * Provides robust offline translation of notification titles, messages, and email templates into Filipino.
 */

if (!function_exists('translateTextToFilipino')) {
    function translateTextToFilipino(?string $text): string {
        if (!$text) return '';
        $cleaned = trim($text);

        // Direct Exact Phrase Mappings
        $exactMap = [
            'TEST ALERT' => 'MGA PAGSUBOK NA ALERTO',
            'TEST ALERT 1' => 'MGA PAGSUBOK NA ALERTO 1',
            'TEST ALERT 2' => 'MGA PAGSUBOK NA ALERTO 2',
            'TEST ALERT 3' => 'MGA PAGSUBOK NA ALERTO 3',
            'TEST 1' => 'PAGSUBOK 1',
            'TEST 2' => 'PAGSUBOK 2',
            'TEST 3' => 'PAGSUBOK 3',
            'TEST' => 'PAGSUBOK',
            'THIS IS A TEST' => 'ITO AY ISANG PAGSUBOK',
            'EMERGENCY ALERT' => 'ALERTO SA EMERHENSYA',
            'EMERGENCY ALERT • QUEZON CITY' => 'ALERTO SA EMERHENSYA • QUEZON CITY',
            'CRITICAL EMERGENCY ALERT' => 'MATAAS NA ALERTO SA EMERHENSYA',
            'WEATHER WARNING • QUEZON CITY' => 'BABALA SA PANAHON • QUEZON CITY',
            'WEATHER ADVISORY • QUEZON CITY' => 'ABISO SA PANAHON • QUEZON CITY',
            'WEATHER FORECAST • QUEZON CITY' => 'TAYA NG PANAHON • QUEZON CITY',
            'EARTHQUAKE BULLETIN' => 'ULAT NG LINDOL',
            'FIRE INCIDENT ALERT' => 'ALERTO SA SUNOG',
            'Public Safety Advisory' => 'Abiso sa Kaligtasan ng Publiko',
            'QUEZON CITY RAINFALL ADVISORY' => 'ABISO SA ULAN SA QUEZON CITY',
            'Open Alertara Emergency Portal' => 'Buksan ang Emergency Portal ng Alertara',
        ];

        if (isset($exactMap[$cleaned])) {
            return $exactMap[$cleaned];
        }

        $result = $cleaned;

        // Sentence & Dynamic Pattern Replacements
        $patterns = [
            '/TEST ALERT (\d+)/i' => 'MGA PAGSUBOK NA ALERTO $1',
            '/TEST (\d+)/i' => 'PAGSUBOK $1',
            '/^THIS IS A TEST$/i' => 'ITO AY ISANG PAGSUBOK',
            '/A public safety incident has been reported\.?/i' => 'Naiulat ang isang insidente sa kaligtasan ng publiko.',
            '/Affected area:\s*the affected area in Quezon City\.?/i' => 'Apektadong lugar: ang apektadong lugar sa Quezon City.',
            '/Protective action:\s*Avoid the affected area and follow instructions from local authorities\.?/i' => 'Paraan ng pag-iingat: Iwasan ang apektadong lugar at sundin ang mga tagubilin ng mga lokal na awtoridad.',
            '/Protective action:\s*Avoid the affected area\.?/i' => 'Paraan ng pag-iingat: Iwasan ang apektadong lugar.',
            '/Avoid the affected area and follow instructions from local authorities\.?/i' => 'Iwasan ang apektadong lugar at sundin ang mga tagubilin ng mga lokal na awtoridad.',
            '/WEATHER FORECAST\s*-\s*QUEZON CITY/i' => 'TAYA NG PANAHON - QUEZON CITY',
            '/PRECAUTIONS:\s*/i' => 'MGA PAG-IINGAT: ',
            '/Safety actions:\s*/i' => 'Paraan ng pag-iingat: ',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $result = preg_replace($pattern, $replacement, $result);
        }

        return $result;
    }
}
