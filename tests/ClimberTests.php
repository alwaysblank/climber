<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
namespace Livy\Climber;

use \PHPUnit\Framework\TestCase;

// phpcs:enable

class ClimberTest extends TestCase
{
    /**
     * @var Tree
     */
    protected $tree;
    /**
     * @var Climber
     */
    protected $test;

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
        $this->assertEquals(
            \Storage::$MenuStringExpected,
            $this->test->element(),
            "Simple menu HTML does not match."
        );
    }

    public function testNoMenu()
    {
        $nullMenu  = new Climber(new Tree(new Spotter\WordPress(null)));
        $falseMenu = new Climber(new Tree(new Spotter\WordPress(false)));
        $this->assertNull($nullMenu->element());
        $this->assertNull($falseMenu->element());
    }

    /**
     * @depends testBasicMenu
     */
    public function testSetClass()
    {
        $this->test->topClass .= " newTop";
        $this->assertNotFalse(strpos($this->test->element(), "newTop"), 'Cannot set `$topClass`.');

        $this->test->menuClass .= " newMenu";
        $this->assertNotFalse(strpos($this->test->element(), "newMenu"), 'Cannot set `$menuClass`.');

        $this->test->itemClass .= " newItem";
        $this->assertNotFalse(strpos($this->test->element(), "newItem"), 'Cannot set `$itemClass`.');

        $this->test->linkClass .= " newLink";
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

    public function testRemoveAttr()
    {
        $this->test->topAttr = ['data-star', 'wars'];
        $this->assertNotFalse(strpos($this->test->element(), 'data-star="wars"'), 'Attrs are not properly added.');

        $this->test->topAttr = ['data-star', false];
        $this->assertFalse(strpos($this->test->element(), 'data-star'), 'Attrs are not properly removed.');
    }

    public function testOverrideAttr()
    {
        $this->test->topAttr = ['data-star', 'wars'];
        $this->assertNotFalse(strpos($this->test->element(), 'data-star="wars"'), 'Attrs are not properly added.');

        $this->test->topAttr = ['data-star', 'trek'];
        $this->assertNotFalse(strpos($this->test->element(), 'data-star="trek"'), 'Attrs are not properly added.');
        $this->assertFalse(strpos($this->test->element(), 'data-star="wars"'), 'Attrs are not properly added.');
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
        $this->assertContains(
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
            \Storage::$ActivatedMenuStringExpected,
            $activateElementTest->element(),
            'Menus were not properly activated.'
        );
    }

//    public function testSubTree()
//    {
//        $fullTree = new Climber(
//            $this->tree,
//            'https://oregon.gov/corvallis/osu'
//        );
//        $subTree = $fullTree->sub()
//    }
}
