<?php

namespace Livy\Climber;

use Livy\Climber\API\SurveyorAPI;

/**
 * Surveyor provides a way to map many URLs to a single one.
 *
 * The primary usage of this is to help activate leaves that have children which are not part of the Tree.
 *
 * @package Livy\Climber
 */
class Surveyor implements SurveyorAPI
{
    protected $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function evaluateUrl(string $currentUrl)
    {
        if (null === $currentUrl || count($this->routes) < 1) {
            return null;
        }

        foreach ($this->routes as $route) {
            if (1 === preg_match($route[0], $currentUrl)) {
                return $route[1];
            }
        }

        return $currentUrl;
    }
}
