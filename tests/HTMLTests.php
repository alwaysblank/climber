<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
namespace Livy\Climber;

use \PHPUnit\Framework\TestCase;

include_once 'shims.php';
// phpcs:enable

class HTMLTest extends TestCase
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

        $this->tree = new Tree(new Spotter\WordPress($this->source));
        $this->test = new Climber($this->tree);
    }

    public function testShims()
    {
        $this->assertInstanceOf(Tree::class, $this->tree, "The source is not a valid Tree.");
        $this->assertInstanceOf(Climber::class, $this->test, "The test item is not a valid Climber.");
    }
    
    public function testBasicMenu()
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = '<nav class="simpleMenu" ><ul class="simpleMenu__menu level-0" ><li class="simpleMenu__item" ><a href="/111" class="simpleMenu__link" >Oregon</a><ul class="simpleMenu__menu simpleMenu__menu--submenu level-1" ><li class="simpleMenu__item" ><a href="/333" class="simpleMenu__link" >Portland</a></li><li class="simpleMenu__item" ><a href="/444" class="simpleMenu__link" >Corvallis</a><ul class="simpleMenu__menu simpleMenu__menu--submenu level-2" ><li class="simpleMenu__item" ><a href="/555" class="simpleMenu__link" >OSU</a></li></ul></li></ul></li><li class="simpleMenu__item" ><a href="/222" class="simpleMenu__link" >California</a></li><li class="simpleMenu__item" ><a href="/666" class="simpleMenu__link" >Iowa</a></li></ul></nav>';
        // phpcs:enable

        $this->assertEquals($expected, $this->test->element(), "Simple menu HTML does not match.");
    }

    /**
     * @depends testBasicMenu
     */
    public function testSetClass()
    {
        $this->test->topClass = "{$this->test->topClass} newTop";
        $this->assertNotFalse(strpos($this->test->element(), "newTop"), 'Cannot set `$topClass`.');

        $this->test->menuClass = "{$this->test->menuClass} newMenu";
        $this->assertNotFalse(strpos($this->test->element(), "newMenu"), 'Cannot set `$menuClass`.');

        $this->test->itemClass = "{$this->test->itemClass} newItem";
        $this->assertNotFalse(strpos($this->test->element(), "newItem"), 'Cannot set `$itemClass`.');

        $this->test->linkClass = "{$this->test->linkClass} newLink";
        $this->assertNotFalse(strpos($this->test->element(), "newLink"), 'Cannot set `$linkClass`.');
    }

    /**
     * @depends testBasicMenu
     */
    public function testSetAttrs()
    {
        $this->test->topAttr = ['data-top', 'new top'];
        $this->assertNotFalse(strpos($this->test->element(), 'data-top="new top"'), 'Cannot set `$topAttr`.');

        $this->test->menuAttr = ['data-menu', 'new menu'];
        $this->assertNotFalse(strpos($this->test->element(), 'data-menu="new menu"'), 'Cannot set `$menuAttr`.');

        $this->test->itemAttr = ['data-item', 'new item'];
        $this->assertNotFalse(strpos($this->test->element(), 'data-item="new item"'), 'Cannot set `$itemAttr`.');

        $this->test->linkAttr = ['data-link', 'new link'];
        $this->assertNotFalse(strpos($this->test->element(), 'data-link="new link"'), 'Cannot set `$linkAttr`.');
    }

    /**
     * @depends testBasicMenu
     */
    public function testSetHooks()
    {
        $this->test->hook('top', function ($nav) {
            $nav['class'] = $nav['class'] . ' topHooked';
            return $nav;
        });
        $this->assertNotFalse(strpos($this->test->element(), ' topHooked'), 'Cannot hook "top".');

        $this->test->hook('menu', function ($nav) {
            $nav['class'] = $nav['class'] . ' menuHooked';
            return $nav;
        });
        $this->assertNotFalse(strpos($this->test->element(), ' menuHooked'), 'Cannot hook "menu".');

        $this->test->hook('item', function ($nav) {
            $nav['class'] = $nav['class'] . ' itemHooked';
            return $nav;
        });
        $this->assertNotFalse(strpos($this->test->element(), ' itemHooked'), 'Cannot hook "item".');

        $this->test->hook('link', function ($nav) {
            $nav['class'] = $nav['class'] . ' linkHooked';
            return $nav;
        });
        $this->assertNotFalse(strpos($this->test->element(), ' linkHooked'), 'Cannot hook "link".');
    }
}
