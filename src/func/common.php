<?php
use \Livy\Climber;

// phpcs:disable
$GLOBALS['livy_climber_helper_func_loaded']['common'] = true;
// phpcs:enable

/**
 * Returns a Climber object.
 *
 * @param Spotter/[ClassName] $spotter      An instance of a class that
 *                                          extends Spotter/Spotter.
 * @param string $currentUrl                The URL of the current page.
 * @return Climber|boolean
 */
function pulley__get_menu($spotter, string $currentUrl = null)
{
    if (is_subclass_of($spotter, 'Livy\\Climber\\Spotter\\Spotter')) {
        return new Climber\Climber(
            new Climber\Tree(
                $spotter
            ),
            $currentUrl
        );
    } else {
        return false;
    }
}

/**
 * Echoes a menu.
 *
 * @see pulley__getMenu()
 *
 * @param Spotter/[ClassName] $spotter      An instance of a class that
 *                                          extends Spotter/Spotter.
 * @param string $currentUrl                The URL of the current page.
 * @return string
 */
function pulley__menu($spotter, string $currentUrl = null)
{
    echo pulley__get_menu($spotter, $currentUrl);
}
