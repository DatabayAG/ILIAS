<?php declare(strict_types=1);

namespace ILIAS\Mail\Setup\Objective;

class Definition
{
    public const NULLABLE = true;

    private Field $field;
    private Field $referenceField;
    private bool $nullable;

    public function __construct(Field $field, Field $referenceField, bool $nullable = false)
    {
        $this->field = $field;
        $this->referenceField = $referenceField;
        $this->nullable = $nullable;
    }

    public function field() : Field
    {
        return $this->field;
    }

    public function referenceField() : Field
    {
        return $this->referenceField;
    }

    public function nullable() : bool
    {
        return $this->nullable;
    }
}
