<?php

namespace Antlion\Events;

use PageController;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Security\SecurityToken;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\ORM\ValidationResult;
use App\Models\FormSubmission;
use App\Service\CrmIntegrationService;
use NSWDPC\SpamProtection\RecaptchaV3Field;


class EventPageController extends PageController
{
    public $Event;

    private static $allowed_actions = [
        'show',
        'EventForm',
        'doSubmitEventForm',
    ];

    private static $url_handlers = [
        'EventForm' => 'EventForm',
        'doSubmitEventForm' => 'doSubmitEventForm',
        '$slug!' => 'show',
    ];


    public function init()
    {
        parent::init();

        Requirements::css('antlion/events:client/css/event.css');

        // Flatpickr (single init, no duplicates)
        Requirements::css('https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
        Requirements::javascript('https://cdn.jsdelivr.net/npm/flatpickr');

        Requirements::customScript(<<<'JS'
        document.addEventListener('DOMContentLoaded', function() {
          if (!window.flatpickr) return;

          flatpickr("#dateStart", { dateFormat: "Y-m-d", allowInput: true });
          flatpickr("#dateEnd",   { dateFormat: "Y-m-d", allowInput: true });

          var clear = document.getElementById('clearFilters');
          if (clear) {
            clear.addEventListener('click', function(e) {
              e.preventDefault();

              var s = document.getElementById('dateStart');
              var e1 = document.getElementById('dateEnd');
              if (s)  s.value  = '';
              if (e1) e1.value = '';

              var url = new URL(window.location.href);
              ['start','end','page'].forEach(p => url.searchParams.delete(p));

              var qs = url.searchParams.toString();
              window.location.href = url.pathname + (qs ? '?' + qs : '');
            });
          }
        });
      JS);
    }

    public function show(HTTPRequest $request)
    {
        $slug = (string)$request->param('slug');

        // Scoped to this EventPage (no global get_one override)
        $this->Event = $this->Events()
            ->filter('URLSegment', $slug)
            ->first();

        if (!$this->Event || !$this->Event->exists()) {
            return $this->httpError(404, 'That event could not be found');
        }

        return [
            'Event' => $this->Event,
        ];
    }

    public function CanonicalURL()
    {
        $link = $this->Link();
        if ($link && $this->Event && $this->Event->exists()) {
            $link = $this->Event->Link();
        }
        return $link ? (Director::protocolAndHost() . $link) : false;
    }

    public function getEvents()
    {
        $today = date('Y-m-d');

        $list = $this->Events()
            ->sort('SortOrder ASC, StartDate ASC, EndDate ASC')
            // default: hide “ended” events
            ->filterAny([
                'EndDate:GreaterThanOrEqual' => $today,
                'EndDate' => null,
            ]);

        $req   = $this->getRequest();
        $start = $this->normalizeDate($req->getVar('start'));
        $end   = $this->normalizeDate($req->getVar('end'));

        // Overlap logic in SQL only:
        // StartDate <= end OR StartDate is null
        // AND EndDate >= start OR EndDate is null
        if ($start && $end) {
            $list = $list
                ->filterAny([
                    'StartDate:LessThanOrEqual' => $end,
                    'StartDate' => null,
                ])
                ->filterAny([
                    'EndDate:GreaterThanOrEqual' => $start,
                    'EndDate' => null,
                ]);
        } elseif ($start) {
            $list = $list->filterAny([
                'EndDate:GreaterThanOrEqual' => $start,
                'EndDate' => null,
            ]);
        } elseif ($end) {
            $list = $list->filterAny([
                'StartDate:LessThanOrEqual' => $end,
                'StartDate' => null,
            ]);
        }

        return $list;
    }

    public function getCurrentEvents()
    {
        $today = date('Y-m-d');

        return $this->getEvents()
            ->filterAny([
                'StartDate:LessThanOrEqual' => $today,
                'StartDate' => null,
            ])
            ->filterAny([
                'EndDate:GreaterThanOrEqual' => $today,
                'EndDate' => null,
            ]);
    }

    public function getFutureEvents()
    {
        $today = date('Y-m-d');

        return $this->getEvents()
            ->filter('StartDate:GreaterThan', $today);
    }

    public function StartParam(): ?string
    {
        return $this->normalizeDate($this->getRequest()->getVar('start'));
    }

    public function EndParam(): ?string
    {
        return $this->normalizeDate($this->getRequest()->getVar('end'));
    }

    public function HasRange(): bool
    {
        return (bool)($this->StartParam() && $this->EndParam());
    }

    private function normalizeDate($raw): ?string
    {
        if (!$raw) {
            return null;
        }
        $t = strtotime((string)$raw);
        return $t ? date('Y-m-d', $t) : null;
    }

    /**
     * Breadcrumbs: keeps your existing behavior, but won’t explode if no event.
     */
    public function Breadcrumbs($maxDepth = 20, $unlinked = false, $stopAtPageType = false, $showHidden = false)
    {
        $page = $this;
        $pages = [];

        if ($this->Event && $this->Event->exists()) {
            $pages[] = new ArrayData([
                'Title' => $this->Event->Title,
                'MenuTitle' => $this->Event->Title,
                'Link' => $this->Event->Link(),
                'ID' => $this->Event->ID,
            ]);
        }

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
            'Pages' => new ArrayList(array_reverse($pages)),
        ])));
    }
    protected function currentEvent(): ?Event
    {
        // 1) primary: the route param on the show action
        // $slug = $this->getRequest()->param('Slug');
        $slug = $this->getRequest()->param('slug');

        // 2) fallback: hidden field on POST (useful on form submit routes)
        if (!$slug) {
            $slug = $this->getRequest()->postVar('EventSlug');
        }

        return $slug ? Event::get()->filter('URLSegment', $slug)->first() : null;
    }
    public function EventForm(): Form
    {
        $event = $this->currentEvent();

        $fields = FieldList::create(
            TextField::create('Name', 'Your name')
                ->setAttribute('autocomplete', 'name'),
            EmailField::create('Email', 'Email')
                ->setAttribute('autocomplete', 'email'),
            TextField::create('Phone', 'Phone (optional)')
                ->setAttribute('autocomplete', 'tel'),
            TextareaField::create('Message', 'Message')->setRows(5),

            // context (hidden)
            HiddenField::create('EventSlug', '')
                ->setValue($event?->URLSegment ?? ''),
            HiddenField::create('EventTitle', '')
                ->setValue($event?->Title ?? ''),
            HiddenField::create('EventLink', '')
                ->setValue($event?->Link() ?? $this->Link()),

            // CRM helpers (hidden)
            HiddenField::create('CRMSource', '')
                ->setValue('event-enquiry'),
            HiddenField::create('PageURL', '')
                ->setValue($this->getRequest()->getURL(true))
        );

        // Add Recaptcha field explicitly (v3 is “invisible” but still needs to be on the form)
        $fields->push(RecaptchaV3Field::create('Recaptcha'));

        $actions = FieldList::create(
            FormAction::create('doSubmitEventForm', 'Send')
                ->setUseButtonTag(true)
                ->addExtraClass('button primary')
        );

        $form = Form::create(
            $this,
            'EventForm',
            $fields,
            $actions,
            RequiredFields::create(['Name','Email','Message'])
        );

        $form->setAttribute('novalidate', true);

        if (method_exists($form, 'enableSpamProtection')) {
            $form->enableSpamProtection();
        }

        return $form;
    }
    public function doSubmitEventForm(array $data, Form $form)
    {
        if (empty($data['Name']) || empty($data['Email']) || empty($data['Message'])) {
            $form->sessionMessage('Please complete the required fields.', ValidationResult::TYPE_ERROR);
            return $this->redirectBack();
        }

        $event = $this->currentEvent();

        // Work out who the email is going to (same logic as before)
        $site = SiteConfig::current_site_config();

        // Pull from EventPage.Mailto first (note: field is Mailto)
        $rawTo = (string)($this->data()->Mailto ?? '');

        // Parse multiple recipients (commas, semicolons, newlines)
        $emails = preg_split('/[\s,;]+/', trim($rawTo)) ?: [];
        $emails = array_values(array_filter(array_map('trim', $emails)));

        // Validate emails
        $emails = array_values(array_filter($emails, function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        }));

        // Fallbacks if Mailto is empty/invalid
        if (empty($emails)) {
            $fallback = $site->SupportEmail ?? $site->ContactRecipientEmail ?? $site->Email ?? null; // use what you have available
            if ($fallback && filter_var($fallback, FILTER_VALIDATE_EMAIL)) {
                $emails = [$fallback];
            } else {
                $emails = ['webmaster@localhost'];
            }
        }

        $to = $emails; // Email::setTo accepts string or array
        $sentTo = implode(', ', $emails);

        // Build context values
        $eventTitle = $data['EventTitle'] ?? ($event?->Title ?? '');
        $eventLink  = $data['EventLink'] ?? ($event?->Link() ?? $this->Link());

        // Create generic submission record
        $submission = FormSubmission::create([
            'FormName'       => 'Event enquiry',
            'FormAction'     => 'EventForm',
            'PageID'         => $this->ID,
            'Context'        => $eventTitle,
            'ContextLink'    => $eventLink,
            'SubmitterName'  => $data['Name'] ?? '',
            'SubmitterEmail' => $data['Email'] ?? '',
            'SubmitterPhone' => $data['Phone'] ?? '',
            'Message'        => $data['Message'] ?? '',
            'SentTo'         => $sentTo,
            'RawData'        => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ]);
        $submission->write();

        // 🔗 CRM integration hook (safe no-op until a real client is wired)
        $crm = CrmIntegrationService::singleton();
        $crm->captureLead($data, $this->getRequest(), [
            'source'       => $data['CRMSource'] ?? 'event-enquiry',
            'page_url'     => $data['PageURL'] ?? $eventLink,
            'event_id'     => $event?->ID,
            'event_title'  => $eventTitle,
        ]);

        // Email subject/body same as before
        $prefix = $this->data()->FormSubjectPrefix ?: '[Event Enquiry]';
        $subject = sprintf(
            '%s %s',
            $prefix,
            $eventTitle ? ('- ' . $eventTitle) : ('from ' . $submission->SubmitterName)
        );

        $body = <<<HTML
        <p><strong>Event enquiry</strong></p>
        <p><strong>Event:</strong> {$eventTitle}<br>
        <strong>Link:</strong> {$eventLink}</p>
        <p><strong>Name:</strong> {$submission->SubmitterName}<br>
        <strong>Email:</strong> {$submission->SubmitterEmail}<br>
        <strong>Phone:</strong> {$submission->SubmitterPhone}</p>
        <p><strong>Message:</strong><br>
        <pre style="white-space:pre-wrap; font-family:inherit">{$submission->Message}</pre></p>
        HTML;

        $email = Email::create()
            ->setTo($to)
            ->setSubject($subject)
            ->setBody($body);

        if (!empty($submission->SubmitterEmail)) {
            $email->setReplyTo($submission->SubmitterEmail);
        }

        try {
            $email->send();
            $submission->Status = 'Emailed';
            $submission->write();

            $form->sessionMessage('Thanks—your enquiry has been sent.', ValidationResult::TYPE_GOOD);
        } catch (\Throwable $e) {
            $submission->Status       = 'Error';
            $submission->ErrorMessage = $e->getMessage();
            $submission->write();

            $form->sessionMessage('Sorry, we could not send your message right now.', ValidationResult::TYPE_ERROR);
        }

        return $this->redirectBack();
    }
}
