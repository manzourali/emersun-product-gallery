<?php

namespace EPG\Admin;

use EPG\Support\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

final class SettingsPage
{
    private string $page_slug = 'epg-settings';

    public function hooks(): void
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'register_settings']);

        add_action('admin_notices', [$this, 'maybe_show_dependency_notice']);
        add_filter('plugin_action_links_' . EPG_BASENAME, [$this, 'add_settings_link']);
    }

    public function add_settings_link(array $links): array
    {
        $url = admin_url('admin.php?page=' . $this->page_slug);
        $links[] = '<a href="' . esc_url($url) . '">' . esc_html__('Settings', EPG_TEXT_DOMAIN) . '</a>';
        return $links;
    }

    public function register_menu(): void
    {
        // Under WooCommerce menu
        add_submenu_page(
            'woocommerce',
            esc_html__('Emersun Gallery', EPG_TEXT_DOMAIN),
            esc_html__('Emersun Gallery', EPG_TEXT_DOMAIN),
            'manage_options',
            $this->page_slug,
            [$this, 'render_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            'epg_settings_group',
            Helpers::OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
                'default' => Helpers::default_settings(),
            ]
        );

        add_settings_section(
            'epg_section_general',
            esc_html__('General', EPG_TEXT_DOMAIN),
            function () {
                echo '<p>' . esc_html__('Basic plugin behavior and debugging.', EPG_TEXT_DOMAIN) . '</p>';
            },
            $this->page_slug
        );

        $this->add_field_checkbox('general.enabled', __('Enable gallery replacement', EPG_TEXT_DOMAIN), 'epg_section_general');
        $this->add_field_checkbox('general.enable_shortcode', __('Enable shortcode', EPG_TEXT_DOMAIN), 'epg_section_general');
        $this->add_field_checkbox('general.debug', __('Enable debug logging', EPG_TEXT_DOMAIN), 'epg_section_general');

        add_settings_field(
            'epg_custom_css',
            esc_html__('Custom CSS', EPG_TEXT_DOMAIN),
            [$this, 'render_custom_css_field'],
            $this->page_slug,
            'epg_section_general'
        );

        add_settings_section(
            'epg_section_layout',
            esc_html__('Layout', EPG_TEXT_DOMAIN),
            function () {
                echo '<p>' . esc_html__('Gallery layout and thumbnails.', EPG_TEXT_DOMAIN) . '</p>';
            },
            $this->page_slug
        );

        add_settings_field(
            'epg_layout',
            esc_html__('Gallery layout', EPG_TEXT_DOMAIN),
            [$this, 'render_layout_field'],
            $this->page_slug,
            'epg_section_layout'
        );

        $this->add_field_checkbox('layout.show_thumbs', __('Show thumbnails', EPG_TEXT_DOMAIN), 'epg_section_layout');

        $this->add_field_number('layout.space_between', __('Space between slides (px)', EPG_TEXT_DOMAIN), 'epg_section_layout', 0, 64);

        $this->add_field_number('layout.thumbs_per_view_desktop', __('Thumbs per view (desktop)', EPG_TEXT_DOMAIN), 'epg_section_layout', 1, 12);
        $this->add_field_number('layout.thumbs_per_view_tablet', __('Thumbs per view (tablet)', EPG_TEXT_DOMAIN), 'epg_section_layout', 1, 12);
        $this->add_field_number('layout.thumbs_per_view_mobile', __('Thumbs per view (mobile)', EPG_TEXT_DOMAIN), 'epg_section_layout', 1, 12);

        add_settings_field(
            'epg_height_strategy',
            esc_html__('Height strategy', EPG_TEXT_DOMAIN),
            [$this, 'render_height_strategy_field'],
            $this->page_slug,
            'epg_section_layout'
        );

        add_settings_section(
            'epg_section_controls',
            esc_html__('Controls', EPG_TEXT_DOMAIN),
            function () {
                echo '<p>' . esc_html__('Swiper controls and behavior.', EPG_TEXT_DOMAIN) . '</p>';
            },
            $this->page_slug
        );

        $this->add_field_checkbox('controls.arrows', __('Show navigation arrows', EPG_TEXT_DOMAIN), 'epg_section_controls');

        add_settings_field(
            'epg_pagination',
            esc_html__('Pagination type', EPG_TEXT_DOMAIN),
            [$this, 'render_pagination_field'],
            $this->page_slug,
            'epg_section_controls'
        );

        $this->add_field_checkbox('controls.keyboard', __('Enable keyboard control', EPG_TEXT_DOMAIN), 'epg_section_controls');
        $this->add_field_checkbox('controls.drag', __('Enable drag/swipe', EPG_TEXT_DOMAIN), 'epg_section_controls');
        $this->add_field_checkbox('controls.loop', __('Enable loop', EPG_TEXT_DOMAIN), 'epg_section_controls');

        $this->add_field_checkbox('controls.autoplay', __('Enable autoplay', EPG_TEXT_DOMAIN), 'epg_section_controls');
        $this->add_field_number('controls.autoplay_delay', __('Autoplay delay (ms)', EPG_TEXT_DOMAIN), 'epg_section_controls', 500, 30000);

        $this->add_field_checkbox('controls.lazyload', __('Enable lazy loading', EPG_TEXT_DOMAIN), 'epg_section_controls');

        add_settings_section(
            'epg_section_wc',
            esc_html__('WooCommerce Integration', EPG_TEXT_DOMAIN),
            function () {
                echo '<p>' . esc_html__('Integration with variations and badges.', EPG_TEXT_DOMAIN) . '</p>';
            },
            $this->page_slug
        );

        $this->add_field_checkbox('woocommerce.include_variation_images', __('Include variation images in gallery', EPG_TEXT_DOMAIN), 'epg_section_wc');
        $this->add_field_checkbox('woocommerce.sync_variation_image', __('Sync variation image selection', EPG_TEXT_DOMAIN), 'epg_section_wc');
        $this->add_field_checkbox('woocommerce.go_to_variation_image', __('Go to variation image on selection', EPG_TEXT_DOMAIN), 'epg_section_wc');
        $this->add_field_checkbox('woocommerce.reset_on_clear', __('Reset to main image on variation clear', EPG_TEXT_DOMAIN), 'epg_section_wc');

        $this->add_field_checkbox('woocommerce.show_sale_badge', __('Show sale badge', EPG_TEXT_DOMAIN), 'epg_section_wc');
        $this->add_field_checkbox('woocommerce.show_stock_badge', __('Show stock badge', EPG_TEXT_DOMAIN), 'epg_section_wc');

        add_settings_section(
            'epg_section_perf',
            esc_html__('Performance', EPG_TEXT_DOMAIN),
            function () {
                echo '<p>' . esc_html__('Conditional asset loading.', EPG_TEXT_DOMAIN) . '</p>';
            },
            $this->page_slug
        );

        $this->add_field_checkbox('performance.load_on_single_product', __('Load assets on single product pages', EPG_TEXT_DOMAIN), 'epg_section_perf');
        $this->add_field_checkbox('performance.load_on_shortcode', __('Load assets when shortcode renders', EPG_TEXT_DOMAIN), 'epg_section_perf');
    }

    public function maybe_show_dependency_notice(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!Helpers::is_elementor_active()) {
            echo '<div class="notice notice-error"><p>'
                . esc_html__('Emersun Product Gallery requires Elementor to be installed and active.', EPG_TEXT_DOMAIN)
                . '</p></div>';
        }

        if (!Helpers::is_woocommerce_active()) {
            echo '<div class="notice notice-warning"><p>'
                . esc_html__('Emersun Product Gallery works with WooCommerce. Please install/activate WooCommerce.', EPG_TEXT_DOMAIN)
                . '</p></div>';
        }
    }

    public function render_page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = Helpers::get_settings();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Emersun Product Gallery Settings', EPG_TEXT_DOMAIN) . '</h1>';

        echo '<form method="post" action="options.php">';
        settings_fields('epg_settings_group');
        do_settings_sections($this->page_slug);

        submit_button();

        echo '</form>';

        echo '<hr />';
        echo '<p><strong>' . esc_html__('Shortcode:', EPG_TEXT_DOMAIN) . '</strong> <code>[epg_gallery]</code></p>';

        echo '</div>';
    }

    public function sanitize_settings($input): array
    {
        $defaults = Helpers::default_settings();
        $input = is_array($input) ? $input : [];

        $out = $defaults;

        // English-only comment: Sanitize general settings
        $out['general']['enabled']          = Helpers::sanitize_bool($input['general']['enabled'] ?? $defaults['general']['enabled']);
        $out['general']['enable_shortcode'] = Helpers::sanitize_bool($input['general']['enable_shortcode'] ?? $defaults['general']['enable_shortcode']);
        $out['general']['debug']            = Helpers::sanitize_bool($input['general']['debug'] ?? $defaults['general']['debug']);

        $custom_css = $input['general']['custom_css'] ?? '';
        $custom_css = is_string($custom_css) ? $custom_css : '';
        $out['general']['custom_css'] = wp_kses($custom_css, []);

        // English-only comment: Layout sanitization
        $out['layout']['layout'] = Helpers::sanitize_enum(
            $input['layout']['layout'] ?? $defaults['layout']['layout'],
            ['thumbs-bottom', 'thumbs-left', 'thumbs-right', 'single'],
            $defaults['layout']['layout']
        );

        $out['layout']['show_thumbs'] = Helpers::sanitize_bool($input['layout']['show_thumbs'] ?? $defaults['layout']['show_thumbs']);

        $out['layout']['space_between'] = Helpers::sanitize_int(
            $input['layout']['space_between'] ?? $defaults['layout']['space_between'],
            0,
            64
        );

        $out['layout']['thumbs_per_view_desktop'] = Helpers::sanitize_int($input['layout']['thumbs_per_view_desktop'] ?? $defaults['layout']['thumbs_per_view_desktop'], 1, 12);
        $out['layout']['thumbs_per_view_tablet']  = Helpers::sanitize_int($input['layout']['thumbs_per_view_tablet'] ?? $defaults['layout']['thumbs_per_view_tablet'], 1, 12);
        $out['layout']['thumbs_per_view_mobile']  = Helpers::sanitize_int($input['layout']['thumbs_per_view_mobile'] ?? $defaults['layout']['thumbs_per_view_mobile'], 1, 12);

        $out['layout']['height_strategy'] = Helpers::sanitize_enum(
            $input['layout']['height_strategy'] ?? $defaults['layout']['height_strategy'],
            ['adaptive', 'fixed'],
            $defaults['layout']['height_strategy']
        );

        $ratio = $input['layout']['fixed_ratio'] ?? $defaults['layout']['fixed_ratio'];
        $ratio = is_string($ratio) ? trim($ratio) : $defaults['layout']['fixed_ratio'];
        if (!preg_match('/^\d+(\.\d+)?:\d+(\.\d+)?$/', $ratio)) {
            $ratio = $defaults['layout']['fixed_ratio'];
        }
        $out['layout']['fixed_ratio'] = $ratio;

        $out['layout']['object_fit'] = Helpers::sanitize_enum(
            $input['layout']['object_fit'] ?? $defaults['layout']['object_fit'],
            ['contain', 'cover'],
            $defaults['layout']['object_fit']
        );

        // English-only comment: Derive thumb position from layout
        $out['layout']['thumb_position'] = 'bottom';
        if ($out['layout']['layout'] === 'thumbs-left') {
            $out['layout']['thumb_position'] = 'left';
        } elseif ($out['layout']['layout'] === 'thumbs-right') {
            $out['layout']['thumb_position'] = 'right';
        }

        // English-only comment: Controls sanitization
        $out['controls']['arrows'] = Helpers::sanitize_bool($input['controls']['arrows'] ?? $defaults['controls']['arrows']);

        $out['controls']['pagination'] = Helpers::sanitize_enum(
            $input['controls']['pagination'] ?? $defaults['controls']['pagination'],
            ['none', 'bullets', 'fraction', 'progressbar'],
            $defaults['controls']['pagination']
        );

        $out['controls']['keyboard'] = Helpers::sanitize_bool($input['controls']['keyboard'] ?? $defaults['controls']['keyboard']);
        $out['controls']['drag']     = Helpers::sanitize_bool($input['controls']['drag'] ?? $defaults['controls']['drag']);
        $out['controls']['loop']     = Helpers::sanitize_bool($input['controls']['loop'] ?? $defaults['controls']['loop']);

        $out['controls']['autoplay'] = Helpers::sanitize_bool($input['controls']['autoplay'] ?? $defaults['controls']['autoplay']);

        $out['controls']['autoplay_delay'] = Helpers::sanitize_int(
            $input['controls']['autoplay_delay'] ?? $defaults['controls']['autoplay_delay'],
            500,
            30000
        );

        $out['controls']['lazyload'] = Helpers::sanitize_bool($input['controls']['lazyload'] ?? $defaults['controls']['lazyload']);

        // English-only comment: WooCommerce integration
        $out['woocommerce']['include_variation_images'] = Helpers::sanitize_bool($input['woocommerce']['include_variation_images'] ?? $defaults['woocommerce']['include_variation_images']);
        $out['woocommerce']['sync_variation_image']     = Helpers::sanitize_bool($input['woocommerce']['sync_variation_image'] ?? $defaults['woocommerce']['sync_variation_image']);
        $out['woocommerce']['go_to_variation_image']    = Helpers::sanitize_bool($input['woocommerce']['go_to_variation_image'] ?? $defaults['woocommerce']['go_to_variation_image']);
        $out['woocommerce']['reset_on_clear']           = Helpers::sanitize_bool($input['woocommerce']['reset_on_clear'] ?? $defaults['woocommerce']['reset_on_clear']);

        $out['woocommerce']['show_sale_badge']  = Helpers::sanitize_bool($input['woocommerce']['show_sale_badge'] ?? $defaults['woocommerce']['show_sale_badge']);
        $out['woocommerce']['show_stock_badge'] = Helpers::sanitize_bool($input['woocommerce']['show_stock_badge'] ?? $defaults['woocommerce']['show_stock_badge']);

        // English-only comment: Performance
        $out['performance']['load_on_single_product'] = Helpers::sanitize_bool($input['performance']['load_on_single_product'] ?? $defaults['performance']['load_on_single_product']);
        $out['performance']['load_on_shortcode']      = Helpers::sanitize_bool($input['performance']['load_on_shortcode'] ?? $defaults['performance']['load_on_shortcode']);

        return $out;
    }

    private function add_field_checkbox(string $path, string $label, string $section_id): void
    {
        $field_id = 'epg_' . str_replace(['.', '-'], '_', $path);

        add_settings_field(
            $field_id,
            esc_html($label),
            function () use ($path) {
                $settings = Helpers::get_settings();
                $value = $this->get_value_by_path($settings, $path);

                echo '<label>';
                echo '<input type="checkbox" name="' . esc_attr(Helpers::OPTION_KEY) . '[' . esc_attr($this->path_to_input_name($path)) . ']" value="1" ' . checked((bool) $value, true, false) . ' />';
                echo ' ' . esc_html__('Enabled', EPG_TEXT_DOMAIN);
                echo '</label>';
            },
            $this->page_slug,
            $section_id
        );
    }

    private function add_field_number(string $path, string $label, string $section_id, int $min, int $max): void
    {
        $field_id = 'epg_' . str_replace(['.', '-'], '_', $path);

        add_settings_field(
            $field_id,
            esc_html($label),
            function () use ($path, $min, $max) {
                $settings = Helpers::get_settings();
                $value = $this->get_value_by_path($settings, $path);

                echo '<input type="number" min="' . esc_attr((string) $min) . '" max="' . esc_attr((string) $max) . '"'
                    . ' name="' . esc_attr(Helpers::OPTION_KEY) . '[' . esc_attr($this->path_to_input_name($path)) . ']"'
                    . ' value="' . esc_attr((string) $value) . '"'
                    . ' class="small-text" />';
            },
            $this->page_slug,
            $section_id
        );
    }

    public function render_layout_field(): void
    {
        $val = (string) Helpers::get_setting('layout.layout', 'thumbs-bottom');

        $options = [
            'thumbs-bottom' => __('Thumbnails Bottom', EPG_TEXT_DOMAIN),
            'thumbs-left'   => __('Thumbnails Left', EPG_TEXT_DOMAIN),
            'thumbs-right'  => __('Thumbnails Right', EPG_TEXT_DOMAIN),
            'single'        => __('Main Slider Only', EPG_TEXT_DOMAIN),
        ];

        echo '<select name="' . esc_attr(Helpers::OPTION_KEY) . '[layout][layout]">';
        foreach ($options as $k => $label) {
            echo '<option value="' . esc_attr($k) . '" ' . selected($val, $k, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    public function render_pagination_field(): void
    {
        $val = (string) Helpers::get_setting('controls.pagination', 'bullets');

        $options = [
            'none'        => __('None', EPG_TEXT_DOMAIN),
            'bullets'     => __('Bullets', EPG_TEXT_DOMAIN),
            'fraction'    => __('Fraction', EPG_TEXT_DOMAIN),
            'progressbar' => __('Progressbar', EPG_TEXT_DOMAIN),
        ];

        echo '<select name="' . esc_attr(Helpers::OPTION_KEY) . '[controls][pagination]">';
        foreach ($options as $k => $label) {
            echo '<option value="' . esc_attr($k) . '" ' . selected($val, $k, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    public function render_height_strategy_field(): void
    {
        $strategy = (string) Helpers::get_setting('layout.height_strategy', 'adaptive');
        $ratio    = (string) Helpers::get_setting('layout.fixed_ratio', '1:1');
        $fit      = (string) Helpers::get_setting('layout.object_fit', 'contain');

        echo '<fieldset>';

        echo '<label style="display:block;margin-bottom:6px;">';
        echo '<select name="' . esc_attr(Helpers::OPTION_KEY) . '[layout][height_strategy]">';
        echo '<option value="adaptive" ' . selected($strategy, 'adaptive', false) . '>' . esc_html__('Adaptive height', EPG_TEXT_DOMAIN) . '</option>';
        echo '<option value="fixed" ' . selected($strategy, 'fixed', false) . '>' . esc_html__('Fixed aspect ratio', EPG_TEXT_DOMAIN) . '</option>';
        echo '</select>';
        echo '</label>';

        echo '<div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">';

        echo '<label>';
        echo esc_html__('Ratio (w:h)', EPG_TEXT_DOMAIN) . ' ';
        echo '<input type="text" name="' . esc_attr(Helpers::OPTION_KEY) . '[layout][fixed_ratio]" value="' . esc_attr($ratio) . '" placeholder="1:1" />';
        echo '</label>';

        echo '<label>';
        echo esc_html__('Object fit', EPG_TEXT_DOMAIN) . ' ';
        echo '<select name="' . esc_attr(Helpers::OPTION_KEY) . '[layout][object_fit]">';
        echo '<option value="contain" ' . selected($fit, 'contain', false) . '>' . esc_html__('Contain', EPG_TEXT_DOMAIN) . '</option>';
        echo '<option value="cover" ' . selected($fit, 'cover', false) . '>' . esc_html__('Cover', EPG_TEXT_DOMAIN) . '</option>';
        echo '</select>';
        echo '</label>';

        echo '</div>';

        echo '<p class="description">' . esc_html__('Fixed ratio uses a CSS aspect-ratio box; object-fit controls image scaling.', EPG_TEXT_DOMAIN) . '</p>';

        echo '</fieldset>';
    }

    public function render_custom_css_field(): void
    {
        $css = (string) Helpers::get_setting('general.custom_css', '');

        echo '<textarea name="' . esc_attr(Helpers::OPTION_KEY) . '[general][custom_css]" rows="8" cols="60" class="large-text code">'
            . esc_textarea($css)
            . '</textarea>';

        echo '<p class="description">' . esc_html__('Custom CSS will be printed on the frontend when the gallery loads.', EPG_TEXT_DOMAIN) . '</p>';
    }

    private function path_to_input_name(string $path): string
    {
        // English-only comment: Converts dot path to nested array syntax segments
        $segments = explode('.', $path);
        $first = array_shift($segments);
        if ($first === null) {
            return '';
        }

        $name = $first;
        foreach ($segments as $seg) {
            $name .= '][' . $seg;
        }

        return $name . ']';
    }

    private function get_value_by_path(array $arr, string $path)
    {
        $segments = array_filter(explode('.', $path), 'strlen');

        $v = $arr;
        foreach ($segments as $seg) {
            if (!is_array($v) || !array_key_exists($seg, $v)) {
                return null;
            }
            $v = $v[$seg];
        }

        return $v;
    }
}
