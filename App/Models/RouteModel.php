<?php


namespace CodeReviewPrototype\App\Models;


class RouteModel {

    public $name = null;
    public $path = null;
    public $mask = null;

    public function __construct ($route) {
        $this->name = $route->name;
        $this->path = $route->path;
        $this->mask = $route->mask;
    }

    public static function hydrateRouteModels ($routes) {
        $out = array();
        foreach ($routes as $route) {
            $route = (object) $route;
            $out[] = new RouteModel($route);
        }

        return $out;
    }
}