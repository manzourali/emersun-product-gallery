<?php

namespace EPG\Integration;

use EPG\Frontend\Gallery;
use EPG\Support\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

final class WooCommerceHooks
{
    public function hooks(): void
    {
        add_action('wp', [$this, 'manage_gallery_hooks']);
        
        // Hook into Elementor widget rendering to replace Elementor's Product Images widget content
        add_filter('elementor/widget/render_content', [$this, 'replace_elementor_product_images_widget'], 10, 2);
    }

    public function manage_gallery_hooks(): void
    {
        if (!Helpers::is_woocommerce_active()) {
            return;
        }

        if (!Helpers::is_enabled()) {
            return;
        }

        // Elementor is mandatory for Swiper execution
        if (!Helpers::is_elementor_active()) {
            return;
        }

        if (Helpers::is_single_product_page()) {
            // Unhook default WooCommerce single product gallery
            remove_action(
                'woocommerce_before_single_product_summary',
                'woocommerce_show_product_images',
                20
            );

            // Hook Emersun Product Gallery in identical position for standard templates
            add_action(
                'woocommerce_before_single_product_summary',
                [$this, 'render_single_product_gallery'],
                20
            );
        }
    }

    public function render_single_product_gallery(): void
    {
        $product = Helpers::get_product();
        if (!$product) {
            return;
        }

        $gallery = new Gallery($product);
        echo $gallery->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Replaces Elementor's Product Images widget HTML with EPG gallery
     * 
     * @param string $widget_content
     * @param \Elementor\Widget_Base $widget
     * @return string
     */
    public function replace_elementor_product_images_widget(string $widget_content, $widget): string
    {
        if (!Helpers::is_enabled()) {
            return $widget_content;
        }

        // Check if current widget is WooCommerce Product Images
        $widget_name = $widget->get_name();
        if (in_array($widget_name, ['woocommerce-product-images', 'woocommerce-product-gallery'], true)) {
            $product = Helpers::get_product();
            if ($product) {
                $gallery = new Gallery($product);
                return $gallery->render();
            }
        }

        return $widget_content;
    }
}
