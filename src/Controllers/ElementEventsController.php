<?php

namespace Antlion\Events\Controllers;

use SilverStripe\View\Requirements;
use DNADesign\Elemental\Controllers\ElementController;

class ElementEventsController extends ElementController
{
    // In SilverStripe controllers, init should be protected
    protected function init(): void
    {
        parent::init();

        // Optional: ensure Swiper assets are present (you may already load these globally)
        // Requirements::css('themes/foundation-theme/css/swiper-bundle.min.css');
        // Requirements::javascript('themes/foundation-theme/js/swiper-bundle.min.js');

        // Build per-instance init
        $element = $this->getElement();                  // Elemental controller provides this
        $id      = (int) $element->ID;
        $options = $element->getCarouselOptionsJSON();   // your method

        $js = <<<JS
        (function(){
          function initCarousel_$id() {
            var el = document.getElementById('carousel-$id');
            if (!el || el.__swiperInit) return;
            el.__swiperInit = true;
            var options = $options;
            new Swiper(el, options);
          }

          // Run on initial load
          if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCarousel_$id, { once: true });
          } else {
            initCarousel_$id();
          }
        })();
        JS;

        // Emit inline (at bottom if you enable force_js_to_bottom)
        Requirements::customScript($js, "carousel-init-$id");
    }
}
