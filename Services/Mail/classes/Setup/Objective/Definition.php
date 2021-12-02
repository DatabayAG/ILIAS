<?php declare(strict_types=1);

namespace ILIAS\Mail\Setup\Objective;

class Definition
{
    /**
     * @var Association[]
     */
    private array $associations;
    private bool $nullable;
    private Ignore $ignore;

    public function __construct(array $associations, ?Ignore $ignore = null)
    {
        $this->associations = $associations;
        $this->ignore = null === $ignore ? new Ignore() : $ignore;
        $this->validate();
    }

    /**
     * @return Association[]
     */
    public function associations() : array
    {
        return $this->associations;
    }

    /**
     * @return string[]
     */
    public function ignoreValues() : array
    {
        return $this->ignore->values();
    }

    public function tableName() : string
    {
        return $this->associations[0]->field()->tableName();
    }

    public function referenceTableName() : string
    {
        return $this->associations[0]->referenceField()->tableName();
    }

    private function validate() : void
    {
        if (\count($this->associations) === 0) {
            throw new \InvalidArgumentException('associations must not be empty');
        }

        $first = $this->associations[0];

        foreach ($this->associations as $association) {
            if ($association->field()->tableName() !== $first->field()->tableName() ||
                $association->referenceField()->tableName() !== $first->referenceField()->tableName()
            ) {
                throw new \InvalidArgumentException('All fields must have the same table');
            }
        }
    }
}
