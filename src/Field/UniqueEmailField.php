<?php

namespace XD\UniqueUserFormSubmissions\Field;

use SilverStripe\Forms\EmailField;
use SilverStripe\UserForms\Model\Submission\SubmittedFormField;

class UniqueEmailField extends EmailField
{
    public function validate($validator)
    {
        $alreaddySubmitted = SubmittedFormField::get()->filter([
            'Name' => $this->Name,
            'Value' => $this->value
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
