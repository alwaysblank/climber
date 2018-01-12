<?php namespace Livy\Climber;

use \PHPUnit\Framework\TestCase;

include_once 'shims.php';

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
    }

    public function testShims()
    {
        $this->assertTrue(is_array($this->source), 'Menu is not an array.');
    }
    
    public function testBasicMenu()
    {
        $expected = '<nav class="simpleMenu" ><ul class="simpleMenu__menu level-0" ><li class="simpleMenu__item" ><a href="/111" class="simpleMenu__link" >Oregon</a><ul class="simpleMenu__menu simpleMenu__menu--submenu level-1" ><li class="simpleMenu__item" ><a href="/333" class="simpleMenu__link" >Portland</a></li><li class="simpleMenu__item" ><a href="/444" class="simpleMenu__link" >Corvallis</a><ul class="simpleMenu__menu simpleMenu__menu--submenu level-2" ><li class="simpleMenu__item" ><a href="/555" class="simpleMenu__link" >OSU</a></li></ul></li></ul></li><li class="simpleMenu__item" ><a href="/222" class="simpleMenu__link" >California</a></li><li class="simpleMenu__item" ><a href="/666" class="simpleMenu__link" >Iowa</a></li></ul></nav>';
        
        $test = new Climber($this->source);
        $this->assertEquals($expected, $test->element(), "Simple menu HTML does not match.");
    }

    /**
     * @depends testBasicMenu
     */
    public function testSetClass()
    {
        $test = new Climber($this->source);

        $test->topClass = "$test->topClass newTop";
        $this->assertNotFalse(strpos($test->element(), "newTop"), 'Cannot set `$topClass`.');

        $test->menuClass = "$test->menuClass newMenu";
        $this->assertNotFalse(strpos($test->element(), "newMenu"), 'Cannot set `$menuClass`.');

        $test->itemClass = "$test->itemClass newItem";
        $this->assertNotFalse(strpos($test->element(), "newItem"), 'Cannot set `$itemClass`.');

        $test->linkClass = "$test->linkClass newLink";
        $this->assertNotFalse(strpos($test->element(), "newLink"), 'Cannot set `$linkClass`.');
    }

    /**
     * @depends testBasicMenu
     */
    public function testSetAttrs()
    {
        $test = new Climber($this->source);

        $test->topAttr = ['data-top', 'new top'];
        $this->assertNotFalse(strpos($test->element(), 'data-top="new top"'), 'Cannot set `$topAttr`.');

        $test->menuAttr = ['data-menu', 'new menu'];
        $this->assertNotFalse(strpos($test->element(), 'data-menu="new menu"'), 'Cannot set `$menuAttr`.');

        $test->itemAttr = ['data-item', 'new item'];
        $this->assertNotFalse(strpos($test->element(), 'data-item="new item"'), 'Cannot set `$itemAttr`.');

        $test->linkAttr = ['data-link', 'new link'];
        $this->assertNotFalse(strpos($test->element(), 'data-link="new link"'), 'Cannot set `$linkAttr`.');
    }

    /**
     * @depends testBasicMenu
     */
    public function testSetHooks()
    {
        $test = new Climber($this->source);

        $test->hook('top', function($nav) {
            $nav['class'] = $nav['class'] . ' topHooked';
            return $nav;
        });
        $this->assertNotFalse(strpos($test->element(), ' topHooked'), 'Cannot hook "top".');

        $test->hook('menu', function($nav) {
            $nav['class'] = $nav['class'] . ' menuHooked';
            return $nav;
        });
        $this->assertNotFalse(strpos($test->element(), ' menuHooked'), 'Cannot hook "menu".');

        $test->hook('item', function($nav) {
            $nav['class'] = $nav['class'] . ' itemHooked';
            return $nav;
        });
        $this->assertNotFalse(strpos($test->element(), ' itemHooked'), 'Cannot hook "item".');

        $test->hook('link', function($nav) {
            $nav['class'] = $nav['class'] . ' linkHooked';
            return $nav;
        });
        $this->assertNotFalse(strpos($test->element(), ' linkHooked'), 'Cannot hook "link".');
    }
}