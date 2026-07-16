<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GCT_Switcher {
    public static function render() {
        $post_id = get_queried_object_id();
        $translations = GCT_Post_Relations::get_translations($post_id);
        $active_langs = GCT_Languages::get_active();
        array_unshift($active_langs, 'es'); // Ensure ES is first
        $current_lang = self::get_current_lang();

        echo '<nav class="gct-lang-nav" aria-label="Selector de idioma">';
        
        foreach ($active_langs as $lang) {
            $href = '';
            
            if ($lang === 'es') {
                $source_id = GCT_Post_Relations::get_source($post_id);
                $href = get_permalink($source_id);
            } else {
                if (isset($translations[$lang])) {
                    $translated_id = $translations[$lang];
                    if (get_post_status($translated_id) === 'publish') {
                        $source_id = GCT_Post_Relations::get_source($post_id);
                        $source_post = get_post($source_id);
                        $slug = $source_post->post_name;
                        
                        if ($source_id == get_option('page_on_front')) {
                            $href = home_url("/$lang/");
                        } else {
                            // Ideally, this should use the translated slug, but for simplicity we rely on the router parsing the source slug
                            $translated_post = get_post($translated_id);
                            $href = home_url("/$lang/" . $translated_post->post_name . "/");
                        }
                    }
                }
            }
            
            if ($href) {
                $aria = $lang === $current_lang ? 'aria-current="page"' : '';
                $label = GCT_Languages::get_label($lang);
                echo '<a href="' . esc_url($href) . '" hreflang="' . esc_attr($lang) . '" lang="' . esc_attr($lang) . '" ' . $aria . '>' . esc_html($label) . '</a>';
            }
        }
        
        echo '</nav>';
    }

    public static function get_current_lang() {
        $lang = get_query_var('gct_lang');
        return $lang ? $lang : 'es';
    }
}