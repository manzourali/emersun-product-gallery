<?php

namespace EPG\Shortcodes;

use EPG\Frontend\Gallery;
use EPG\Support\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

final class GalleryShortcode
{
    public function hooks(): void
    {
        add_shortcode('epg_gallery', [$this, 'render']);
    }

    public function render($atts): string
    {
        if (!Helpers::is_shortcode_enabled()) {
            Helpers::log('Shortcode execution skipped: Shortcodes are disabled in settings.');
            return '';
        }

        if (!Helpers::is_elementor_active()) {
            Helpers::log('Shortcode execution skipped: Elementor dependency is missing.');
            return '<p class="epg-error">' . esc_html__('Emersun Gallery requires Elementor.', EPG_TEXT_DOMAIN) . '</p>';
        }

        $parsed = shortcode_atts([
            'product_id' => '',
            'layout'     => '',
            'thumbs'     => '',
            'autoplay'   => '',
            'loop'       => '',
            'pagination' => '',
            'arrows'     => '',
            'class'      => '',
        ], $atts, 'epg_gallery');

        $product_id = !empty($parsed['product_id']) ? absint($parsed['product_id']) : null;
        $product    = Helpers::get_product($product_id);

        if (!$product) {
            return '<p class="epg-error">' . esc_html__('Product not found for gallery.', EPG_TEXT_DOMAIN) . '</p>';
        }

        $custom_options = [];

        if (!empty($parsed['layout']) && in_array($parsed['layout'], ['thumbs-bottom', 'thumbs-left', 'thumbs-right', 'single'], true)) {
            $custom_options['layout']['layout'] = sanitize_text_field($parsed['layout']);
        }

        if ($parsed['thumbs'] !== '') {
            $custom_options['layout']['show_thumbs'] = Helpers::sanitize_bool($parsed['thumbs']);
        }

        if ($parsed['autoplay'] !== '') {
            $custom_options['controls']['autoplay'] = Helpers::sanitize_bool($parsed['autoplay']);
        }

        if ($parsed['loop'] !== '') {
            $custom_options['controls']['loop'] = Helpers::sanitize_bool($parsed['loop']);
        }

        if (!empty($parsed['pagination']) && in_array($parsed['pagination'], ['none', 'bullets', 'fraction', 'progressbar'], true)) {
            $custom_options['controls']['pagination'] = sanitize_text_field($parsed['pagination']);
        }

        if ($parsed['arrows'] !== '') {
            $custom_options['controls']['arrows'] = Helpers::sanitize_bool($parsed['arrows']);
        }

        if (!empty($parsed['class'])) {
            $custom_options['extra_class'] = sanitize_text_field($parsed['class']);
        }

        $gallery = new Gallery($product, $custom_options);
        return $gallery->render();
    }
}
