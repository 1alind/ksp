<?php
/**
 * Aura Luxury Store - Multi-Language Dictionary
 * English (en), Arabic (ar), Kurdish Badini (ku - کوردی بادینی)
 * All default translation texts are loaded from /website/database/translations.json for easy editing.
 */

$translationsJsonFile = __DIR__ . '/database/translations.json';
$translations = [];

if (file_exists($translationsJsonFile)) {
    $rawTranslations = file_get_contents($translationsJsonFile);
    $decodedTranslations = json_decode($rawTranslations, true);
    if (is_array($decodedTranslations)) {
        $translations = $decodedTranslations;
    }
}

// Fallback safety structure if JSON is empty or missing
if (empty($translations)) {
    $translations = ['en' => [], 'ar' => [], 'ku' => []];
}

function t($key, $lang = 'en') {
    global $translations;
    $lang = in_array($lang, ['en', 'ar', 'ku']) ? $lang : 'en';
    return $translations[$lang][$key] ?? ($translations['en'][$key] ?? $key);
}

/**
 * Admin Translation Helper
 * Automatically detects current active language from session / global and looks up translations.
 */
function adm_t($key, $defaultEn = '') {
    global $lang, $translations;
    $currentLang = $lang ?? $_SESSION['lang'] ?? $_COOKIE['aura_lang'] ?? 'en';
    if (!in_array($currentLang, ['en', 'ar', 'ku'])) {
        $currentLang = 'en';
    }
    if (isset($translations[$currentLang][$key]) && $translations[$currentLang][$key] !== '') {
        return $translations[$currentLang][$key];
    }
    if (isset($translations['en'][$key]) && $translations['en'][$key] !== '') {
        return $translations['en'][$key];
    }
    return !empty($defaultEn) ? $defaultEn : $key;
}
?>
