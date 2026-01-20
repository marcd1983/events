<?php

namespace Antlion\Events;

use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Assets\Image;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TimeField;
use SilverStripe\LinkField\Models\Link;
use SilverStripe\LinkField\Form\MultiLinkField;
use SilverStripe\View\Parsers\URLSegmentFilter;

class Event extends DataObject
{
    private static $table_name = 'Antlion_Event';

    private static $db = [
        'Title'      => 'Varchar(255)',
        'SubTitle'   => 'Varchar(255)',
        'URLSegment' => 'Varchar(255)',
        'StartDate'  => 'Date',
        'EndDate'    => 'Date',
        'Location'   => 'Varchar(255)',
        'Time'       => 'Time',          // was Varchar; keep TimeField consistent
        'Content'    => 'HTMLText',
        'SortOrder'  => 'Int',
        'HideImage'  => 'Boolean',
    ];

    private static $has_one = [
        'Image'     => Image::class,
        'EventPage' => EventPage::class,
    ];

    private static $has_many = [
        'Links' => Link::class . '.Owner',
    ];


    private static $owns = [
        'Image',
        'Links',
    ];

    private static $default_sort = 'SortOrder ASC';

    private static $summary_fields = [
        'Image.CMSThumbnail' => 'Event Image',
        'Title'              => 'Title',
        'StartDate'          => 'Start',
        'EndDate'            => 'End',
    ];

    private static $indexes = [
        // Unique per EventPage, not globally
        'EventPageURLSegment' => [
            'type'    => 'unique',
            'columns' => ['EventPageID', 'URLSegment'],
        ],
    ];

    public function getCMSFields(): FieldList
    {
        $fields = FieldList::create(
            TextField::create('Title', 'Title'),
            TextField::create('SubTitle', 'Sub Title'),
            TextField::create('URLSegment', 'URL Segment')
                ->setDescription('Auto-generated from Title if left blank. Must be unique per Events page.'),
            DateField::create('StartDate', 'Start Date')
                ->setDescription('Optional. If set, event is considered “future” until this date.'),
            DateField::create('EndDate', 'End Date')
                ->setDescription('Optional. If set, event ends after this date.'),
            TimeField::create('Time', 'Time')
                ->setDescription('Optional.'),
            TextField::create('Location', 'Event Location Information'),
            HTMLEditorField::create('Content', 'Event Content'),
            UploadField::create('Image', 'Attach a thumbnail')
                ->setDescription('Optional. Used for event cards.'),
            CheckboxField::create('HideImage', 'Hide Image')
                ->setDescription('Hide image from event detail page.'),
            MultiLinkField::create('Links', 'CTA Buttons', $this->Links())
        );

        return $fields;
    }

    protected function onBeforeWrite()
    {
        parent::onBeforeWrite();

        $this->URLSegment = $this->generateURLSegment((string)$this->URLSegment);
        $this->URLSegment = $this->ensureUniqueURLSegment($this->URLSegment);
    }

    public function validate(): ValidationResult
    {
        $result = parent::validate();

        if (!$this->Title) {
            $result->addError('Title is required.');
        }

        if ($this->StartDate && $this->EndDate) {
            $s = strtotime($this->StartDate);
            $e = strtotime($this->EndDate);
            if ($s && $e && $e < $s) {
                $result->addError('End Date cannot be earlier than Start Date.');
            }
        }

        // Unique per EventPage validation (helps editors before hitting DB unique index)
        if ($this->EventPageID && $this->URLSegment) {
            $exists = static::get()
                ->filter([
                    'EventPageID' => $this->EventPageID,
                    'URLSegment'  => $this->URLSegment,
                ])
                ->exclude('ID', (int)$this->ID)
                ->exists();

            if ($exists) {
                $result->addError('URL Segment must be unique per Events page.');
            }
        }

        return $result;
    }

    private function generateURLSegment(string $raw): string
    {
        $raw = trim($raw);

        if ($raw === '') {
            $raw = (string)$this->Title;
        }

        $filter = URLSegmentFilter::create();
        $seg = $filter->filter($raw);

        $seg = strtolower(trim((string)$seg, '-'));

        if ($seg === '') {
            $seg = 'event';
        }

        // Reasonable length cap
        $seg = substr($seg, 0, 100);

        return $seg;
    }

