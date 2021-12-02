<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ILIAS\Mail\Setup\Objective\Association;
use ILIAS\Mail\Setup\Objective\Definition;
use ILIAS\Mail\Setup\Objective\Field;
use ILIAS\Mail\Setup\Objective\Ignore;

class ilMailSetupObjectiveDefinitionTest extends TestCase
{
    public function testGetter() : void
    {
        $mockAssociation = $this->getMockBuilder(Association::class)->disableOriginalConstructor()->getMock();
        $definition = new Definition([$mockAssociation]);
        $this->assertEquals(1, \count($definition->associations()));
        $this->assertEquals($mockAssociation, $definition->associations()[0]);
    }

    public function testIgnoreValues() : void
    {
        $mockIgnore = $this->getMockBuilder(Ignore::class)->getMock();
        $mockIgnore->expects(self::once())->method('values')->willReturn(['hejaa']);
        $mockAssociation = $this->getMockBuilder(Association::class)->disableOriginalConstructor()->getMock();
        $definition = new Definition([$mockAssociation], $mockIgnore);
        $this->assertEquals(1, \count($definition->ignoreValues()));
    }

    public function testEmptyAssociations() : void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Definition([]);
    }

    public function testInconsistentTables() : void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Definition([
            $this->createAssociation('a', 'c'),
            $this->createAssociation('b', 'c'),
        ]);
    }

    public function testInconsistentReferenceTable() : void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Definition([
            $this->createAssociation('a', 'b'),
            $this->createAssociation('a', 'c'),
        ]);
    }

    public function testTableName() : void
    {
        $definition = new Definition([
            $this->createAssociation('a', 'b'),
            $this->createAssociation('a', 'b'),
        ]);
        $this->assertEquals('a', $definition->tableName());
        $this->assertEquals('b', $definition->referenceTableName());
    }

    private function createAssociation(string $tableName, $referenceTableName) : Association
    {
        $mockField = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $mockField->expects(self::any())->method('tableName')->willReturn($tableName);

        $mockReferenceField = $this->getMockBuilder(Field::class)->disableOriginalConstructor()->getMock();
        $mockReferenceField->expects(self::any())->method('tableName')->willReturn($referenceTableName);

        $mockAssociation = $this->getMockBuilder(Association::class)->disableOriginalConstructor()->getMock();
        $mockAssociation->expects(self::any())->method('field')->willReturn($mockField);
        $mockAssociation->expects(self::any())->method('referenceField')->willReturn($mockReferenceField);

        return $mockAssociation;
    }
}
