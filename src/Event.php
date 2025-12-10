<?php

namespace Antlion\Events;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\DateField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Assets\Image;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use SilverStripe\ORM\DataList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TimeField;

class Event extends DataObject
{
    private static $db = array(
	    'Title' => 'Varchar(255)',
        'SubTitle' => 'Varchar(255)',
        'URLSegment' => 'Varchar(255)',
	    'StartDate' => 'Date',
	    'EndDate' => 'Date',
	    'Location' => 'Varchar(255)',
        'Time' => 'Varchar(255)',
	    'Content' => 'HTMLText',   
	    'SortOrder' => 'Int',
        'HideImage' => 'Boolean'
    );

    private static $has_one = array(
	    'Image' => Image::class,
	    'EventPage' => EventPage::class
    );

    private static $default_sort = 'SortOrder ASC';

    private static $summary_fields = array("Image.CMSThumbnail" => "Event Image", "Title" => "Title");

    private static $owns = [
        'Image'
    ];

    public function getCMSFields(): FieldList
    {
        // DateField::set_default_config('showcalendar', true);
        return new FieldList(
	        TextField::create('Title', 'Title'),
            TextField::create('SubTitle', 'Sub Title'),
            ReadonlyField::create('URLSegment', 'URL Segment'),
	        DateField::create('StartDate', 'Start Date')->setDescription('Optional and used for starting event on specific date, click field for calendar'),
	        DateField::create('EndDate', 'End Date')->setDescription('Optional and used for ending event on specific date, click field for calendar'),
            TimeField::create('Time', 'Time')->setDescription('Optional and used for setting a time for event'),
	        TextField::create('Location', 'Event Location Information'),
	        HTMLEditorField::create('Content', 'Event Content'),
	        UploadField::create('Image', 'Attach a thumbnail')->setDescription('Uploading thumbnail is optional and used for Events quick view grid'),
            CheckboxField::create('HideImage', 'Hide Image')->setDescription('Hide image from event page'),
        );
    }

    public function onBeforeWrite()
    {
        $this->MangeURLSegment();
        parent::onBeforeWrite();
    }

    public function MangeURLSegment(){
        // If there is no URLSegment set, generate one from Title
        if((!$this->URLSegment || $this->URLSegment == 'new-event') && $this->Title != 'New Event')
        {
            $segment = preg_replace('/[^A-Za-z0-9]+/','-',$this->Title);
            $segment = preg_replace('/-+/','-',$segment);
            $this->URLSegment = $segment;
        } //end if
        else if($this->isChanged('URLSegment')){
        // Make sure the URLSegment is valid for use in a URL
            $segment = preg_replace('/[^A-Za-z0-9]+/','-',$this->URLSegment);
            $segment = preg_replace('/-+/','-',$segment);

            // If after sanitising there is no URLSegment, give it a reasonable default
            if(!$segment)
            {
                $segment = "event-" . $this->ID;
            } //end if

            $this->URLSegment = $segment;
        } //end else

        // Ensure that this object has a non-conflicting URLSegment value.
        $count = 2;
        while($this->LookForExistingURLSegment($this->URLSegment))
        {
            $this->URLSegment = preg_replace('/-[0-9]+$/', '', $this->URLSegment) . '-' . $count;
            $count++;
        }

    }

    //Test whether the URLSegment exists already on another Event
    public function LookForExistingURLSegment($URLSegment)
    {
        return (DataObject::get_one(Event::class, "URLSegment = '" . $URLSegment ."' AND ID != " . $this->ID));
    }

	public function isCurrent()
    {
        if ($this->EndDate == '') {
            return true;
        }
		if ($this->EndDate == '') {
            return strtotime($this->EndDate) <= time();
        }
		if (strtotime ($this->EndDate)>=time() - 24 * 60 * 60){
			return true;
		}
		return false;
	}

    public function EndDateNice()
    {
        if ($this->EndDate == '') {
            return '';
        }
        return date('M j', strtotime($this->EndDate));
    }

    public function StartDateNice()
    {
        if ($this->StartDate == '') {
            return '';
        }
        return date('M j', strtotime($this->StartDate));
    }

    public function EndDateNiceYear()
    {
        if ($this->EndDate == '') {
            return '';
        }
        return date('Y', strtotime($this->EndDate));
    }    

    public function Link(){
        return $this->EventPage()->Link($this->URLSegment);
        // return $this->EventPage()->Link('show/'.$this->URLSegment);
    }

    public function StrippedContent()
    {
        if (!$this->Content) {
            return '';
        }

        $content = $this->Content;

        // 1. Remove all Silverstripe shortcodes (e.g., [image ...], [file ...], etc.)
        $content = preg_replace('/\[(.*?)\]/', '', $content);

        // 2. Strip any leftover HTML tags
        $text = strip_tags($content);

        // 3. Remove &amp; and &nbsp; (and collapse extra spaces)
        $text = str_replace(['&amp;', '&nbsp;'], ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text); // normalize spacing

        // 4. Split into sentences
        $sentences = preg_split('/(?<=[.?!])\s+/', trim($text), 3, PREG_SPLIT_NO_EMPTY);

        // 5. Take first two
        $excerpt = implode(' ', array_slice($sentences, 0, 2));

        return $excerpt;
    }

    public function EventTimeDisplay()
    {
        $output = '';

        $start = !empty($this->StartDate) ? date('F j, Y', strtotime($this->StartDate)) : null;
        $end   = !empty($this->EndDate)   ? date('F j, Y', strtotime($this->EndDate))   : null;

        if ($start && $end) {
            $output = "Starts {$start} through {$end}";
        } elseif ($end) {
            $output = "Ends {$end}";
        } elseif ($start) {
            $output = $start;
        }

        return $output;
    }
     public static function activeFilterSQL(): string
    {
        return '("StartDate" IS NULL OR "StartDate" <= CURRENT_DATE())'
            . ' AND ("EndDate" IS NULL OR "EndDate" >= CURRENT_DATE())';
    }

    public static function getActive(): DataList
    {
        return static::get()->where(static::activeFilterSQL());
    }

    public function getActiveNice(): string
    {
        return $this->Active() ? 'Yes' : 'No';
    }
}
