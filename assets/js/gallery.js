(function ($) {
    'use strict';

    /**
     * Resolves the Swiper constructor provided by Elementor
     */
    function resolveSwiperConstructor() {
        if (typeof elementorFrontend !== 'undefined' && elementorFrontend.utils && elementorFrontend.utils.swiper) {
            return elementorFrontend.utils.swiper;
        }
        if (typeof window.Swiper !== 'undefined') {
            return window.Swiper;
        }
        return null;
    }

    /**
     * Initializes a single gallery instance
     */
    function initGallery($wrapper) {
        if ($wrapper.data('epg-initialized')) {
            return;
        }

        var SwiperConstructor = resolveSwiperConstructor();
        if (!SwiperConstructor) {
            if (window.epgData && window.epgData.debug) {
                console.warn('[EPG] Swiper library not found. Waiting for Elementor frontend ready.');
            }
            return;
        }

        var config = $wrapper.data('epg-config') || {};
        var isRTL = Boolean(window.epgData && window.epgData.is_rtl);

        var $mainContainer = $wrapper.find('.epg-main-swiper');
        var $thumbsContainer = $wrapper.find('.epg-thumbs-swiper');

        var thumbsSwiper = null;

        // 1. Initialize Thumbnails Swiper if element exists
        if ($thumbsContainer.length) {
            var isVertical = config.is_vertical;
            var thumbsPerView = (config.thumbs && config.thumbs.desktop) ? config.thumbs.desktop : 5;

            var thumbsOptions = {
                direction: isVertical ? 'vertical' : 'horizontal',
                slidesPerView: thumbsPerView,
                spaceBetween: 8,
                watchSlidesProgress: true,
                slideToClickedSlide: true,
                freeMode: true,
                breakpoints: {
                    0: {
                        slidesPerView: (config.thumbs && config.thumbs.mobile) ? config.thumbs.mobile : 4,
                        direction: isVertical ? 'horizontal' : 'horizontal' // Fallback to horizontal on small viewports
                    },
                    768: {
                        slidesPerView: (config.thumbs && config.thumbs.tablet) ? config.thumbs.tablet : 5,
                        direction: isVertical ? 'vertical' : 'horizontal'
                    },
                    1024: {
                        slidesPerView: thumbsPerView,
                        direction: isVertical ? 'vertical' : 'horizontal'
                    }
                }
            };

            thumbsSwiper = new SwiperConstructor($thumbsContainer[0], thumbsOptions);
        }

        // 2. Main Swiper Configuration
        var mainOptions = {
            slidesPerView: 1,
            spaceBetween: config.spaceBetween || 0,
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            loop: Boolean(config.loop),
            grabCursor: Boolean(config.drag),
            keyboard: config.keyboard ? { enabled: true } : false,
            autoplay: config.autoplay ? config.autoplay : false,
            navigation: {
                nextEl: $wrapper.find('.epg-button-next')[0],
                prevEl: $wrapper.find('.epg-button-prev')[0]
            }
        };

        // Pagination type handling
        if (config.pagination && config.pagination !== 'none') {
            mainOptions.pagination = {
                el: $wrapper.find('.epg-pagination')[0],
                clickable: true,
                type: config.pagination
            };
        }

        // Link thumbs instance if available
        if (thumbsSwiper) {
            mainOptions.thumbs = {
                swiper: thumbsSwiper
            };
        }

        // Initialize Main Swiper
        var mainSwiper = new SwiperConstructor($mainContainer[0], mainOptions);

        $wrapper.data('epg-main-swiper', mainSwiper);
        $wrapper.data('epg-thumbs-swiper', thumbsSwiper);
        $wrapper.data('epg-initialized', true);

        // 3. WooCommerce Variation Events Binding
        if (config.wc && config.wc.isVariable) {
            bindVariationEvents($wrapper, mainSwiper);
        }
    }

    /**
     * Binds WooCommerce variation selection changes to navigate slider
     */
    function bindVariationEvents($wrapper, mainSwiper) {
        var $form = $('form.variations_form');
        if (!$form.length) {
            return;
        }

        $form.on('found_variation', function (event, variation) {
            var $badge = $wrapper.find('.epg-badge-discount').first();
            if (variation && $badge.length) {
                var regularPrice = parseFloat(variation.display_regular_price);
                var salePrice = parseFloat(variation.display_price);

                if (regularPrice > 0 && salePrice >= 0 && salePrice < regularPrice) {
                    $badge.text(Math.round(((regularPrice - salePrice) / regularPrice) * 100) + '%').show();
                } else {
                    $badge.hide();
                }
            }

            var config = $wrapper.data('epg-config') || {};
            if (!config.wc || !config.wc.syncVariation || !variation || !variation.image_id) {
                return;
            }

            var imageId = String(variation.image_id);
            var $targetSlides = $wrapper.find('.epg-main-slide[data-image-id="' + imageId + '"]');
            if (!$targetSlides.length) {
                return;
            }

            // Prefer the first NON-cloned slide (original one) using its explicitly rendered index
            var originalIndex = null;
            $targetSlides.each(function () {
                let dataIndex = $(this).attr('data-slide-index');
                if (originalIndex === null && typeof dataIndex !== 'undefined' && dataIndex !== '') {
                    originalIndex = parseInt(dataIndex, 10);
                }
            });

            if (originalIndex !== null) {
                navigateToSlide(mainSwiper, originalIndex);
            }
        });

        $form.on('reset_data', function () {
            var config = $wrapper.data('epg-config') || {};
            var $badge = $wrapper.find('.epg-badge-discount').first();
            if ($badge.length) {
                var defaultDiscount = parseInt($badge.attr('data-default-discount'), 10) || 0;
                if (defaultDiscount > 0) {
                    $badge.text(defaultDiscount + '%').show();
                } else {
                    $badge.hide();
                }
            }

            if (config.wc && config.wc.resetOnClear) {
                if (mainSwiper.params.loop) {
                    mainSwiper.slideToLoop(0);
                } else {
                    mainSwiper.slideTo(0);
                }
            }
        });
    }

    /**
     * DOM Ready Handler and Elementor hooks
     */
    $(document).ready(function () {
        $('.epg-gallery-wrapper').each(function () {
            initGallery($(this));
        });

        // Re-check after Elementor frontend finishes initialization
        $(window).on('elementor/frontend/init', function () {
            $('.epg-gallery-wrapper').each(function () {
                initGallery($(this));
            });
        });
    });

    /**
 * Safely navigates the main swiper to a desired slide index
 * considering loop mode duplicate slides
 */
    function navigateToSlide(mainSwiper, viewIndex) {
        if (typeof viewIndex === 'undefined' || viewIndex < 0) {
            return;
        }

        // In loop mode Swiper clones slides; use slideToLoop on the real view index
        if (mainSwiper.params.loop) {
            mainSwiper.slideToLoop(viewIndex);
        } else {
            mainSwiper.slideTo(viewIndex);
        }
    }


})(jQuery);
