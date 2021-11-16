<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ILIAS\Mail\Setup\Objective\Definition;
use ILIAS\Mail\Setup\Objective\Field;

class ilMailSetupObjectiveDefinitionTest extends TestCase
{
    public function testGetter() : void
    {
        $mockField = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $mockReferenceField = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $definition = new Definition($mockField, $mockReferenceField);
        $this->assertEquals($mockField, $definition->field());
        $this->assertEquals($mockReferenceField, $definition->referenceField());
        $this->assertEquals(false, $definition->nullable());
    }

    public function testNullable() : void
    {
        $mockField = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $mockReferenceField = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $definition = new Definition($mockField, $mockReferenceField, Definition::NULLABLE);
        $this->assertEquals(true, $definition->nullable());
    }
}
