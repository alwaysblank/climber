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
        $this->source = [
            0 => new \WP_Post([
              'ID' => 22,
              'object_id' => 111,
              'title' => "Oregon",
              'menu_item_parent' => 0,
              'menu_order' => 1,
            ]),
            1 => new \WP_Post([
              'ID' => 33,
              'object_id' => 222,
              'title' => "California",
              'menu_item_parent' => 0,
              'menu_order' => 2,
            ]),
            2 => new \WP_Post([
              'ID' => 44,
              'object_id' => 333,
              'title' => "Portland",
              'menu_item_parent' => 22,
              'menu_order' => 3,
            ]),
            3 => new \WP_Post([
              'ID' => 55,
              'object_id' => 444,
              'title' => "Corvallis",
              'menu_item_parent' => 22,
              'menu_order' => 4,
            ]),
            4 => new \WP_Post([
              'ID' => 66,
              'object_id' => 555,
              'title' => "OSU",
              'menu_item_parent' => 55,
              'menu_order' => 5,
            ]),
            5 => new \WP_Post([
              'ID' => 77,
              'object_id' => 666,
              'title' => "Iowa",
              'menu_item_parent' => 0,
              'menu_order' => 6,
            ]),
        ];

        $this->test = new Tree(new Spotter\WordPress($this->source));
    }

    public function testShims()
    {
        $this->assertTrue(is_array($this->source), 'Menu is not an array.');
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
