<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
namespace Livy\Climber;

use \PHPUnit\Framework\TestCase;

include_once 'shims.php';
// phpcs:enable

class TreeTest extends TestCase
{
    public $source;

    protected function setUp()
    {
        $this->test = new Tree(new Spotter\WordPress(\WP_Data::get()));
    }

    public function testShims()
    {
        $this->assertInstanceOf(Tree::class, $this->test, "The source is not a valid Tree.");
    }

    public function testProcessing()
    {
        $test = $this->test->grow();
        $this->assertEquals(22, $test[44][0]);
        $this->assertEmpty($test[44][1]);
        $this->assertEquals('Portland', $test[44][2]['name']);
    }

    public function testGetLeaf()
    {
        $valid = [
            null,
            [],
            [
                'id' => 33,
                'parent' => null,
                'order' => 1,
                'target' => 'https://california.gov',
                'name' => 'California',
            ]
        ];
        $this->assertEquals($this->test->getLeaf(33), $valid);
    }

    public function testGetLeafContent()
    {
        $this->assertEquals([], $this->test->getLeafContent(33, 1));
        $this->assertEquals(1, $this->test->getLeafContent(33, 'data', 'order'));
        $this->assertNull($this->test->getLeafContent(33, 5));
        $this->assertNull($this->test->getLeafContent(33, 'data', 'nothing'));
        $this->assertNull($this->test->getLeafContent(33, 'chilren', 'nothing'));
    }

    public function testChildrenPath()
    {
        $this->assertEquals([22, 55], $this->test->getLeafPath(66), "Did not find correct ancestors (should be two of them).");
        $this->assertEquals([], $this->test->getLeafPath(22), "Did not correctly return no ancestors (i.e. an empty array)");
    }
    
    public function testChildrenSiblings()
    {
        $leaf44siblings = $this->test->getLeafSiblings(44);
        $this->assertTrue(
            isset($leaf44siblings[44]) && isset($leaf44siblings[55])
        );
    
        $leaf44siblings_exclude = $this->test->getLeafSiblings(44, true);
        $this->assertTrue(
            !isset($leaf44siblings_exclude[44]) && isset($leaf44siblings_exclude[55])
        );
    }

    public function testSetLeafProp()
    {
        $this->test->setLeafProp(44, 0, 22);
        $this->assertEquals(22, $this->test->getLeafContent(44, 0));
    }
}
