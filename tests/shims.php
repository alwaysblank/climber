<?php 

class WP_Post {

    public $ID;
    public $title;
    public $menu_item_parent;
    public $menu_order;

    public function __construct($args)
    {
        foreach ($args as $key => $value) {
            $this->$key = $value;
        }
    }
}