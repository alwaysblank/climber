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
            ],
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
        $this->assertEquals(
            [22, 55],
            $this->test->getLeafPath(66),
            "Did not find correct ancestors (should be two of them)."
        );
        $this->assertEquals(
            [],
            $this->test->getLeafPath(22),
            "Did not correctly return no ancestors (i.e. an empty array)"
        );
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
        $this->assertNotContains(
            22,
            $parentTest->getLeafContent(22, 1),
            "Target not removed from old parent's children."
        );
    }

    public function testSetChildren()
    {
        $childrenTest = $this->test;

        // Verify Initial state
        $this->assertEquals(
            22,
            $childrenTest->getLeafContent(55, 0),
            "Child 55 was does not have correct initial parent."
        );
        $this->assertNull(
            $childrenTest->getLeafContent(77, 0),
            "Child 77 does not have correct initial parent (none)."
        );
        $this->assertNotContains(55, $childrenTest->getLeafContent(33, 1), "Child 55 is already a child of parent 33.");
        $this->assertNotContains(77, $childrenTest->getLeafContent(33, 1), "Child 77 is already a child of parent 33.");

        $childrenTest->setLeafProp(33, 1, [55, 77]);

        // Verify change
        $this->assertContains(55, $childrenTest->getLeafContent(33, 1), "Child 55 was not moved.");
        $this->assertContains(77, $childrenTest->getLeafContent(33, 1), "Child 77 was not moved.");
        $this->assertEquals([33, 55], $childrenTest->getLeafPath(66), "Grandchild not in correct location.");
    }

    public function testSetData()
    {
        $dataTest = $this->test;

        $dataTest->setLeafProp(44, 2, ['name', 'New Carthage']);

        $this->assertEquals('New Carthage', $dataTest->getLeafContent(44, 2, 'name'));
    }

    public function testSetLeaf()
    {
        $leafTest = $this->test;

        $leafTest->setLeaf(
            55, // targeting leaf 55
            [3, 'current'], // make this active
            [2, ['name', 'New Rome']], // change the name
            [0, 33]// Make leaf 33 the new parent
        );

        $this->assertEquals('New Rome', $leafTest->getLeafContent(55, 2, 'name'));
        $this->assertEquals('current', $leafTest->getLeafContent(55, 3));
        $this->assertContains(55, $leafTest->getLeafContent(33, 1));
    }
}
