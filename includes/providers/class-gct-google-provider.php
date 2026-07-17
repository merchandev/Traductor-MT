<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GCT_Google_Provider implements GCT_Provider_Interface {
    private $api_key;
    private $api_url = 'https://translation.googleapis.com/language/translate/v2';

    public function __construct() {
        $this->api_key = get_option('gct_api_key');
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

        $chunks = array_chunk($texts, 100);
        $translated_texts = [];

        foreach ($chunks as $chunk) {
            $body = [
                'q' => $chunk,
                'source' => $source_lang,
                'target' => $target_lang,
                'format' => 'html'
            ];

            $response = wp_remote_post($this->api_url . '?key=' . $this->api_key, [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => json_encode($body),
                'timeout' => 30
            ]);

            if (is_wp_error($response)) {
                $translated_texts = array_merge($translated_texts, $chunk);
                continue;
            }
            
            $res_body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($res_body['data']['translations'])) {
                $translated_texts = array_merge($translated_texts, array_column($res_body['data']['translations'], 'translatedText'));
            } else {
                $translated_texts = array_merge($translated_texts, $chunk);
            }
        }
        
        return $translated_texts;
    }
}