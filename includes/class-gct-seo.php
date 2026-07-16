<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GCT_SEO {
    public static function init() {
        add_action('wp_head', [__CLASS__, 'output_hreflang'], 1);
        add_action('wp_head', [__CLASS__, 'output_canonical'], 1);
    }

    public static function output_hreflang() {
        if (!is_singular()) return;

        $post_id = get_queried_object_id();
        $translations = GCT_Post_Relations::get_translations($post_id);
        
        // Output Spanish (default)
        $source_id = GCT_Post_Relations::get_source($post_id);
        $es_url = get_permalink($source_id);
        echo '<link rel="alternate" hreflang="es" href="' . esc_url($es_url) . '" />' . "\n";
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($es_url) . '" />' . "\n";

        // Output translations
        foreach ($translations as $lang => $translated_id) {
            if ($lang !== 'es' && get_post_status($translated_id) === 'publish') {
                $translated_post = get_post($translated_id);
                if ($source_id == get_option('page_on_front')) {
                    $url = home_url("/$lang/");
                } else {
                    $url = home_url("/$lang/" . $translated_post->post_name . "/");
                }
                echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url($url) . '" />' . "\n";
            }
        }
    }

    public static function output_canonical() {
        // WordPress automatically outputs canonicals for the queried object, which the router overrides correctly.
        // If additional adjustments are needed, they can be done here.
    }
}