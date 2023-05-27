<?php

namespace XD\UniqueUserFormSubmissions\Field;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\EmailField;
use SilverStripe\UserForms\Model\Submission\SubmittedForm;
use SilverStripe\UserForms\Model\Submission\SubmittedFormField;

class UniqueEmailField extends EmailField
{
    public function validate($validator)
    {
        /** @var SubmittedForm $parent */
        $controller = Controller::curr();
        $parentID = $controller->ID;
        $parentClass = $controller->ClassName;

        $alreaddySubmitted = SubmittedFormField::get()
            ->filter([
                'Name' => $this->Name,
                'Value' => $this->value,
                'Parent.ParentID' => $parentID,
                'Parent.ParentClass' => $parentClass,
            ])->count();

        if ($alreaddySubmitted) {
            $validator->validationError(
                $this->name,
                _t(__CLASS__ . '.ValidationError', 'Dit e-mailadres is al gebruikt.'),
                'validation'
            );

            return false;
        }

        return parent::validate($validator);
    }
}
