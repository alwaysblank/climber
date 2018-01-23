<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
namespace Livy\Climber;

use \PHPUnit\Framework\TestCase;

include_once 'shims.php';
// phpcs:enable

class StructureTest extends TestCase
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
    }

    public function testShims()
    {
        $this->assertTrue(is_array($this->source), 'Menu is not an array.');
    }

    public function testChildren()
    {
        $test = new Climber($this->source);
        $this->assertContains('Portland', $test->tree[0]->children[0]->title);
        $this->assertContains('OSU', $test->tree[0]->children[1]->children[0]->title);
    }
}
