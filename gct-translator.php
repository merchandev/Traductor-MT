<?php
/**
 * Plugin Name: GCT Translator
 * Plugin URI:  https://github.com/merchandev/gct-translator
 * Description: Sistema de traducción SEO para WordPress. Crea páginas traducidas reales con slugs propios, hreflang correcto y selector rastreable.
 * Version:     5.0.0
 * Author:      Arturo Merchan
 * Text Domain: gct-translator
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GCT_VERSION', '5.0.0' );
define( 'GCT_DIR', plugin_dir_path( __FILE__ ) );
define( 'GCT_URL', plugin_dir_url( __FILE__ ) );

// Load all classes
require_once GCT_DIR . 'includes/class-gct-languages.php';
require_once GCT_DIR . 'includes/class-gct-post-relations.php';
require_once GCT_DIR . 'includes/class-gct-post-translator.php';
require_once GCT_DIR . 'includes/class-gct-router.php';
require_once GCT_DIR . 'includes/class-gct-switcher.php';
require_once GCT_DIR . 'includes/class-gct-seo.php';
require_once GCT_DIR . 'includes/class-gct-admin.php';
require_once GCT_DIR . 'includes/providers/interface-gct-provider.php';
require_once GCT_DIR . 'includes/providers/class-gct-deepl-provider.php';
require_once GCT_DIR . 'includes/providers/class-gct-google-provider.php';

add_action( 'plugins_loaded', function() {
    GCT_Languages::init();
    GCT_Router::init();
    GCT_SEO::init();
    GCT_Admin::init();
} );

// Public function used by theme header.php
function gct_render_language_switcher() {
    GCT_Switcher::render();
}

register_activation_hook( __FILE__, [ 'GCT_Router', 'flush' ] );
register_deactivation_hook( __FILE__, [ 'GCT_Router', 'flush' ] );