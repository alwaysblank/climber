<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps, PSR1.Classes.ClassDeclaration.MultipleClasses

class WP_Post
{

    public $ID;
    public $title;
    public $menu_item_parent;
    public $menu_order;
    public $object_id;

    public function __construct($args)
    {
        foreach ($args as $key => $value) {
            $this->$key = $value;
        }
    }
}

class WP_Data
{
    public $data;
    
    public function __construct()
    {
        $this->data = [
            0 => new \WP_Post([
                'ID' => 22,
                'url' => 'https://oregon.gov',
                'title' => "Oregon",
                'menu_item_parent' => 0,
                'menu_order' => 2,
            ]),
            1 => new \WP_Post([
                'ID' => 33,
                'url' => 'https://california.gov',
                'title' => "California",
                'menu_item_parent' => 0,
                'menu_order' => 1,
            ]),
            2 => new \WP_Post([
                'ID' => 44,
                'url' => 'https://oregon.gov/portland',
                'title' => "Portland",
                'menu_item_parent' => 22,
                'menu_order' => 3,
            ]),
            3 => new \WP_Post([
                'ID' => 55,
                'url' => 'https://oregon.gov/corvallis',
                'title' => "Corvallis",
                'menu_item_parent' => 22,
                'menu_order' => 4,
            ]),
            4 => new \WP_Post([
                'ID' => 66,
                'url' => 'https://oregon.gov/corvallis/osu',
                'title' => "OSU",
                'menu_item_parent' => 55,
                'menu_order' => 5,
            ]),
            5 => new \WP_Post([
                'ID' => 77,
                'url' => 'https://iowa.gov',
                'title' => "Iowa",
                'menu_item_parent' => 0,
                'menu_order' => 6,
            ]),
        ];
    }

    public static function get()
    {
        $instance = new static();
        return $instance->data;
    }
}

/**
 * This pretends to be the actual function. If it is given a valid `$menu`, it
 * returns (always the same) faked menu data.
 *
 * @param integer|string|WP_Term $menu
 * @return array|false
 */
function wp_get_nav_menu_items($menu) {
    if (is_string($menu) || is_integer($menu) || is_a($menu, 'WP_Term')) {
        return WP_Data::get();
    }

    return false;
}

/**
 * This pretends to be the actual function. It just returns an array that can
 * be successfully passed to `get_term()`.
 *
 * @return array
 */
function get_nav_menu_locations()
{
    return ['primary_navigation' => 1];
}

class WP_Term {
    // Nobody here but us chickens.
}

/**
 * This pretends to be the actual function. It just returns an object that will
 * validated as `WP_Term` object.
 *
 * @param integer $integer
 * @param string $type
 * @return void
 */
function get_term($integer, $type)
{
    if (is_integer($integer) && is_string($type)) {
        return new WP_Term;
    }

    return false;
}

// phpcs:enable