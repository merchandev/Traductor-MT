<?php
if ( ! defined( 'ABSPATH' ) ) exit;

interface GCT_Provider_Interface {
    public function translate( string $text, string $source_lang, string $target_lang ): string;
    public function translate_batch( array $texts, string $source_lang, string $target_lang ): array;
    public function is_configured(): bool;
}