<?php

/**
 * These functions make it a bit easier to use Climber! Specifically, they can
 * help your code look a bit simpler. They are just wrappers for the class-based
 * stuff that Climber, Tree, and Spotter do—they don't add additional
 * functionality, just make it a bit easier to use!
 *
 * # Namespace
 *
 * Helper functions aren't *mechanically* namespaced, but they all start with
 * `pulley__`, to help make them recognizable and prevent collision. I chose
 * "pulley" because pullies are a recognizable piece of gear that people use
 * to climb trees!
 *
 * # Naming Convestions
 *
 * Wherever it makes sense, helper functions follow a convention where function
 * names that include `get` will return a Climber object, and function names
 * that do not will echo a string. This convention is borrowed from WordPress.
 *
 * # Contexts
 *
 * This loader will attempt to only load files containing functions for the
 * software you are executing it from within. For instance, if you aren't
 * running Climber in WordPress, there's no need to load the WordPress helper
 * functions.
 */

// Know whether or not we loaded these files.
$GLOBALS['livy_climber_helper_func_loaded']['base'] = true;

include_once(dirname(__FILE__) . '/common.php');

/**
 * Only load WordPress functions of we believe WordPress exists in this context.
 */
if (function_exists('wp_get_nav_menu_items')) {
    include_once(dirname(__FILE__) . '/wp.php');
}
