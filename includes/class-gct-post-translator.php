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

        // Copy thumbnail
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
        
        if (!$source_post || $lang === 'es') return false;

        $provider = self::get_provider($provider_slug);
        if (!$provider || !$provider->is_configured()) return false;

        $translated_title = $provider->translate($source_post->post_title, 'es', $lang);
        $translated_content = $provider->translate($source_post->post_content, 'es', $lang);
        $translated_excerpt = $provider->translate($source_post->post_excerpt, 'es', $lang);

        $update = [
            'ID' => $post_id,
            'post_title' => $translated_title,
            'post_content' => $translated_content,
            'post_excerpt' => $translated_excerpt,
        ];
        wp_update_post($update);

        GCT_Post_Relations::set_status($post_id, 'machine');
        update_post_meta($post_id, '_gct_source_hash', md5($source_post->post_content));
        update_post_meta($post_id, '_gct_last_translated_at', current_time('mysql'));

        return true;
    }

    public static function get_provider($slug) {
        if ($slug === 'deepl') return new GCT_DeepL_Provider();
        if ($slug === 'google') return new GCT_Google_Provider();
        return null;
    }
}