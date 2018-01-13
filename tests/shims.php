<?php
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps

class WP_Post
// phpcs:enable
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
