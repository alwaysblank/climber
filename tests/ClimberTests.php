<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
namespace Livy\Climber;

use \PHPUnit\Framework\TestCase;

include_once 'shims.php';
// phpcs:enable

class ClimberTest extends TestCase
{
    protected function setUp()
    {
        $this->tree = new Tree(new Spotter\WordPress(\WP_Data::get()));
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
        $expected = '<nav class="simpleMenu"><ul class="simpleMenu__menu level-0"><li class="simpleMenu__item"><a href="https://california.gov" class="simpleMenu__link">California</a></li><li class="simpleMenu__item"><a href="https://oregon.gov" class="simpleMenu__link">Oregon</a><ul class="simpleMenu__menu simpleMenu__menu--submenu level-1"><li class="simpleMenu__item"><a href="https://oregon.gov/portland" class="simpleMenu__link">Portland</a></li><li class="simpleMenu__item"><a href="https://oregon.gov/corvallis" class="simpleMenu__link">Corvallis</a><ul class="simpleMenu__menu simpleMenu__menu--submenu level-2"><li class="simpleMenu__item"><a href="https://oregon.gov/corvallis/osu" class="simpleMenu__link">OSU</a></li></ul></li></ul></li><li class="simpleMenu__item"><a href="https://iowa.gov" class="simpleMenu__link">Iowa</a></li></ul></nav>';
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

    public function testGetLeafByTarget()
    {
        $this->assertEquals(
            66,
            $this->test->getLeafByTarget('https://oregon.gov/corvallis/osu'),
            'Cannot correctly identify leaf by target.'
        );
    }
    
    public function testActivate()
    {
        $activateTest = new Climber(
            $this->tree,
            'https://oregon.gov/corvallis/osu'
        );
        $this->assertEquals(
            'current',
            $activateTest->tree->getLeafContent(66, 3),
            'Leaf 66 is not active with "current".'
        );
        $this->assertEquals(
            'parent',
            $activateTest->tree->getLeafContent(55, 3),
            'Leaf 55 is not active with "parent".'
        );
        $this->assertEquals(
            'ancestor',
            $activateTest->tree->getLeafContent(22, 3),
            'Leaf 2 is not active with "ancestor".'
        );
    }

    public function testActivateElement()
    {
        $activateElementTest = new Climber(
            $this->tree,
            'https://oregon.gov/corvallis/osu'
        );

        $this->assertEquals(
            // phpcs:disable Generic.Files.LineLength.TooLong
            '<nav class="simpleMenu"><ul class="simpleMenu__menu level-0"><li class="simpleMenu__item"><a href="https://california.gov" class="simpleMenu__link">California</a></li><li class="simpleMenu__item simpleMenu__item--ancestor"><a href="https://oregon.gov" class="simpleMenu__link">Oregon</a><ul class="simpleMenu__menu simpleMenu__menu--submenu simpleMenu__menu--active level-1"><li class="simpleMenu__item"><a href="https://oregon.gov/portland" class="simpleMenu__link">Portland</a></li><li class="simpleMenu__item simpleMenu__item--parent"><a href="https://oregon.gov/corvallis" class="simpleMenu__link">Corvallis</a><ul class="simpleMenu__menu simpleMenu__menu--submenu simpleMenu__menu--active level-2"><li class="simpleMenu__item simpleMenu__item--current"><a href="https://oregon.gov/corvallis/osu" class="simpleMenu__link">OSU</a></li></ul></li></ul></li><li class="simpleMenu__item"><a href="https://iowa.gov" class="simpleMenu__link">Iowa</a></li></ul></nav>',
            // phpcs:enable
            $activateElementTest->element(),
            'Menus were not properly acivated.'
        );
    }
}
