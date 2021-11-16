<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ILIAS\Mail\Setup\Objective\MetricsCollectedObjective;
use ILIAS\Setup\Metrics\Storage;
use ILIAS\Setup\Environment;
use ILIAS\Setup\Metrics\Metric;

class ilMailSetupObjectiveMetricsCollectedObjectiveTest extends TestCase // \ilMailBaseTest
{
    public function testConstruct() : void
    {
        $mockStorage = $this->getMockBuilder(Storage::class)->getMock();
        new MetricsCollectedObjective($mockStorage);
        $this->assertTrue(true);
    }

    public function testGetTentativePreconditions() : void
    {
        $mockEnvironment = $this->getMockBuilder(Environment::class)->getMock();
        $objective = new class extends MetricsCollectedObjective {
            public function __construct()
            {
            }

            public function testGetTentativePreconditions(Environment $environment) : array
            {
                return $this->getTentativePreconditions($environment);
            }
        };
        $preconditions = $objective->testGetTentativePreconditions($mockEnvironment);
        $this->assertEquals(2, \count($preconditions));
        $this->assertInstanceOf(\ilIniFilesLoadedObjective::class, $preconditions[0]);
        $this->assertInstanceOf(\ilDatabaseInitializedObjective::class, $preconditions[1]);
    }

    public function testCollectFrom() : void
    {
        $mockStatement = $this->getMockBuilder(\ilDBStatement::class)->getMock();
        $mockStorage = $this->getMockBuilder(Storage::class)->getMock();
        $mockStorage->expects(self::once())->method('store')->willReturnCallback(static function (string $key, Metric $metric) use (&$result) : void {
            $result = [$key, $metric];
        });
        $mockDatabase = $this->getMockBuilder(\ilDBInterface::class)->getMock();
        $mockDatabase->expects(self::exactly(12))->method('query')->willReturn($mockStatement);
        $mockDatabase->expects(self::exactly(12))->method('fetchAssoc')->willReturnOnConsecutiveCalls(
            ['count(1)' => 2],
            ['count(1)' => 2],
            ['count(1)' => 2],
            ['count(1)' => 2],
            ['count(1)' => 2],
            ['count(1)' => 2],
            ['count(1)' => 0], // <- this one should not produce a metric
            ['count(1)' => 2],
            ['count(1)' => 2],
            ['count(1)' => 2],
            ['count(1)' => 2],
            ['count(1)' => 2],

        );
        $mockEnvironment = $this->getMockBuilder(Environment::class)->getMock();
        $mockEnvironment->expects(self::once())->method('getResource')->with(Environment::RESOURCE_DATABASE)->willReturn($mockDatabase);
        $objective = new MetricsCollectedObjective($mockStorage);
        $this->assertEquals($mockEnvironment, $objective->achieve($mockEnvironment));
        $this->assertEquals('Database FK Violations', $result[0]);
        $this->assertInstanceOf(Metric::class, $result[1]);
        $this->assertEquals(Metric::STABILITY_STABLE, $result[1]->getStability());
        $this->assertEquals(Metric::TYPE_COLLECTION, $result[1]->getType());
        $this->assertTrue(\is_array($result[1]->getValue()));
        $this->assertEquals(11, \count($result[1]->getValue()));

        foreach ($result[1]->getValue() as $key => $metric) {
            $this->assertTrue(\is_string($key));
            $this->assertInstanceOf(Metric::class, $metric);
            $this->assertEquals(Metric::STABILITY_VOLATILE, $metric->getStability());
            $this->assertEquals(Metric::TYPE_GAUGE, $metric->getType());
            $this->assertEquals(2, $metric->getValue());
        }
    }
}
