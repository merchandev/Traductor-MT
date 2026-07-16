<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GCT_Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('wp_ajax_gct_create_translation', [__CLASS__, 'ajax_create_translation']);
        add_action('wp_ajax_gct_run_translation', [__CLASS__, 'ajax_run_translation']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function add_settings_page() {
        add_options_page('GCT Translator', 'GCT Traductor SEO', 'manage_options', 'gct-translator', [__CLASS__, 'settings_page_html']);
    }

    public static function register_settings() {
        register_setting('gct_translator_group', 'gct_api_provider');
        register_setting('gct_translator_group', 'gct_api_key');
        register_setting('gct_translator_group', 'gct_active_languages');
    }

    public static function settings_page_html() { ?>
        <div class="wrap">
            <h1>GCT Translator &mdash; SEO Edition v5</h1>
            <form method="post" action="options.php">
                <?php settings_fields('gct_translator_group'); ?>
                <table class="form-table">
                    <tr><th>Proveedor API</th><td>
                        <select name="gct_api_provider">
                            <option value="deepl" <?php selected(get_option('gct_api_provider'), 'deepl'); ?>>DeepL API</option>
                            <option value="google" <?php selected(get_option('gct_api_provider'), 'google'); ?>>Google Cloud Translation</option>
                        </select>
                    </td></tr>
                    <tr><th>API Key</th><td>
                        <input type="password" name="gct_api_key" value="<?php echo esc_attr(get_option('gct_api_key')); ?>" class="regular-text" />
                    </td></tr>
                    <tr><th>Idiomas Activos</th><td>
                        <?php 
                        $active = GCT_Languages::get_active();
                        $langs = ['en', 'fr', 'de', 'it', 'pt'];
                        foreach ($langs as $l) {
                            $checked = in_array($l, $active) ? 'checked' : '';
                            echo "<label><input type='checkbox' name='gct_active_languages[]' value='$l' $checked /> " . strtoupper($l) . "</label><br>";
                        }
                        ?>
                    </td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    <?php }

    public static function add_meta_boxes() {
        $screens = ['page', 'post', 'tours', 'rutas', 'destinos', 'servicios'];
        foreach ($screens as $screen) {
            add_meta_box('gct_translation_meta', 'Traducciones GCT', [__CLASS__, 'meta_box_html'], $screen, 'side', 'high');
        }
    }

    public static function meta_box_html($post) {
        $source_id = GCT_Post_Relations::get_source($post->ID);
        $translations = GCT_Post_Relations::get_translations($source_id);
        $active = GCT_Languages::get_active();
        
        echo '<div class="gct-meta-box">';
        echo '<p><strong>Idioma Original:</strong> ES</p>';
        
        foreach ($active as $lang) {
            $label = GCT_Languages::get_label($lang);
            echo "<div class='gct-translation-item' style='margin-bottom:10px;'>";
            echo "<strong>$label:</strong> ";
            
            if (isset($translations[$lang])) {
                $tid = $translations[$lang];
                $status = GCT_Post_Relations::get_status($tid);
                echo "<a href='" . get_edit_post_link($tid) . "'>Editar traducción</a> <span class='gct-badge gct-badge--$status'>$status</span>";
                echo "<br><button type='button' class='button gct-btn-translate' data-post='$tid' data-nonce='" . wp_create_nonce('gct_trans') . "'>Traducir con API</button>";
            } else {
                echo "<button type='button' class='button gct-btn-create' data-source='$source_id' data-lang='$lang' data-nonce='" . wp_create_nonce('gct_create') . "'>Crear borrador</button>";
            }
            echo "</div>";
        }
        echo '</div>';
    }

    public static function ajax_create_translation() {
        check_ajax_referer('gct_create', 'nonce');
        $source_id = (int) $_POST['source_id'];
        $lang = sanitize_text_field($_POST['lang']);
        
        $new_id = GCT_Post_Translator::create_translation_draft($source_id, $lang);
        if ($new_id) {
            wp_send_json_success(['edit_url' => get_edit_post_link($new_id, 'raw')]);
        }
        wp_send_json_error();
    }

    public static function ajax_run_translation() {
        check_ajax_referer('gct_trans', 'nonce');
        $post_id = (int) $_POST['post_id'];
        $provider = get_option('gct_api_provider', 'deepl');
        
        if (GCT_Post_Translator::translate_post($post_id, $provider)) {
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    public static function enqueue_assets() {
        wp_enqueue_style('gct-admin-css', GCT_URL . 'assets/admin.css', [], GCT_VERSION);
        wp_enqueue_script('gct-admin-js', GCT_URL . 'assets/admin.js', [], GCT_VERSION, true);
    }
}