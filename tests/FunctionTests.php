<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
use \PHPUnit\Framework\TestCase;
// phpcs:enable

class ClimberTest extends TestCase
{
    protected function setUp()
    {
        $this->spotter = new Livy\Climber\Spotter\WordPress(
            \WP_Data::get()
        );
        $this->tree = new Livy\Climber\Tree($this->spotter);
        $this->test = new Livy\Climber\Climber($this->tree);
    }

    public function testFunctionsLoad()
    {
        $this->assertTrue(isset($GLOBALS['livy_climber_helper_func_loaded']['base']));
        $this->assertTrue($GLOBALS['livy_climber_helper_func_loaded']['base']);
    }

    /**
     * Common helpers.
     */

    public function testFunctionsCommonLoad()
    {
        $this->assertTrue(isset($GLOBALS['livy_climber_helper_func_loaded']['common']));
        $this->assertTrue($GLOBALS['livy_climber_helper_func_loaded']['common']);
    }

    public function testFunctionsCommonGet()
    {
        $this->assertInstanceOf(
            'Livy\Climber\Climber', 
            pulley__get_menu($this->spotter)
        );
    }

    public function testFunctionCommonEcho()
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = '<nav class="simpleMenu"><ul class="simpleMenu__menu level-0"><li class="simpleMenu__item"><a href="https://california.gov" class="simpleMenu__link">California</a></li><li class="simpleMenu__item"><a href="https://oregon.gov" class="simpleMenu__link">Oregon</a><ul class="simpleMenu__menu simpleMenu__menu--submenu level-1"><li class="simpleMenu__item"><a href="https://oregon.gov/portland" class="simpleMenu__link">Portland</a></li><li class="simpleMenu__item"><a href="https://oregon.gov/corvallis" class="simpleMenu__link">Corvallis</a><ul class="simpleMenu__menu simpleMenu__menu--submenu level-2"><li class="simpleMenu__item"><a href="https://oregon.gov/corvallis/osu" class="simpleMenu__link">OSU</a></li></ul></li></ul></li><li class="simpleMenu__item"><a href="https://iowa.gov" class="simpleMenu__link">Iowa</a></li></ul></nav>';
        // phpcs:enable

        $this->assertEquals($expected, pulley__get_menu($this->spotter), "Menu strings do not match.");
    }

    /**
     * WordPress helpers.
     */

    // public function testFunctionsWordPressLoad()
    // {
    //     $this->assertTrue(
    //         function_exists('wp_get_nav_menu_items'),
    //         "`wp_get_nav_menu_items` does not exist."
    //     );
    //     $this->assertTrue(
    //         isset($GLOBALS['livy_climber_helper_func_loaded']['wp']),
    //         "WP helper global not set."
    //     );
    //     $this->assertTrue(
    //         $GLOBALS['livy_climber_helper_func_loaded']['wp'],
    //         "WP helper global not true."
    //     );
    // }
}