<?php

namespace Antlion\Events\Elements;

use Antlion\Events\Event;
use Antlion\Events\Controllers\ElementEventsController;
use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\NumericField;
use SilverStripe\Forms\ToggleCompositeField;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Versioned\GridFieldArchiveAction;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchButton;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

class ElementEvents extends BaseElement
{
    private static $table_name = 'ElementEvents';

    private static $icon = 'font-icon-p-event-alt';

    private static $singular_name = 'Events Element';
    private static $plural_name   = 'Events Elements';

    private static $controller_class = ElementEventsController::class;

    private static $inline_editable = false;

    private static $db = [
        'Content'     => 'HTMLText',
        'Appearance'  => 'Enum("Grid,Carousel","Grid")',

        // Swiper config
        'Loop'            => 'Boolean',
        'Speed'           => 'Int',
        'SpaceBetween'    => 'Int',
        'SlidesPerView'   => 'Int',   // desktop
        'SlidesPerViewMd' => 'Int',   // tablet
        'SlidesPerViewSm' => 'Int',   // mobile
        'CenteredSlides'  => 'Boolean',
        'FreeMode'        => 'Boolean',
        'Pagination'      => 'Boolean',
        'Navigation'      => 'Boolean',
        'Scrollbar'       => 'Boolean',
        'MouseWheel'      => 'Boolean',
        'Autoplay'        => 'Boolean',
        'AutoplayDelay'   => 'Int',
        'Lazy'            => 'Boolean',
    ];

    private static $many_many = [
        'Events' => Event::class,
    ];

    private static $many_many_extraFields = [
        'Events' => [
            'SortOrder' => 'Int',
        ],
    ];

    private static $owns = [
        // Nothing to own here unless you add images/files to the element later
    ];

    public function populateDefaults()
    {
        parent::populateDefaults();

        $this->Speed          = 600;
        $this->SpaceBetween   = 20;
        $this->SlidesPerView  = 5;
        $this->SlidesPerViewMd = 3;
        $this->SlidesPerViewSm = 1;
        $this->Pagination     = true;
        $this->Navigation     = true;
        $this->Loop           = true;
        $this->Autoplay       = true;
        $this->AutoplayDelay  = 5000;
    }

    public function fieldLabels($includeRelations = true)
    {
        $labels = parent::fieldLabels($includeRelations);
        $labels['Content']    = _t(__CLASS__ . '.ContentLabel', 'Intro');
        $labels['Events']     = _t(__CLASS__ . '.EventsLabel', 'Events');
        $labels['Appearance'] = _t(__CLASS__ . '.Appearance', 'Appearance');
        return $labels;
    }

    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            // Intro
            $content = $fields->dataFieldByName('Content');
            if ($content instanceof HTMLEditorField) {
                $content->setRows(6);
            }

            // Appearance
            $fields->insertBefore(
                'Content',
                DropdownField::create('Appearance', $this->fieldLabel('Appearance'), [
                    'Grid'     => 'Grid',
                    'Carousel' => 'Carousel',
                ])->setEmptyString('-- choose --')
            );

            // Events relationship UI: replace default config w/ search + sortable rows
            if ($this->ID) {
                $eventField = $fields->dataFieldByName('Events');
                if ($eventField) {
                    $fields->removeByName('Events');

                    $cfg = $eventField->getConfig();
                    $cfg->removeComponentsByType([
                        GridFieldAddExistingAutocompleter::class,
                        GridFieldDeleteAction::class,
                        GridFieldArchiveAction::class,
                    ]);

                    $cfg->addComponents(
                        new GridFieldOrderableRows('SortOrder'),
                        new GridFieldAddExistingSearchButton()
                    );

                    $fields->addFieldToTab('Root.Main', $eventField);
                }
            }

            // Carousel settings group
            // (we keep these fields in DB, but we can tuck the UI into a toggle)
            $carouselFields = [
                NumericField::create('SlidesPerView',   'Slides per view (desktop)'),
                NumericField::create('SlidesPerViewMd', 'Slides per view (tablet ≥ 640px)'),
                NumericField::create('SlidesPerViewSm', 'Slides per view (mobile < 640px)'),
                NumericField::create('SpaceBetween',    'Space between slides (px)'),
                CheckboxField::create('Loop',           'Loop'),
                CheckboxField::create('Pagination',     'Pagination'),
                CheckboxField::create('Navigation',     'Navigation (prev/next arrows)'),
                CheckboxField::create('Scrollbar',      'Scrollbar'),
                CheckboxField::create('MouseWheel',     'Mousewheel control'),
                CheckboxField::create('Lazy',           'Lazy images'),
                CheckboxField::create('CenteredSlides', 'Centered slides'),
                CheckboxField::create('FreeMode',       'Free mode (drag slides)'),
                CheckboxField::create('Autoplay',       'Autoplay'),
                NumericField::create('AutoplayDelay',   'Autoplay delay (ms)')
                    ->setDescription('Used only when Autoplay is enabled.'),
                NumericField::create('Speed',           'Transition speed (ms)'),
            ];

