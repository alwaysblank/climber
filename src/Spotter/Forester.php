<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 3/13/2018
 * Time: 6:14 PM
 */

namespace Livy\Climber\Spotter;

/**
 * Forester is a special kind of Spotter: It takes a Tree-like array, and returns it for consumption.
 * The point of this is for situations where Climber or Tree might want to generate a new tree with
 * some modifications.
 *
 * @package Livy\Climber\Spotter
 */
class Forester extends Spotter
{
    protected function soil()
    {
        $temp = [];
        foreach ($this->seed as $id => $data) {
            $temp[$id] = $this->convertToFormat($data);
        }

        return $temp;
    }

    protected function convertToFormat($data)
    {
        // No valid parent; can't process this.
        if (!isset($data[0]) && null !== $data[0]) {
            return false;
        }

        // No valid data slot; can't process this.
        if (isset($data[2]) && !is_array($data[2])) {
            return false;
        }

        return [
            'parent' => $data[0],
            'data'   => isset($data[2]) ? $data[2] : [],
        ];
    }
}
