<?php
use \Livy\Climber;

// phpcs:disable
$GLOBALS['livy_climber_helper_func_loaded']['wp'] = true;
// phpcs:enable

/**
 * This is a set of helper functions for WordPress!
 *
 * @link https://developer.wordpress.org/reference/functions/wp_get_nav_menu_items/
 * @link https://developer.wordpress.org/reference/functions/get_nav_menu_locations/
 * @link https://developer.wordpress.org/reference/functions/get_term/
 */

/**
 * Get a Climber.
 *
 * The `$menu` argument accepts any value that you would pass to
 * `wp_get_nave_menu_items()`.
 *
 * @param int|string|WP_Term $menu
 * @param string $currentUrl
 * @return Climber
 */
function pulley__wp_get_menu($menu, string $currentUrl = null)
{
    return pulley__get_menu(
        new Climber\Spotter\WordPress(wp_get_nav_menu_items($menu)),
        $currentUrl
    );
}

/**
 * Echos a generated menu.
 *
 * @see pulley__wp_get_menu()
 *
 * @param int|string|WP_Term $menu
 * @param string $currentUrl
 * @return void
 */
function pulley__wp_menu($menu, string $currentUrl = null)
{
    echo pulley__wp_get_menu($menu, $currentUrl);
}

/**
 * Get a Climber, and specify the menu you want by location (instead of ID,
 * etc).
 *
 * @see pulley__wp_get_menu()
 *
 * @param string $location
 * @param string $currentUrl
 * @return Climber
 */
function pulley__wp_get_menu_by_location(
    string $location,
    string $currentUrl = null
) {
    $allLocations = get_nav_menu_locations();
    if (isset($allLocations[$location])) {
        return pulley__wp_get_menu(
            get_term($allLocations[$location], 'nav_menu'),
            $currentUrl
        );
    }

    return false;
}

/**
 * Echo a menu, and specify the menu you want by location (instead of ID, etc).
 *
 * @see pulley__wp_get_menu_by_location()
 *
 * @param string $location
 * @param string $currentUrl
 * @return void
 */
function pulley__wp_menu_by_location(
    string $location,
    string $currentUrl = null
) {
    echo pulley__wp_get_menu_by_location($location, $currentUrl);
}
