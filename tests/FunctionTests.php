<?php // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

namespace Livy\Climber;

use \PHPUnit\Framework\TestCase;

/**
 * Force reload of function loader, so that it will pick up on our fake
 * functions and allow us to correctly test for context-specific functions.
 */

include dirname(__FILE__) . '/../src/func/function_loader.php';
// phpcs:enable

class FunctionTest extends TestCase
{
    protected function setUp()
    {
        $this->spotter = new Spotter\WordPress(
            \WP_Data::get()
        );
        $this->tree = new Tree($this->spotter);
        $this->test = new Climber($this->tree);
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
            __NAMESPACE__.'\\Climber',
            \pulley__get_menu($this->spotter)
        );
    }

    public function testFunctionCommonEcho()
    {
        $this->expectOutputString(\Storage::$MenuStringExpected);
        \pulley__menu($this->spotter);
    }

    /**
     * WordPress helpers.
     */

    public function testFunctionsWordPressLoad()
    {
        $this->assertTrue(
            function_exists('wp_get_nav_menu_items'),
            "`wp_get_nav_menu_items` does not exist."
        );
        $this->assertTrue(
            isset($GLOBALS['livy_climber_helper_func_loaded']['wp']),
            "WP helper global not set."
        );
        $this->assertTrue(
            $GLOBALS['livy_climber_helper_func_loaded']['wp'],
            "WP helper global not true."
        );
    }

    public function testFunctionsWordPressGet()
    {
        $this->assertInstanceOf(
            __NAMESPACE__.'\\Climber',
            \pulley__wp_get_menu(1),
            "WP helper cannot get menu."
        );
    }

    public function testFunctionWordPressEcho()
    {
        $this->expectOutputString(\Storage::$MenuStringExpected);
        \pulley__wp_menu(1);
    }
    
    public function testFunctionsWordPressGetByLocation()
    {
        $this->assertInstanceOf(
            __NAMESPACE__.'\\Climber',
            \pulley__wp_get_menu_by_location('primary_navigation'),
            "WP helper cannot get menu by location."
        );
    }

    public function testFunctionWordPressEchoByLocation()
    {
        $this->expectOutputString(\Storage::$MenuStringExpected);
        \pulley__wp_menu_by_location('primary_navigation');
    }
    
    public function testFunctionWordPressEchoActivated()
    {
        $this->expectOutputString(\Storage::$ActivatedMenuStringExpected);
        \pulley__wp_menu(1, 'https://oregon.gov/corvallis/osu');
    }
    
    public function testFunctionWordPressEchoByLocationActivated()
    {
        $this->expectOutputString(\Storage::$ActivatedMenuStringExpected);
        \pulley__wp_menu_by_location(
            'primary_navigation',
            'https://oregon.gov/corvallis/osu'
        );
    }
}
