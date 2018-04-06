<?php

namespace Livy\Climber\Spotter;

/**
 * Simple allows you to pass a simple set of arrays directly to a Spotter. In theory, this makes it easy to use any
 * system with Climber, without creating a custom Spotter.
 *
 * @package Livy\Climber\Spotter
 */
class Simple extends Spotter
{
    public $expected = array(
        'id',
        'parent',
        'order',
        'target',
        'name',
    );

    protected function soil()
    {
        $temp = array();

        $order = 0;
        foreach ($this->seed as $item) {
            if (isset($item['id'])) {
                $order++;
                $temp[$item['id']] = array();
                foreach ($this->expected as $field) {
                    if (isset($item[$field])) {
                        $temp[$item['id']][$field] = $item[$field];
                    } elseif ('parent' === $field) {
                        // Parent is required to be set, but can be null (no parent)
                        $temp[$item['id']][$field] = null;
                    }
                }
            }
        }

        return $temp;
    }
}
