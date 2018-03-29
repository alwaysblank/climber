<?php

namespace Livy\Climber\Spotter;

use Livy\Climber\API\SpotterAPI;

abstract class Spotter implements SpotterAPI
{
    protected $seed;
    protected $sprout;

    public function __construct($seed)
    {
        $this->seed = $seed;
    }

    public function germinate()
    {
        // Only pass this on if it's a valid seed.
        $this->sprout = $this->seed ? $this->soil() : null;
        return $this->sprout;
    }

    abstract protected function soil();
}
