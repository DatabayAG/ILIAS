<?php declare(strict_types=1);

namespace ILIAS\Mail\Setup\Objective;

class Field
{
    private string $tableName;
    private string $fieldName;
    private string $originalTableName;
    private string $convertedTableName;

    public function __construct(string $tableName, string $fieldName, ?string $alias = null)
    {
        $this->tableName = $tableName;
        $this->fieldName = $fieldName;
        $this->convertedTableName = $tableName;
        $this->originalTableName = $tableName;

        if (null !== $alias) {
            $this->convertedTableName = $tableName . ' as ' . $alias;
            $this->tableName = $alias;
            $this->originalTableName = $tableName;
        }
    }

    public function tableName() : string
    {
        return $this->convertedTableName;
    }

    public function fieldName() : string
    {
        return $this->tableName . '.' . $this->fieldName;
    }

    public function rawFieldName() : string
    {
        return $this->fieldName;
    }

    public function rawTableName() : string
    {
        return $this->originalTableName;
    }
}
