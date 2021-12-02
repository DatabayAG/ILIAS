<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ILIAS\Mail\Setup\Objective\Ignore;

class ilMailSetupObjectiveIgnoreTest extends TestCase
{
    public function testConstruct() : void
    {
        $this->assertInstanceOf(Ignore::class, new Ignore());
    }

    public function testValues() : void
    {
        $this->assertEquals(['IS NOT NULL', '!= 3', '!= "heja"'], (new Ignore(null, '3', '"heja"'))->values());
    }
}
