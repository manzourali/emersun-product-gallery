<?php

namespace EPG\Frontend;

use EPG\Support\Helpers;
use WC_Product;
use WC_Product_Variable;

if (!defined('ABSPATH')) {
    exit;
}

final class Gallery
{
    private ?WC_Product $product;
    private array $options;

    public function __construct(?WC_Product $product = null, array $custom_options = [])
    {
        $this->product = $product ?: Helpers::get_product();
        $this->options = $this->merge_options($custom_options);
    }

    public function render(): string
    {
        if (!$this->product instanceof WC_Product) {
            return '';
        }

        // Ensure frontend assets are enqueued (handles shortcode execution anywhere)
        epg()->assets->enqueue();

        $images = $this->collect_images();

        if (empty($images)) {
            return '<div class="epg-gallery-empty">' . esc_html__('No images available.', EPG_TEXT_DOMAIN) . '</div>';
        }

        $layout_class    = 'epg-layout-' . sanitize_html_class($this->options['layout']['layout']);
        $ratio_strategy  = sanitize_html_class($this->options['layout']['height_strategy']);
        $custom_class    = !empty($this->options['extra_class']) ? sanitize_html_class($this->options['extra_class']) : '';
        $aspect_padding  = Helpers::ratio_to_padding_percent((string) $this->options['layout']['fixed_ratio']);
        $fit_mode        = sanitize_html_class($this->options['layout']['object_fit']);

        $wrapper_classes = [
            'epg-gallery-wrapper',
            $layout_class,
            'epg-strategy-' . $ratio_strategy,
            'epg-fit-' . $fit_mode,
            $custom_class,
        ];

        $client_config = $this->prepare_client_config();
        $config_attr   = esc_attr(wp_json_encode($client_config));

        ob_start();
?>
        <div class="<?php echo esc_attr(implode(' ', array_filter($wrapper_classes))); ?>"
            data-epg-config="<?php echo $config_attr; ?>"
            data-product-id="<?php echo esc_attr((string) $this->product->get_id()); ?>">

            <?php $this->render_badges(); ?>

            <div class="epg-main-slider-container">
                <div class="swiper epg-main-swiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($images as $index => $img) : ?>
                            <div class="swiper-slide epg-main-slide"
                                data-slide-index="<?php echo esc_attr((string) $index); ?>"
                                data-image-id="<?php echo esc_attr((string) $img['id']); ?>"
                                data-variation-ids="<?php echo esc_attr(implode(',', $img['variation_ids'])); ?>">
                                <div class="epg-image-inner" <?php echo ($ratio_strategy === 'fixed') ? 'style="--epg-ratio-padding: ' . esc_attr((string) $aspect_padding) . '%;"' : ''; ?>>
                                    <?php echo $img['main_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($this->options['controls']['arrows'])) : ?>
                        <div class="swiper-button-prev epg-button-prev" aria-label="<?php esc_attr_e('Previous slide', EPG_TEXT_DOMAIN); ?>"></div>
                        <div class="swiper-button-next epg-button-next" aria-label="<?php esc_attr_e('Next slide', EPG_TEXT_DOMAIN); ?>"></div>
                    <?php endif; ?>

                    <?php if ($this->options['controls']['pagination'] !== 'none') : ?>
                        <div class="swiper-pagination epg-pagination"></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($this->options['layout']['show_thumbs']) && $this->options['layout']['layout'] !== 'single' && count($images) > 1) : ?>
                <div class="epg-thumbs-slider-container">
                    <div class="swiper epg-thumbs-swiper">
                        <div class="swiper-wrapper">
                            <?php foreach ($images as $index => $img) : ?>
                                <div class="swiper-slide epg-thumb-slide"
                                    data-image-id="<?php echo esc_attr((string) $img['id']); ?>"
                                    data-variation-ids="<?php echo esc_attr(implode(',', $img['variation_ids'])); ?>">
                                    <div class="epg-thumb-inner">
                                        <?php echo $img['thumb_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
                                        ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
<?php
        return (string) ob_get_clean();
    }

    private function collect_images(): array
    {
        $collected = [];
        $seen_ids  = [];

        // 1. Featured image
        $featured_id = $this->product->get_image_id();
        if ($featured_id) {
            $seen_ids[] = (int) $featured_id;
            $collected[(int) $featured_id] = [
                'id'            => (int) $featured_id,
                'variation_ids' => [],
                'is_featured'   => true,
            ];
        }

        // 2. Product gallery images
        $gallery_ids = $this->product->get_gallery_image_ids();
        if (is_array($gallery_ids)) {
            foreach ($gallery_ids as $gid) {
                $gid = (int) $gid;
                if (!in_array($gid, $seen_ids, true)) {
                    $seen_ids[] = $gid;
                    $collected[$gid] = [
                        'id'            => $gid,
                        'variation_ids' => [],
                        'is_featured'   => false,
                    ];
                }
            }
        }

        // 3. Variable product variation images
        $include_variations = (bool) ($this->options['woocommerce']['include_variation_images'] ?? true);
        if ($include_variations && $this->product instanceof WC_Product_Variable) {
            $variation_children = $this->product->get_children();
            if (is_array($variation_children)) {
                foreach ($variation_children as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    if (!$variation) {
                        continue;
                    }

                    $var_img_id = (int) $variation->get_image_id();
                    if ($var_img_id > 0) {
                        if (!isset($collected[$var_img_id])) {
                            $collected[$var_img_id] = [
                                'id'            => $var_img_id,
                                'variation_ids' => [$variation_id],
                                'is_featured'   => false,
                            ];
                            $seen_ids[] = $var_img_id;
                        } else {
                            $collected[$var_img_id]['variation_ids'][] = $variation_id;
                        }
                    }
                }
            }
        }

        // Fallback placeholder if no image exists
        if (empty($collected)) {
            $placeholder_url = wc_placeholder_img_src('woocommerce_single');
            return [
                [
                    'id'            => 0,
                    'variation_ids' => [],
                    'is_featured'   => true,
                    'main_html'     => '<img src="' . esc_url($placeholder_url) . '" alt="' . esc_attr__('Placeholder', EPG_TEXT_DOMAIN) . '" class="epg-placeholder" />',
                    'thumb_html'    => '<img src="' . esc_url($placeholder_url) . '" alt="' . esc_attr__('Placeholder', EPG_TEXT_DOMAIN) . '" class="epg-placeholder" />',
                ],
            ];
        }

        // Generate WordPress responsive img tags
        $result = [];
        $is_first = true; // flag to detect the very first image for LCP optimization

        foreach ($collected as $item) {
            $id = $item['id'];

            // LCP Optimization for the first image
            $loading_attr  = $is_first ? 'eager' : ($this->options['controls']['lazyload'] ? 'lazy' : 'eager');
            $custom_attrs  = [
                'class'   => 'epg-main-img wp-post-image',
                'loading' => $loading_attr,
            ];

            // Add fetchpriority high for the LCP hero image
            if ($is_first) {
                $custom_attrs['fetchpriority'] = 'high';
            }

            $main_img  = wp_get_attachment_image($id, 'woocommerce_single', false, $custom_attrs);

            $thumb_img = wp_get_attachment_image($id, 'woocommerce_thumbnail', false, [
                'class'   => 'epg-thumb-img',
                'loading' => 'lazy', // Thumbnails are never LCP candidates, always lazy load them
            ]);

            if (empty($main_img)) {
                $is_first = false; // even if empty, shift the flag
                continue;
            }

            $item['main_html']  = $main_img;
            $item['thumb_html'] = !empty($thumb_img) ? $thumb_img : $main_img;
            $result[] = $item;

            $is_first = false; // First iteration is done
        }

        return $result;
    }

    private function render_badges(): void
    {
        $show_sale  = (bool) ($this->options['woocommerce']['show_sale_badge'] ?? true);
        $show_stock = (bool) ($this->options['woocommerce']['show_stock_badge'] ?? false);

        if (!$show_sale && !$show_stock) {
            return;
        }

        echo '<div class="epg-badges-wrap">';

        if ($show_sale && $this->product->is_on_sale()) {
            $discount = $this->get_discount_percentage();
            if ($discount > 0) {
                echo '<span class="epg-badge epg-badge-sale epg-badge-discount" data-default-discount="' . esc_attr((string) $discount) . '">' . esc_html(sprintf('%d%%', $discount)) . '</span>';
            }
        }

        if ($show_stock && !$this->product->is_in_stock()) {
            echo '<span class="epg-badge epg-badge-out-of-stock">' . esc_html__('Out of stock', EPG_TEXT_DOMAIN) . '</span>';
        }

        echo '</div>';
    }

    private function get_discount_percentage(): int
    {
        if ($this->product instanceof WC_Product_Variable) {
            $prices = $this->product->get_variation_prices(true);
            $regular_prices = $prices['regular_price'] ?? [];
            $sale_prices    = $prices['sale_price'] ?? [];
            $max_discount   = 0;

            foreach ($sale_prices as $variation_id => $sale_price) {
                $regular_price = (float) ($regular_prices[$variation_id] ?? 0);
                $sale_price    = (float) $sale_price;

                if ($regular_price > 0 && $sale_price >= 0 && $sale_price < $regular_price) {
                    $max_discount = max($max_discount, (int) round((($regular_price - $sale_price) / $regular_price) * 100));
                }
            }

            return $max_discount;
        }

        $regular_price = (float) $this->product->get_regular_price();
        $sale_price    = (float) $this->product->get_sale_price();

        if ($regular_price <= 0 || $sale_price < 0 || $sale_price >= $regular_price) {
            return 0;
        }

        return (int) round((($regular_price - $sale_price) / $regular_price) * 100);
    }

    private function prepare_client_config(): array
    {
        $layout_type = (string) $this->options['layout']['layout'];
        $is_vertical = in_array($layout_type, ['thumbs-left', 'thumbs-right'], true);

        return [
            'layout'       => $layout_type,
            'is_vertical'  => $is_vertical,
            'direction'    => $is_vertical ? 'vertical' : 'horizontal',
            'spaceBetween' => (int) $this->options['layout']['space_between'],
            'loop'         => (bool) $this->options['controls']['loop'],
            'keyboard'     => (bool) $this->options['controls']['keyboard'],
            'drag'         => (bool) $this->options['controls']['drag'],
            'autoplay'     => (bool) $this->options['controls']['autoplay'] ? [
                'delay'                => (int) $this->options['controls']['autoplay_delay'],
                'disableOnInteraction' => false,
                'pauseOnMouseEnter'    => true,
            ] : false,
            'pagination'   => $this->options['controls']['pagination'],
            'thumbs'       => [
                'desktop' => (int) $this->options['layout']['thumbs_per_view_desktop'],
                'tablet'  => (int) $this->options['layout']['thumbs_per_view_tablet'],
                'mobile'  => (int) $this->options['layout']['thumbs_per_view_mobile'],
            ],
            'wc'           => [
                'syncVariation'   => (bool) $this->options['woocommerce']['sync_variation_image'],
                'goToVariation'   => (bool) $this->options['woocommerce']['go_to_variation_image'],
                'resetOnClear'    => (bool) $this->options['woocommerce']['reset_on_clear'],
                'isVariable'      => $this->product instanceof WC_Product_Variable,
            ],
        ];
    }

    private function merge_options(array $custom_options): array
    {
        $base = Helpers::get_settings();
        return array_replace_recursive($base, $custom_options);
    }
}
