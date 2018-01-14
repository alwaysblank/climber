<?php

namespace Livy\Climber\Spotter;

class WordPress extends Spotter
{
    public $expected = [
    'ID' => 'id',
    'menu_item_parent' => 'parent',
    'menu_order' => 'order',
    'object_id' => 'target',
    'title' => 'name',
    ];

    protected function plant()
    {
        $temp = [];
        foreach ($this->seed as $item) {
            $temp[$item->ID] = [];
            
            // If there's no parent, `parent` should be null
            if ($item->menu_item_parent == 0) {
              $item->menu_item_parent = null;
            }

            foreach ($this->expected as $property => $rename) {
                $temp[$item->ID][$rename] = property_exists($item, $property) ? $item->$property : null;

            }
        }

        return $temp;
    }
}
