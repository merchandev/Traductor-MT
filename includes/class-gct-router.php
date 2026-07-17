<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GCT_Router {
    public static function init() {
        add_filter('query_vars', [__CLASS__, 'add_query_vars']);
        add_action('init', [__CLASS__, 'add_rewrite_rules']);
        add_action('template_redirect', [__CLASS__, 'template_redirect'], 1);
        add_action('init', [__CLASS__, 'maybe_flush'], 999);
    }

    public static function add_query_vars($vars) {
        $vars[] = 'gct_lang';
        return $vars;
    }

    public static function add_rewrite_rules() {
        $langs = implode('|', array_keys(GCT_Languages::get_all()));
        
        add_rewrite_rule(
            '^(' . $langs . ')/?$',
            'index.php?gct_lang=$matches[1]',
            'top'
        );
        
        add_rewrite_rule(
            '^(' . $langs . ')/(.+?)/?$',
            'index.php?gct_lang=$matches[1]&pagename=$matches[2]',
            'top'
        );
    }

    public static function template_redirect() {
        if (is_admin()) return;
        
        $lang = get_query_var('gct_lang');
        if (!$lang) return;

        global $wp_query;
        $pagename = get_query_var('pagename');
        
        if (empty($pagename)) {
            $source_id = get_option('page_on_front');
        } else {
            $source_post = get_page_by_path($pagename, OBJECT, ['page', 'post', 'tours', 'rutas', 'destinos', 'servicios']);
            $source_id = $source_post ? $source_post->ID : 0;
        }

        if ($source_id) {
            $translated_id = GCT_Post_Relations::get_translation_for_lang($source_id, $lang);
            
            if ($translated_id && get_post_status($translated_id) === 'publish') {
                $translated_post = get_post($translated_id);
                $wp_query->queried_object = $translated_post;
                $wp_query->queried_object_id = $translated_id;
                $wp_query->post = $translated_post;
                $wp_query->posts = [$translated_post];
                $wp_query->is_page = $translated_post->post_type === 'page';
                $wp_query->is_singular = true;
                $wp_query->is_home = false;
                $wp_query->is_404 = false;
                status_header(200);
            } else {
                wp_redirect(get_permalink($source_id), 301);
                exit;
            }
        }
    }

    public static function flush() {
        self::add_rewrite_rules();
        flush_rewrite_rules();
    }

    public static function maybe_flush() {
        if (!get_option('gct_rules_v5')) {
            self::flush();
            update_option('gct_rules_v5', true);
        }
    }
}