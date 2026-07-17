<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GCT_Languages {
    public static $languages = [
        'es' => ['label' => 'ES', 'locale' => 'es_ES', 'flag' => '🇪🇸'],
        'en' => ['label' => 'EN', 'locale' => 'en_GB', 'flag' => '🇬🇧'],
        'fr' => ['label' => 'FR', 'locale' => 'fr_FR', 'flag' => '🇫🇷'],
        'de' => ['label' => 'DE', 'locale' => 'de_DE', 'flag' => '🇩🇪'],
        'it' => ['label' => 'IT', 'locale' => 'it_IT', 'flag' => '🇮🇹'],
        'pt' => ['label' => 'PT', 'locale' => 'pt_PT', 'flag' => '🇵🇹'],
    ];

    public static function init() {}

    public static function get_active() {
        $active = get_option('gct_active_languages', ['en', 'fr', 'de', 'it', 'pt']);
        return is_array($active) ? $active : [];
    }

    public static function get_all() {
        return self::$languages;
    }

    public static function get_label($code) {
        return isset(self::$languages[$code]) ? self::$languages[$code]['label'] : strtoupper($code);
    }

    public static function is_valid($code) {
        return array_key_exists($code, self::$languages);
    }
}