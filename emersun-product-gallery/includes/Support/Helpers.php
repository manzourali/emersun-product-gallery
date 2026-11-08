<?php

namespace EPG\Support;

use WC_Product;

if (!defined('ABSPATH')) {
    exit;
}

final class Helpers
{
    public const OPTION_KEY = 'epg_settings';

    public static function is_woocommerce_active(): bool
    {
        return class_exists('\WooCommerce');
    }

    public static function is_elementor_active(): bool
    {
        return did_action('elementor/loaded') || defined('ELEMENTOR_VERSION');
    }

    public static function is_single_product_page(): bool
    {
        return function_exists('is_product') && is_product();
    }

    public static function get_settings(): array
    {
        $defaults = self::default_settings();
        $saved = get_option(self::OPTION_KEY, []);

        if (!is_array($saved)) {
            $saved = [];
        }

        return array_replace_recursive($defaults, $saved);
    }

    public static function default_settings(): array
    {
        return [
            'general' => [
                'enabled' => true,
                'enable_shortcode' => true,
                'debug' => false,
                'custom_css' => '',
            ],
            'layout' => [
                'layout' => 'thumbs-bottom', // thumbs-bottom|thumbs-left|thumbs-right|single
                'show_thumbs' => true,
                'thumb_position' => 'bottom', // bottom|left|right (derived but kept for clarity)
                'space_between' => 8,

                'thumbs_per_view_desktop' => 6,
                'thumbs_per_view_tablet'  => 5,
                'thumbs_per_view_mobile'  => 4,

                'height_strategy' => 'fixed', // adaptive|fixed
                'fixed_ratio' => '1:1', // e.g. 1:1, 4:3, 3:4, 16:9
                'object_fit' => 'contain', // contain|cover
            ],
            'controls' => [
                'arrows' => true,
                'pagination' => 'bullets', // none|bullets|fraction|progressbar
                'keyboard' => true,
                'drag' => true,
                'loop' => false,
                'autoplay' => false,
                'autoplay_delay' => 3500,
                'lazyload' => true,
            ],
            'woocommerce' => [
                'include_variation_images' => true,
                'sync_variation_image' => true,
                'go_to_variation_image' => true,
                'reset_on_clear' => true,
                'show_sale_badge' => true,
                'show_stock_badge' => true,
            ],
            'performance' => [
                'load_on_single_product' => true,
                'load_on_shortcode' => true,
            ],
        ];
    }

    public static function get_setting(string $path, $default = null)
    {
        $settings = self::get_settings();
        $segments = array_filter(explode('.', $path), 'strlen');

        $value = $settings;
        foreach ($segments as $seg) {
            if (!is_array($value) || !array_key_exists($seg, $value)) {
                return $default;
            }
            $value = $value[$seg];
        }

        return $value;
    }

    public static function is_enabled(): bool
    {
        return (bool) self::get_setting('general.enabled', true);
    }

    public static function is_shortcode_enabled(): bool
    {
        return (bool) self::get_setting('general.enable_shortcode', true);
    }

    public static function is_debug(): bool
    {
        return (bool) self::get_setting('general.debug', false);
    }

    public static function log(string $message, array $context = []): void
    {
        if (!self::is_debug()) {
            return;
        }

        $line = '[EPG] ' . $message;

        if (!empty($context)) {
            $line .= ' ' . wp_json_encode($context);
        }

        // English-only log line for easier debugging
        error_log($line);
    }

    public static function get_product(?int $product_id = null): ?WC_Product
    {
        if (!self::is_woocommerce_active()) {
            return null;
        }

        if ($product_id) {
            $product = wc_get_product($product_id);
            return ($product instanceof WC_Product) ? $product : null;
        }

        global $product;
        if ($product instanceof WC_Product) {
            return $product;
        }

        $queried_id = get_queried_object_id();
        if ($queried_id) {
            $p = wc_get_product($queried_id);
            return ($p instanceof WC_Product) ? $p : null;
        }

        return null;
    }

    public static function is_rtl(): bool
    {
        return is_rtl();
    }

    public static function sanitize_bool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function sanitize_int($value, int $min = 0, int $max = PHP_INT_MAX): int
    {
        $v = (int) $value;
        if ($v < $min) {
            return $min;
        }
        if ($v > $max) {
            return $max;
        }
        return $v;
    }

    public static function sanitize_enum($value, array $allowed, string $fallback): string
    {
        $v = is_string($value) ? $value : '';
        return in_array($v, $allowed, true) ? $v : $fallback;
    }

    public static function ratio_to_padding_percent(string $ratio): float
    {
        // English-only comment: Converts "w:h" to (h/w)*100 for CSS padding-top hack
        $parts = array_map('trim', explode(':', $ratio));
        if (count($parts) !== 2) {
            return 100.0;
        }

        $w = (float) $parts[0];
        $h = (float) $parts[1];

        if ($w <= 0 || $h <= 0) {
            return 100.0;
        }

        return ($h / $w) * 100.0;
    }
}
