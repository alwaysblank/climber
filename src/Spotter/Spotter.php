<?php

namespace Livy\Climber\Spotter;

abstract class Spotter
{
    protected $seed;
    protected $sprout;

    public function __construct($seed)
    {
        $this->seed = $seed;
    }

    abstract protected function plant();

    public function germinate()
    {
        // Only pass this on if it's a valid seed.
        $this->sprout = $this->seed ? $this->plant() : null;
        return $this->sprout;
    }
}
