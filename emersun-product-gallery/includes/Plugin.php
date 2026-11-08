<?php

namespace EPG;

use EPG\Admin\SettingsPage;
use EPG\Frontend\Assets;
use EPG\Integration\WooCommerceHooks;
use EPG\Shortcodes\GalleryShortcode;

if (!defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    private static ?Plugin $instance = null;

    public SettingsPage $settings_page;
    public Assets $assets;
    public WooCommerceHooks $wc_hooks;
    public GalleryShortcode $shortcode;

    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        // Intentionally empty (singleton)
    }

    public function boot(): void
    {
        $this->includes();

        add_action('init', [$this, 'load_textdomain'], 5);

        $this->settings_page = new SettingsPage();
        $this->assets        = new Assets();
        $this->wc_hooks      = new WooCommerceHooks();
        $this->shortcode     = new GalleryShortcode();

        $this->settings_page->hooks();
        $this->assets->hooks();
        $this->wc_hooks->hooks();
        $this->shortcode->hooks();
    }

    private function includes(): void
    {
        require_once EPG_DIR . 'includes/Support/Helpers.php';

        require_once EPG_DIR . 'includes/Admin/SettingsPage.php';

        require_once EPG_DIR . 'includes/Frontend/Assets.php';
        require_once EPG_DIR . 'includes/Frontend/Gallery.php';

        require_once EPG_DIR . 'includes/Integration/WooCommerceHooks.php';

        require_once EPG_DIR . 'includes/Shortcodes/Gallery.php';
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            EPG_TEXT_DOMAIN,
            false,
            dirname(EPG_BASENAME) . '/languages'
        );
    }
}
