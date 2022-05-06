<?php

namespace XD\UniqueUserFormSubmissions\Extension;

use SilverStripe\Control\Email\Email;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\UserForms\Model\Submission\SubmittedForm;
use SilverStripe\UserForms\Model\Submission\SubmittedFormField;
use XD\UniqueUserFormSubmissions\Controller\ConfirmSubmittedFormController;
use XD\UniqueUserFormSubmissions\Model\EditableUniqueEmailField;

/**
 * @property SubmittedForm owner
 */
class UniqueSubmittedForm extends DataExtension
{
    const STATUS_PENDING = 'Pending';
    const STATUS_CONFIRMED = 'Confirmed';

    private static $db = [
        'ConfirmationStatus' => 'Enum("Pending,Confirmed", "Pending")',
        'ConfirmationMailSent' => 'Datetime',
        'ConfirmationToken' => 'Varchar'
    ];
    
    private static $defaults = [
        'ConfirmationStatus' => self::STATUS_PENDING
    ];

    private static $index = [
        'ConfirmationToken' => [
            'type' => 'unique',
            'columns' => ['ConfirmationToken']
        ]
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName(['ConfirmationToken']);
        $fields->addFieldsToTab('Root.Main', [
            ReadonlyField::create('ConfirmationStatus', _t(__CLASS__ . '.ConfirmationStatus', 'Bevestigings status')),
            ReadonlyField::create('ConfirmationMailSent', _t(__CLASS__ . '.ConfirmationMailSent', 'Bevestigings mail verstuurd op'))
        ]);
    }

    public function updateAfterProcess()
    {
        if (
            $this->owner->ConfirmationStatus === self::STATUS_PENDING
            && !$this->owner->ConfirmationMailSent
            && $this->owner->findUniqueEmailField()
        ) {
            if ($this->sendConfirmationMail()) {
                $this->owner->ConfirmationMailSent = DBDatetime::now()->getValue();
                $this->owner->write();
            }
        }
    }

    public function createConfirmationLink($token)
    {
        return ConfirmSubmittedFormController::createLink($token);
    }

    public function sendConfirmationMail()
    {
        $email = Email::create();

        $from = [];
        // Check if recipient senders have been configured
        $recipients = $this->owner->Parent()->FilteredEmailRecipients();
        if ($recipients && $recipients->exists()) {
            foreach ($recipients as $recipient) {
                if ($recipient->EmailFrom) {
                    $from[] = explode(',', $recipient->EmailFrom);
                }
            }
        }
        
        // Fallback to admin_email
        if (empty($from)) {
            $from = Email::config()->get('admin_email');
        }

        $email->setFrom($from);

        $emailField = $this->owner->findUniqueEmailField();
        $email->setTo($emailField->Value);

        $email->setSubject(_t(__CLASS__ . '.ConfirmationMailSubject', 'Bevestig jouw inzending voor {title}', null, [
            'title' => $this->owner->Parent()->getTitle()
        ]));

        // Create a token
        if (!$this->owner->ConfirmationToken) {
            $this->owner->ConfirmationToken = uniqid();
        }

        // Create the link
        $link = $this->owner->createConfirmationLink($this->owner->ConfirmationToken);

        $email->setBody(_t(
            __CLASS__ . '.ConfirmationMailBody', 
            '<p>Bedankt voor jouw inzending voor {title}</p><p>Bevestig jouw inzending door op de volgende link te klikken <a href="{link}">bevestig mijn inzending</a></p><p>Kan je de link niet openen? Kopieer dan de onderstaande link in je browser.</p><p><a href="{link}">{link}</a></p><p>Met vriendelijke groet,<br/>{site}</p>', 
            null, [
                'title' => $this->owner->Parent()->getTitle(),
                'link' => $link,
                'site' => SiteConfig::current_site_config()->getTitle()
            ]
        ));

        return $email->send();
    }

    public function findUniqueEmailField()
    {
        $values = $this->owner->Values();
        if (!$values || !$values->count()) {
            return null;
        }

        return $values->filterByCallback(function(SubmittedFormField $field) {
            return $field->getEditableField() instanceof EditableUniqueEmailField;
        })->first();
    }
}