    private function ensureUniqueURLSegment(string $base): string
    {
        $base = $base ?: 'event';
        $seg = $base;
        $i = 2;

        while ($this->urlSegmentExists($seg)) {
            $seg = preg_replace('/-\d+$/', '', $base) . '-' . $i;
            $i++;
        }

        return $seg;
    }

    private function urlSegmentExists(string $seg): bool
    {
        if (!$this->EventPageID) {
            // If not yet attached to a page, fall back to global uniqueness to reduce collisions
            return static::get()
                ->filter('URLSegment', $seg)
                ->exclude('ID', (int)$this->ID)
                ->exists();
        }

        return static::get()
            ->filter([
                'EventPageID' => $this->EventPageID,
                'URLSegment'  => $seg,
            ])
            ->exclude('ID', (int)$this->ID)
            ->exists();
    }

    public function Link(): string
    {
        if ($this->EventPageID && $this->EventPage()->exists()) {
            // Your controller maps '$slug' => show() so /eventpage/{slug} works
            return $this->EventPage()->Link($this->URLSegment);
        }
        return '#';
    }

    public function IsMultiDay(): bool
    {
        return !empty($this->StartDate) && !empty($this->EndDate) && ($this->StartDate !== $this->EndDate);
    }

    public function Active(): bool
    {
        return $this->isCurrent();
    }

    public function isCurrent(): bool
    {
        $today = strtotime('today');

        $start = $this->StartDate ? strtotime($this->StartDate) : null;
        $end   = $this->EndDate ? strtotime($this->EndDate . ' 23:59:59') : null;

        if ($start && $start > $today) {
            return false;
        }
        if ($end && $end < $today) {
            return false;
        }
        return true;
    }

    public function isFuture(): bool
    {
        $today = strtotime('today');
        return $this->StartDate && strtotime($this->StartDate) > $today;
    }

    public function StrippedContent(): string
    {
        if (!$this->Content) {
            return '';
        }

        $content = $this->Content;

        // Remove SilverStripe-style shortcodes: [image ...], [file ...], etc.
        $content = preg_replace('/\[(.*?)\]/', '', $content);

        $text = strip_tags($content);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', trim($text));

        // First two sentences-ish
        $sentences = preg_split('/(?<=[.?!])\s+/', $text, 3, PREG_SPLIT_NO_EMPTY);
        return implode(' ', array_slice($sentences ?: [], 0, 2));
    }

    public function EventTimeDisplay(): string
    {
        $start = !empty($this->StartDate) ? date('F j, Y', strtotime($this->StartDate)) : null;
        $end   = !empty($this->EndDate) ? date('F j, Y', strtotime($this->EndDate)) : null;

        if ($start && $end) {
            return "Starts {$start} through {$end}";
        }
        if ($end) {
            return "Ends {$end}";
        }
        if ($start) {
            return $start;
        }

        return '';
    }

    public function TimeDisplay(): string
    {
        if (!$this->Time) {
            return '';
        }

        // DB "Time" often comes through as "HH:MM:SS"
        $raw = (string)$this->Time;

        // Try parsing as HH:MM(:SS)
        $ts = strtotime('1970-01-01 ' . $raw);
        if ($ts) {
            // e.g. 6:30 PM
            return date('g:i A', $ts);
        }

        // Fallback: return as-is
        return $raw;
    }


    /**
     * Template-safe image URL for cards:
     * avoids calling FocusFill in templates when module isn't installed.
     */
    public function CardImageURL(int $w = 600, int $h = 600): ?string
    {
        $img = $this->Image();
        if (!$img || !$img->exists()) {
            return null;
        }

        if (method_exists($img, 'FocusFill')) {
            $v = $img->FocusFill($w, $h);
            return $v ? $v->getURL() : $img->getURL();
        }

        $v = $img->Fill($w, $h);
        return $v ? $v->getURL() : $img->getURL();
    }

    public static function getActive(): DataList
    {
        $today = date('Y-m-d');

        return static::get()
            ->filterAny([
                'StartDate:LessThanOrEqual' => $today,
                'StartDate' => null,
            ])
            ->filterAny([
                'EndDate:GreaterThanOrEqual' => $today,
                'EndDate' => null,
            ]);
    }
}
