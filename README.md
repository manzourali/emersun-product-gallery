=== Emersun Product Gallery ===
Contributors: emersun
Tags: woocommerce, product gallery, elementor, swiper, gallery
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.0.0

Replace the WooCommerce single product image gallery with a configurable, responsive gallery powered by Elementor's Swiper assets. A shortcode is also available for placing a product gallery in page content or an Elementor layout.

== Description ==

Emersun Product Gallery adds a responsive image gallery to WooCommerce product pages. Choose a thumbnail layout, configure navigation and playback controls, and optionally include product variation images.

Elementor is required because the gallery uses Elementor's registered Swiper assets. The plugin integrates with the standard WooCommerce product gallery hook and Elementor's Product Images widget.

Features include:

* Thumbnail layouts below, to the left, or to the right of the main image, plus a single-image layout.
* Configurable arrows, pagination, keyboard navigation, dragging, looping, and autoplay.
* Responsive thumbnail counts and image sizing options.
* Optional variation image inclusion and synchronization.
* Optional sale and stock badges.
* A shortcode with per-gallery options.
* Custom CSS and optional debug logging in the settings page.

== Requirements ==

* WordPress 6.0 or later.
* PHP 7.4 or later.
* WooCommerce 7.0 or later.
* Elementor must be installed and active for the gallery to render.

== Installation ==

1. Upload the `emersun-product-gallery` folder to the `/wp-content/plugins/` directory, or install the plugin through WordPress.
2. Activate **Emersun Product Gallery** from the Plugins screen.
3. Make sure WooCommerce and Elementor are active.
4. Configure the gallery under **WooCommerce > Emersun Gallery**.

The gallery replacement and shortcode are enabled by default. If Elementor is inactive, the plugin will not replace the product gallery; the shortcode displays a dependency message.

== Usage ==

On standard WooCommerce product pages, the plugin replaces the default product gallery when gallery replacement is enabled in settings.

Use the `[epg_gallery]` shortcode inside a product context to display that product's gallery. To choose a product explicitly, provide its ID:

`[epg_gallery product_id="123"]`

Available shortcode attributes:

* `product_id` - WooCommerce product ID. Defaults to the current product.
* `layout` - `thumbs-bottom`, `thumbs-left`, `thumbs-right`, or `single`.
* `thumbs` - show thumbnails: `true` or `false`.
* `autoplay` - enable autoplay: `true` or `false`.
* `loop` - enable looping: `true` or `false`.
* `pagination` - `none`, `bullets`, `fraction`, or `progressbar`.
* `arrows` - show navigation arrows: `true` or `false`.
* `class` - additional CSS class for the gallery.

Example:

`[epg_gallery product_id="123" layout="thumbs-left" thumbs="true" arrows="true" pagination="fraction"]`

Shortcode attributes override the corresponding gallery settings for that instance. The shortcode can be disabled in the plugin settings.

== Frequently Asked Questions ==

= Why is the gallery not appearing? =

Confirm that WooCommerce and Elementor are active and that gallery replacement is enabled under **WooCommerce > Emersun Gallery**. The shortcode also requires the shortcode option to be enabled.

= Can I use a gallery for a product other than the current product? =

Yes. Pass its WooCommerce product ID with the `product_id` attribute, for example `[epg_gallery product_id="123"]`.

== Changelog ==

= 1.0.0 =
* Initial release.
