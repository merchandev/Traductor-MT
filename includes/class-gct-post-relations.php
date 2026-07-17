<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GCT_Post_Relations {
    public static function set_translation($source_id, $translated_id, $lang) {
        $group = 'page-' . $source_id;
        
        update_post_meta($source_id, '_gct_language', 'es');
        update_post_meta($source_id, '_gct_translation_group', $group);
        
        update_post_meta($translated_id, '_gct_language', $lang);
        update_post_meta($translated_id, '_gct_source_post_id', $source_id);
        update_post_meta($translated_id, '_gct_translation_group', $group);
    }

    public static function get_translations($post_id) {
        $source_id = self::get_source($post_id);
        if (!$source_id) return [];
        $group = 'page-' . $source_id;
        
        $args = [
            'post_type' => 'any',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => [
                ['key' => '_gct_translation_group', 'value' => $group]
            ],
            'fields' => 'ids'
        ];
        
        $translations = ['es' => $source_id];
        $posts = get_posts($args);
        foreach ($posts as $id) {
            $lang = get_post_meta($id, '_gct_language', true);
            if ($lang && $lang !== 'es') {
                $translations[$lang] = $id;
            }
        }
        return $translations;
    }

    public static function get_source($post_id) {
        $source_id = get_post_meta($post_id, '_gct_source_post_id', true);
        return $source_id ? (int) $source_id : $post_id;
    }

    public static function get_language($post_id) {
        $lang = get_post_meta($post_id, '_gct_language', true);
        return $lang ? $lang : 'es';
    }

    public static function get_translation_for_lang($post_id, $lang) {
        if ($lang === 'es') return self::get_source($post_id);
        $translations = self::get_translations($post_id);
        return isset($translations[$lang]) ? $translations[$lang] : null;
    }

    public static function get_translation_group($post_id) {
        $source_id = self::get_source($post_id);
        return 'page-' . $source_id;
    }

    public static function mark_outdated($source_id) {
        $translations = self::get_translations($source_id);
        foreach ($translations as $lang => $id) {
            if ($lang !== 'es') {
                self::set_status($id, 'outdated');
            }
        }
    }

    public static function get_status($post_id) {
        return get_post_meta($post_id, '_gct_translation_status', true);
    }

    public static function set_status($post_id, $status) {
        update_post_meta($post_id, '_gct_translation_status', $status);
    }
}