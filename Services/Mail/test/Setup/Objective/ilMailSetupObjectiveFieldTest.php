<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ILIAS\Mail\Setup\Objective\Field;

class ilMailSetupObjectiveFieldTest extends TestCase
{
    public function testAllWithoutAlias() : void
    {
        $field = new Field('my_cool_table', 'my_cooler_field');

        $this->assertEquals('my_cool_table', $field->tableName());
        $this->assertEquals('my_cool_table.my_cooler_field', $field->fieldName());
        $this->assertEquals('my_cooler_field', $field->rawFieldName());
    }

    public function testAllWithAlias() : void
    {
        $field = new Field('my_cool_table', 'my_cooler_field', 'myalias');

        $this->assertEquals('my_cool_table as myalias', $field->tableName());
        $this->assertEquals('myalias.my_cooler_field', $field->fieldName());
        $this->assertEquals('my_cooler_field', $field->rawFieldName());
        $this->assertEquals('my_cool_table', $field->rawTableName());
    }
}
