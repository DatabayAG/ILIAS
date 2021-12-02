<?php declare(strict_types=1);

namespace ILIAS\Mail\Setup\Objective;

class Association
{
    private Field $field;
    private Field $referenceField;

    public function __construct(Field $field, Field $referenceField)
    {
        $this->field = $field;
        $this->referenceField = $referenceField;
    }

    public function field() : Field
    {
        return $this->field;
    }

    public function referenceField() : Field
    {
        return $this->referenceField;
    }
}
