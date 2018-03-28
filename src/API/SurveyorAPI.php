<?php

namespace Livy\Climber\API;

/**
 * This defines the API for the Livy\Surveyor to help insure consistent behavior
 * in future versions of the software.
 * @package Livy\Climber\API
 */
interface SurveyorAPI
{
    /**
     * Surveyor constructor.
     *
     * Surveyor expects to get an array of arrays. The first item in each array is a regex to be matched against the
     * current url. The second item is the URL that should ultimately be passed to Climber as the "Active Url".
     *
     * Routes are processed in the order they are passed, and are exclusive: Once something matches, then Surveyor stops
     * evaluating and returns that match.
     *
     * @param array $routes
     */
    public function __construct(array $routes);

    /**
     * Evaluate a URL against our list of routes.
     *
     * Returns the matched URL, if a match exists, and $currentUrl if no match exists. This allows the user to insert
     * Surveyor directly into a normal Climber call, since it will pass the URL through if it doesn't hit any matches.
     *
     * @param string $currentUrl
     * @return string
     */
    public function evaluateUrl(string $currentUrl);
}
