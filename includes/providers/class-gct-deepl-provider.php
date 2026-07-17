<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GCT_DeepL_Provider implements GCT_Provider_Interface {
    private $api_key;
    private $api_url;

    public function __construct() {
        $this->api_key = get_option('gct_api_key');
        $this->api_url = strpos($this->api_key, ':fx') !== false ? 'https://api-free.deepl.com/v2/translate' : 'https://api.deepl.com/v2/translate';
    }

    public function is_configured(): bool {
        return !empty($this->api_key);
    }

    public function translate(string $text, string $source_lang, string $target_lang): string {
        $res = $this->translate_batch([$text], $source_lang, $target_lang);
        return !empty($res) ? $res[0] : $text;
    }

    public function translate_batch(array $texts, string $source_lang, string $target_lang): array {
        if (!$this->is_configured() || empty($texts)) return $texts;
        
        $body = [
            'text' => $texts,
            'source_lang' => strtoupper($source_lang),
            'target_lang' => strtoupper($target_lang === 'en' ? 'EN-GB' : $target_lang),
        ];

        $response = wp_remote_post($this->api_url, [
            'headers' => ['Authorization' => 'DeepL-Auth-Key ' . $this->api_key, 'Content-Type' => 'application/json'],
            'body'    => json_encode($body),
            'timeout' => 30
        ]);

        if (is_wp_error($response)) return $texts;
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['translations'])) {
            return array_column($body['translations'], 'text');
        }
        
        return $texts;
    }
}