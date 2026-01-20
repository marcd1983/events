<?php

namespace Antlion\Events\Controllers;

use DNADesign\Elemental\Controllers\ElementController;
use SilverStripe\View\Requirements;

class ElementEventsController extends ElementController
{
    protected function init(): void
    {
        parent::init();

        $element = $this->getElement();
        if (!$element || !$element->exists()) {
            return;
        }

        // Only init Swiper when the element is configured as Carousel
        if (!method_exists($element, 'IsCarousel') || !$element->IsCarousel()) {
            return;
        }

        $id = (int)$element->ID;

        // JSON is already encoded; keep as raw JS object
        $optionsJson = $element->getCarouselOptionsJSON();

        // Guard if Swiper is not loaded on the page
        $js = <<<JS
(function(){
  function initCarousel_$id() {
    if (typeof window.Swiper === 'undefined') return;

    var el = document.getElementById('carousel-$id');
    if (!el || el.__swiperInit) return;

    el.__swiperInit = true;

    var options = $optionsJson;
    new window.Swiper(el, options);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCarousel_$id, { once: true });
  } else {
    initCarousel_$id();
  }
})();
JS;

        Requirements::customScript($js, "elementevents-carousel-init-$id");
    }
}
