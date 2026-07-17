<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GCT_Post_Translator {
    public static function create_translation_draft($source_post_id, $lang) {
        $source_post = get_post($source_post_id);
        if (!$source_post) return false;

        $new_post = [
            'post_title'   => "[$lang] " . $source_post->post_title,
            'post_name'    => $lang . '-' . $source_post->post_name,
            'post_content' => $source_post->post_content,
            'post_excerpt' => $source_post->post_excerpt,
            'post_status'  => 'draft',
            'post_type'    => $source_post->post_type,
            'post_parent'  => $source_post->post_parent,
            'menu_order'   => $source_post->menu_order,
        ];
        
        $translated_id = wp_insert_post($new_post);
        if (is_wp_error($translated_id)) return false;

        $thumbnail_id = get_post_thumbnail_id($source_post_id);
        if ($thumbnail_id) {
            set_post_thumbnail($translated_id, $thumbnail_id);
        }

        GCT_Post_Relations::set_translation($source_post_id, $translated_id, $lang);
        GCT_Post_Relations::set_status($translated_id, 'pending');

        return $translated_id;
    }

    public static function translate_post($post_id, $provider_slug) {
        $source_id = GCT_Post_Relations::get_source($post_id);
        $lang = GCT_Post_Relations::get_language($post_id);
        $source_post = get_post($source_id);
        if (!$source_post || $lang === 'es') return 'Post de origen no válido o idioma incorrecto.';

        $provider = self::get_provider($provider_slug);
        if (!$provider || !$provider->is_configured()) return 'El proveedor de API no está configurado (revisa los ajustes y la API Key).';

        // STATIC HTML TRANSLATION LOGIC
        // 1. Get the frontend URL of the source post
        $source_url = get_permalink($source_id);
        $response = wp_remote_get($source_url, ['timeout' => 30]);
        
        if (is_wp_error($response)) {
            return 'Error al obtener el HTML original (wp_remote_get): ' . $response->get_error_message();
        }

        $html = wp_remote_retrieve_body($response);
        if (empty($html)) {
            return 'El HTML devuelto por la página original está vacío. Revisa si hay redirecciones o bloqueos de loopback.';
        }

        // 2. Parse DOM
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        // Use mb_convert_encoding to handle UTF-8 properly in DOMDocument
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        
        // Find text nodes that are not inside scripts, styles, etc.
        $text_nodes = $xpath->query('//text()[not(ancestor::script) and not(ancestor::style) and not(ancestor::noscript)]');
        
        $strings_to_translate = [];
        $nodes_map = [];

        foreach ($text_nodes as $node) {
            $val = trim($node->nodeValue);
            if (!empty($val) && preg_match('/[a-zA-Z]/', $val)) {
                $strings_to_translate[] = $val;
                $nodes_map[] = $node;
            }
        }

        // Also translate specific attributes like alt, placeholder, title, value (for buttons)
        $attr_nodes = $xpath->query('//@alt | //@title | //@placeholder | //input[@type="submit" or @type="button"]/@value');
        foreach ($attr_nodes as $attr) {
            $val = trim($attr->nodeValue);
            if (!empty($val) && preg_match('/[a-zA-Z]/', $val)) {
                $strings_to_translate[] = $val;
                $nodes_map[] = $attr;
            }
        }

        if (!empty($strings_to_translate)) {
            $translated_strings = $provider->translate_batch($strings_to_translate, 'es', $lang);
            
            // Re-inject translated strings
            foreach ($translated_strings as $i => $translated_text) {
                if (isset($nodes_map[$i])) {
                    // translatedText from Google might contain HTML entities like &#39;
                    $decoded_text = html_entity_decode($translated_text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $nodes_map[$i]->nodeValue = htmlspecialchars($decoded_text, ENT_QUOTES, 'UTF-8');
                }
            }
        }
        
        // Update <html> lang attribute
        $html_nodes = $dom->getElementsByTagName('html');
        if ($html_nodes->length > 0) {
            $html_nodes->item(0)->setAttribute('lang', $lang);
        }

        // 3. Save full HTML to post_content
        $final_html = $dom->saveHTML();

        $update = [
            'ID' => $post_id,
            'post_title' => $provider->translate($source_post->post_title, 'es', $lang),
            'post_content' => wp_slash($final_html)
        ];
        wp_update_post($update);

        GCT_Post_Relations::set_status($post_id, 'machine');
        update_post_meta($post_id, '_gct_is_html_translation', 'yes');
        update_post_meta($post_id, '_gct_last_translated_at', current_time('mysql'));

        return true;
    }

    public static function get_provider($slug) {
        if ($slug === 'deepl') {
            require_once GCT_DIR . 'includes/providers/class-gct-deepl-provider.php';
            return new GCT_DeepL_Provider();
        }
        if ($slug === 'google') {
            require_once GCT_DIR . 'includes/providers/class-gct-google-provider.php';
            return new GCT_Google_Provider();
        }
        return null;
    }
}