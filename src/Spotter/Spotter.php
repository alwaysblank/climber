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
        $this->sprout = $this->plant();
        return $this->sprout;
    }
}
