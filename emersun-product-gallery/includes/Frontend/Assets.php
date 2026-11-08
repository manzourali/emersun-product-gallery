<?php

namespace EPG\Frontend;

use EPG\Support\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

final class Assets
{
    private bool $assets_enqueued = false;

    public function hooks(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'register_assets']);
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_on_single_product'], 20);
    }

    public function register_assets(): void
    {
        // Register gallery CSS
        wp_register_style(
            'epg-gallery',
            EPG_URL . 'assets/css/gallery.css',
            [],
            EPG_VERSION
        );

        // Detect Elementor's registered Swiper handle or fallback to registered dependencies
        $swiper_deps = $this->resolve_elementor_swiper_dependencies();

        // Register gallery JS
        wp_register_script(
            'epg-gallery',
            EPG_URL . 'assets/js/gallery.js',
            $swiper_deps,
            EPG_VERSION,
            true
        );

        // Localize config for frontend usage
        wp_localize_script('epg-gallery', 'epgData', [
            'is_rtl'     => Helpers::is_rtl(),
            'debug'      => Helpers::is_debug(),
            'i18n'       => [
                'next'  => __('Next slide', EPG_TEXT_DOMAIN),
                'prev'  => __('Previous slide', EPG_TEXT_DOMAIN),
                'slide' => __('Slide', EPG_TEXT_DOMAIN),
            ],
            'registered' => [
                'elementor_active' => Helpers::is_elementor_active(),
            ],
        ]);
    }

    public function maybe_enqueue_on_single_product(): void
    {
        if (!Helpers::is_enabled()) {
            return;
        }

        if (!Helpers::is_elementor_active()) {
            return;
        }

        $load_on_single = (bool) Helpers::get_setting('performance.load_on_single_product', true);

        if ($load_on_single && Helpers::is_single_product_page()) {
            $this->enqueue();
        }
    }

    public function enqueue(): void
    {
        if ($this->assets_enqueued) {
            return;
        }

        // Elementor is a mandatory dependency
        if (!Helpers::is_elementor_active()) {
            Helpers::log('Assets enqueue skipped: Elementor is not active.');
            return;
        }

        // Ensure Elementor core frontend Swiper styles and scripts are pulled in
        $this->ensure_elementor_swiper_enqueued();

        wp_enqueue_style('epg-gallery');
        wp_enqueue_script('epg-gallery');

        // Append custom CSS from admin settings if provided
        $custom_css = (string) Helpers::get_setting('general.custom_css', '');
        if (!empty($custom_css)) {
            wp_add_inline_style('epg-gallery', $custom_css);
        }

        $this->assets_enqueued = true;
    }

    private function resolve_elementor_swiper_dependencies(): array
    {
        $deps = ['jquery'];

        // Elementor registers 'swiper' script handle in modern versions, or bundles it with elementor-frontend
        if (wp_script_is('swiper', 'registered')) {
            $deps[] = 'swiper';
        } elseif (wp_script_is('elementor-frontend', 'registered')) {
            $deps[] = 'elementor-frontend';
        }

        return $deps;
    }

    private function ensure_elementor_swiper_enqueued(): void
    {
        // Enqueue Elementor's bundled Swiper style if registered
        if (wp_style_is('swiper', 'registered')) {
            wp_enqueue_style('swiper');
        } elseif (wp_style_is('elementor-frontend', 'registered')) {
            wp_enqueue_style('elementor-frontend');
        }

        // Enqueue Swiper script
        if (wp_script_is('swiper', 'registered')) {
            wp_enqueue_script('swiper');
        } elseif (wp_script_is('elementor-frontend', 'registered')) {
            wp_enqueue_script('elementor-frontend');
        }
    }
}
