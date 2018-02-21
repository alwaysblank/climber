<?php

namespace Livy\Climber\API;

/**
 * This defines the API for the Livy\Spotter to help insure consistent behavior
 * in future versions of the software.
 * @package Livy\Climber\API
 */
interface SpotterAPI
{
    /**
     * Sets up a Spotter from a seed.
     *
     * The seed can be whatever the extending Spotter class requires;
     * it might be an array, an object, or just a string or integer that
     * the spotter uses to get what it needs.
     *
     * @param mixed $seed
     */
    public function __construct($seed);

    /**
     * If we have a good sprout, plant it. Otherwise, return null.
     *
     * @return mixed
     */
    public function germinate();
}