            // Remove the raw fields so they don't show twice (only if present)
            foreach ([
                'Loop','Speed','SpaceBetween','SlidesPerView','SlidesPerViewMd','SlidesPerViewSm',
                'CenteredSlides','FreeMode','Pagination','Navigation','Scrollbar','MouseWheel',
                'Autoplay','AutoplayDelay','Lazy',
            ] as $name) {
                if ($fields->dataFieldByName($name)) {
                    $fields->removeByName($name);
                }
            }

            $fields->addFieldToTab(
                'Root.Main',
                ToggleCompositeField::create(
                    'CarouselSettings',
                    'Carousel settings',
                    $carouselFields
                )->setStartClosed(true)
            );
        });

        return parent::getCMSFields();
    }

    /**
     * Active events, ordered by the join SortOrder then featured/newest.
     * Uses ORM overlap logic (no raw SQL helper).
     */
    public function getEventList(): DataList
    {
        $today = date('Y-m-d');

        $list = $this->Events()
            ->filterAny([
                'StartDate:LessThanOrEqual' => $today,
                'StartDate' => null,
            ])
            ->filterAny([
                'EndDate:GreaterThanOrEqual' => $today,
                'EndDate' => null,
            ]);

        // If you still have IsFeatured on Event, this is fine; if not, remove that sort key.
        return $list->sort([
            'SortOrder'  => 'ASC',
            'IsFeatured' => 'DESC',
            'Created'    => 'DESC',
        ]);
    }

    public function getSummary(): DBHTMLText
    {
        $count = $this->Events()->count();
        $label = _t(
            static::class . '.PLURALS',
            '1 Event|{count} Events',
            ['count' => $count]
        );

        return DBField::create_field('HTMLText', $label)->Summary(20);
    }

    protected function provideBlockSchema()
    {
        $schema = parent::provideBlockSchema();
        $schema['content'] = $this->getSummary();
        return $schema;
    }

    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Events');
    }

    public function IsCarousel(): bool
    {
        return $this->Appearance === 'Carousel';
    }

    /**
     * Build Swiper options array from DB config.
     */
    public function getCarouselOptions(): array
    {
        // NOTE: in an Element class, use $this, not $this->owner
        $o = [
            'effect'        => 'slide',
            'loop'          => (bool)$this->Loop,
            'speed'         => (int)($this->Speed ?: 600),
            'spaceBetween'  => (int)($this->SpaceBetween ?: 0),
            'centeredSlides'=> (bool)$this->CenteredSlides,
            'breakpoints'   => [
                0    => ['slidesPerView' => (int)($this->SlidesPerViewSm ?: 1)],
                640  => ['slidesPerView' => (int)($this->SlidesPerViewMd ?: 2)],
                1024 => ['slidesPerView' => (int)($this->SlidesPerView ?: 3)],
            ],
        ];

        if ($this->FreeMode) {
            $o['freeMode'] = true;
        }

        if ($this->MouseWheel) {
            // Swiper expects an object; true works, but object is safer if you extend later
            $o['mousewheel'] = true;
        }

        if ($this->Pagination) {
            $o['pagination'] = [
                'el'        => '.swiper-pagination',
                'clickable' => true,
            ];
        }

        if ($this->Navigation) {
            $o['navigation'] = [
                'nextEl' => '.swiper-button-next',
                'prevEl' => '.swiper-button-prev',
            ];
        }

        if ($this->Scrollbar) {
            $o['scrollbar'] = [
                'el'   => '.swiper-scrollbar',
                'hide' => false,
            ];
        }

        if ($this->Autoplay) {
            $o['autoplay'] = [
                'delay'                => (int)($this->AutoplayDelay ?: 5000),
                'disableOnInteraction' => false,
                'pauseOnMouseEnter'    => true,
            ];
        }

        if ($this->Lazy) {
            $o['lazy'] = [
                'loadPrevNext' => true,
            ];
        }

        return $o;
    }

    public function getCarouselOptionsJSON(): string
    {
        // Throws in dev if something is not JSON-encodable (useful)
        return json_encode($this->getCarouselOptions(), JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
