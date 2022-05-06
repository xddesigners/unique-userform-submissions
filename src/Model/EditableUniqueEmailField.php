<?php

namespace XD\UniqueUserFormSubmissions\Model;

use SilverStripe\UserForms\Model\EditableFormField;
use SilverStripe\UserForms\Model\EditableFormField\EditableEmailField;
use XD\UniqueUserFormSubmissions\Field\UniqueEmailField;

class EditableUniqueEmailField extends EditableFormField
{
    private static $singular_name = 'Unique email Field';

    private static $plural_name = 'Unique email Fields';

    private static $has_placeholder = true;

    private static $table_name = 'EditableUniqueEmailField';

    private static $defaults = [
        'Required' => 1
    ];

    public function getSetsOwnError()
    {
        return true;
    }

    public function getFormField()
    {
        $field = UniqueEmailField::create($this->Name, $this->Title ?: false, $this->Default)
            ->setFieldHolderTemplate(EditableFormField::class . '_holder')
            ->setTemplate(EditableFormField::class);

        $this->doUpdateFormField($field);

        return $field;
    }

    /**
     * Updates a formfield with the additional metadata specified by this field
     *
     * @param FormField $field
     */
    protected function updateFormField($field)
    {
        parent::updateFormField($field);
        $field->setAttribute('data-rule-email', true);
    }
}
