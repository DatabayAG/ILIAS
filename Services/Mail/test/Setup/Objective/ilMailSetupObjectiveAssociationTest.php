<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ILIAS\Mail\Setup\Objective\Association;
use ILIAS\Mail\Setup\Objective\Field;

class ilMailSetupObjectiveAssociationTest extends TestCase
{
    public function testConstruct() : void
    {
        $mockField = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $this->assertInstanceOf(Association::class, new Association($mockField, $mockField));
    }

    public function testField() : void
    {
        $mockField = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $mockReferenceField = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $this->assertEquals($mockField, (new Association($mockField, $mockReferenceField))->field());
    }

    public function testReferenceField() : void
    {
        $mockField = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $mockReferenceField = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $this->assertEquals($mockReferenceField, (new Association($mockField, $mockReferenceField))->referenceField());
    }
}
