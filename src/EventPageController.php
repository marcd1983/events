<?php

namespace Antlion\Events;

use PageController;

use SilverStripe\View\Requirements;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\DataObject;
use SilverStripe\Core\Convert;
use SilverStripe\Control\Director;
use SilverStripe\View\SSViewer;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;

class EventPageController extends PageController
{
  public $Event;

  public function init()
  {
    parent::init();
    Requirements::css('antlion/events:client/css/event.css');
    Requirements::css('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    Requirements::javascript('https://cdn.jsdelivr.net/npm/flatpickr');
    Requirements::customScript(<<<'JS'
    document.addEventListener('DOMContentLoaded', function() {
      // If you kept two inputs:
      flatpickr("#dateStart", { dateFormat: "Y-m-d", allowInput: true });
      flatpickr("#dateEnd",   { dateFormat: "Y-m-d", allowInput: true });

    });
    JS);   
    Requirements::customScript(<<<'JS'
    document.addEventListener('DOMContentLoaded', function() {
      var fp = flatpickr("#dateRange", {
        mode: "range",
        dateFormat: "Y-m-d",
        allowInput: true,
        defaultDate: (function(){
          var s = new URLSearchParams(window.location.search).get('start');
          var e = new URLSearchParams(window.location.search).get('end');
          return (s && e) ? [s, e] : [];
        })(),
        onChange: function(selectedDates, dateStr, instance) {
          var s = document.getElementById('hiddenStart');
          var e = document.getElementById('hiddenEnd');
          if (selectedDates.length === 2) {
            var toYMD = d => [d.getFullYear(), ('0'+(d.getMonth()+1)).slice(-2), ('0'+d.getDate()).slice(-2)].join('-');
            s.value = toYMD(selectedDates[0]);
            e.value = toYMD(selectedDates[1]);
          }
        }
      });
    });
    JS);
  Requirements::customScript(<<<'JS'
  document.addEventListener('DOMContentLoaded', function() {
    // Existing flatpickr:
    if (window.flatpickr) {
      flatpickr("#dateStart", { dateFormat: "Y-m-d", allowInput: true });
      flatpickr("#dateEnd",   { dateFormat: "Y-m-d", allowInput: true });
    }

    // Clear/reset behavior
    var clear = document.getElementById('clearFilters');
    if (clear) {
      clear.addEventListener('click', function(e) {
        e.preventDefault();

        // Clear inputs (both two-input and single-range setups)
        var s = document.getElementById('dateStart');
        var e1 = document.getElementById('dateEnd');
        var r  = document.getElementById('dateRange');
        var hs = document.getElementById('hiddenStart');
        var he = document.getElementById('hiddenEnd');

        if (s)  s.value  = '';
        if (e1) e1.value = '';
        if (r)  r.value  = '';
        if (hs) hs.value = '';
        if (he) he.value = '';

        // Strip only our filter params (and pagination) from the URL
        var url = new URL(window.location.href);
        ['start','end','range','page'].forEach(p => url.searchParams.delete(p));

        // Reload clean URL (preserve any other params like UTMs)
        var qs = url.searchParams.toString();
        window.location.href = url.pathname + (qs ? '?' + qs : '');
      });
    }
  });
  JS);

  }


  public function getCurrentEvents()
  {
    $events = $this->getEvents();
    return $events->filterByCallback(function ($item) {
      return $item->isCurrent();
    });
  }

  public function getFutureEvents()
  {
    $events = $this->getEvents();
    return $events->filterByCallback(function ($item) {
      return !$item->isCurrent();
    });
  }

  private static $allowed_actions = array(
    'show'
  );

  private static $url_handlers = array(
    '$slug!' => 'show'
  );

  // Handle reqests
  public function show(HTTPRequest $request)
  {
    // URL/$Action/$ID/$OtherID
    // SELECT from Event where URLSegment = '2018-special-offers';
    $this->Event = DataObject::get_one(Event::class, "URLSegment = '" . Convert::raw2sql($request->param('slug')) . "'");

    if (!$this->Event) {
      return $this->httpError(404, 'That event could not be found');
    }

    return array(
      'Event' => $this->Event
    );
  }

    /**
   * Produce the correct breadcrumb trail for use on the DataObject Item Page
   */
  public function GenerateBreadcrumbs()
  {
    return $this->Breadcrumbs();
  }

  /**
   * Modified breadcrumbs method from sitetree.
   * This method was modified to add product package into the breadcrumbs.
   * @param int $maxDepth
   * @param bool $unlinked
   * @param bool $stopAtPageType
   * @param bool $showHidden
   * @return mixed
   */
  public function Breadcrumbs($maxDepth = 20, $unlinked = false, $stopAtPageType = false, $showHidden = false)
  {
      $page = $this;
      $pages = [];

      // Add Event as a breadcrumb item
      if ($this->Event && $this->Event->exists()) {
          $pages[] = new ArrayData([
              'Title' => $this->Event->Title,
              'MenuTitle' => $this->Event->Title,
              'Link' => $this->Event->Link(),
              'ID' => $this->Event->ID,
          ]);
      }

      // Add all parent pages
      while (
          $page
          && (!$maxDepth || count($pages) < $maxDepth)
          && (!$stopAtPageType || $page->ClassName != $stopAtPageType)
      ) {
          if ($showHidden || $page->ShowInMenus || $page->ID == $this->ID) {
              $pages[] = $page;
          }
          $page = $page->Parent;
      }

      $template = new SSViewer('BreadcrumbsTemplate');

      return $template->process($this->customise(new ArrayData([
          'Pages' => new ArrayList(array_reverse($pages))
      ])));
  }

  public function CanonicalURL()
  {
    $link = $this->Link();
    if ($link) {
      if ($this->Event)
        $link = $this->Event->Link();
      return Director::protocolAndHost() . $link;
    }
    return false;
  }

  public function getEvents()
  {
      $sort = "SortOrder ASC, StartDate ASC, EndDate ASC";
      $list = $this->Events()->sort($sort);

      // Read optional GET params (?start=YYYY-MM-DD&end=YYYY-MM-DD)
      /** @var HTTPRequest $req */
      $req   = $this->getRequest();
      $start = $req->getVar('start');
      $end   = $req->getVar('end');

      // Normalize to Y-m-d (be forgiving about input)
      $norm = static function (?string $s) {
          if (!$s) return null;
          $t = strtotime($s);
          return $t ? date('Y-m-d', $t) : null;
      };
      $start = $norm($start);
      $end   = $norm($end);

      // Show only “current or future” as you do now… then apply range if present
      $list = $list->filterByCallback(function ($item) {
          return $item->EndDate == null || strtotime($item->EndDate) >= (time() - 24*60*60);
      });

      // Overlap logic:
      // An event overlaps the selected range if:
      //   StartDate <= end  AND  (EndDate or StartDate) >= start
      // Handle open-ended cases gracefully.
      if ($start && $end) {
          $list = $list->filter([
              'StartDate:LessThanOrEqual'      => $end,
              'EndDate:GreaterThanOrEqual'     => $start, // null EndDate handled below
          ])->filterAny([
              'EndDate:GreaterThanOrEqual'     => $start,
              'EndDate'                        => null,   // open-ended event still counts if it started before end
          ]);
      } elseif ($start) {
          $list = $list->filterAny([
              'EndDate:GreaterThanOrEqual'     => $start,
              'EndDate'                        => null,
          ]);
      } elseif ($end) {
          $list = $list->filter('StartDate:LessThanOrEqual', $end);
      }

      return $list;
  }  

public function StartParam(): ?string {
    return $this->getRequest()->getVar('start');
}
public function EndParam(): ?string {
    return $this->getRequest()->getVar('end');
}
public function HasRange(): bool {
    $r = $this->getRequest();
    return (bool)($r->getVar('start') && $r->getVar('end'));
}


}
