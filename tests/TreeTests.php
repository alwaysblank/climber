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

    public function testChildrenPath()
    {
        $this->assertEquals([22, 55], $this->test->getLeafPath(66));
        $this->assertEquals([], $this->test->getLeafPath(22));
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
}
