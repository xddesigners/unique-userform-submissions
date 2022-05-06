<?php

namespace XD\UniqueUserFormSubmissions\Controller;

use PageController;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\ORM\DataObject;
use SilverStripe\UserForms\Model\Submission\SubmittedForm;
use XD\UniqueUserFormSubmissions\Extension\UniqueSubmittedForm;

class ConfirmSubmittedFormController extends PageController
{
    private static $allowed_actions = [
        'index',
    ];

    public function index(HTTPRequest $request)
    {
        $token = $request->param('Token');
        if (!$token) {
            $this->httpError(404);
        }

        $submission = DataObject::get_one(SubmittedForm::class, ['ConfirmationToken' => $token]);
        if (!$submission || !$submission->exists()) {
            $this->httpError(404);
        }

        $submission->ConfirmationStatus = UniqueSubmittedForm::STATUS_CONFIRMED;
        $submission->write();

        return [
            'Title' => _t(__CLASS__ . '.SubmissionConfirmedTitle', 'Bedankt!'),
            'Content' => _t(__CLASS__ . '.SubmissionConfirmedContent', 'Je inzending is bij deze bevestigd.')
        ];
    }

    public static function createLink($token)
    {
        return Director::absoluteURL(self::join_links(['uufs', $token]));
    }
}
