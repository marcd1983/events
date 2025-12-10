<?php

namespace Antlion\Events;

use Page;
use Silverstripe\Forms\GridField\GridField;
use Silverstripe\Forms\GridField\GridFieldPaginator;
use Silverstripe\Forms\GridField\GridFieldConfig_RecordEditor;
use UndefinedOffset\SortableGridField\Forms\GridFieldSortableRows;
use SilverStripe\Forms\DropdownField;


class EventPage extends Page
{

    private static $description = "Events Module";
    private static $icon_class = 'font-icon-p-event-alt';

    private static $db = [
      'SortOrder' => 'Int',
      "GridColumn" => "Enum('2,3,4,5,6','4')",
    ];

    private static $has_many = ['Events' => Event::class];

    public function getCMSFields() {
      $fields = parent::getCMSFields();

      $gridOptions = singleton(self::class)->dbObject('GridColumn')->enumValues();

      $config = GridFieldConfig_RecordEditor::create();
      // $config->addComponent(new GridFieldSortableRows('SortOrder'));

      // Add a pagination component with a limit of 200 rows per page
      $config->getComponentByType(GridFieldPaginator::class)
      ->setItemsPerPage(200);
      $gridField = GridField::create('Events', 'Events', $this->Events(), $config);

      $fields->addFieldToTab('Root.Add Events', $gridField);

      $fields->addFieldToTab(
          "Root.Main",
          DropdownField::create(
              "GridColumn",
              "Events Grid Size?"
          )
          ->setSource($gridOptions)
          ->setDescription('Used by the Events grid'),
          'Content'
      );

      return $fields;
  }    
   

}
