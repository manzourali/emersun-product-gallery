<?php
/**
 * Plugin Name: Emersun Product Gallery
 * Description: Replaces WooCommerce single product gallery with an Elementor Swiper-based gallery. Provides shortcode and admin settings.
 * Version: 1.0.0
 * Author: Emersun
 * Text Domain: emersun-product-gallery
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * WC requires at least: 7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('EPG_VERSION', '1.0.0');
define('EPG_FILE', __FILE__);
define('EPG_DIR', plugin_dir_path(__FILE__));
define('EPG_URL', plugin_dir_url(__FILE__));
define('EPG_BASENAME', plugin_basename(__FILE__));
define('EPG_TEXT_DOMAIN', 'emersun-product-gallery');

require_once EPG_DIR . 'includes/Plugin.php';

function epg(): \EPG\Plugin
{
    return \EPG\Plugin::instance();
}

add_action('plugins_loaded', static function () {
    epg()->boot();
});
