<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
namespace Livy\Climber;

use \PHPUnit\Framework\TestCase;

include_once 'shims.php';
// phpcs:enable

class TreeTest extends TestCase
{
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
        $this->assertNull($this->test->getLeafContent(33, 'children', 'nothing'));
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

    public function testSetParent()
    {
        $parentTest = $this->test;
        // Verifies initial state
        $this->assertEquals(22, $parentTest->getLeafContent(55, 0), "Initial parent not set correctly.");
        $this->assertContains(55, $parentTest->getLeafContent(22, 1), "Initial child not set correctly.");
        $this->assertNotContains(55, $parentTest->getLeafContent(77, 1), "Initial future child not set correctly");

        $parentTest->setLeafProp(55, 0, 77); // set parent of 55 to 77

        // Verify change
        $this->assertEquals(77, $parentTest->getLeafContent(55, 0), "Target parent not changed.");
        $this->assertContains(55, $parentTest->getLeafContent(77, 1), "Target not added to new parent's children.");
        $this->assertNotContains(22, $parentTest->getLeafContent(22, 1), "Target not removed from old parent's children.");
    }
}
