<?php declare(strict_types=1);

namespace ILIAS\Mail\Setup\Objective;

class Ignore
{
    /**
     * @var string[]
     */
    private array $valuesToIgnore;

    public function __construct(?string ...$valuesToIgnore)
    {
        $this->valuesToIgnore = array_map(static function (?string $valueToIgnore) : string {
            return null === $valueToIgnore ? 'IS NOT NULL' : '!= ' . $valueToIgnore;
        }, $valuesToIgnore);
    }

    /**
     * @return string[]
     */
    public function values() : array
    {
        return $this->valuesToIgnore;
    }
}
