<?php


namespace CodeReviewPrototype\App\Controllers;

use CodeReviewPrototype\App\Models\RouteModel;

class RouteController {

    public $routes = null;

    public function __construct () {
        $this->routes = RouteModel::hydrateRouteModels(array(
            array('name'=>'Default',    'path'=>'/',            'mask'=>'/'),
            array('name'=>'Branches',   'path'=>'/branch',      'mask'=>'/branch/[parent]/[child]'),
//            array('name'=>'', 'mask'=>'', 'path'=>''),
        ));
    }

    public function getCurrentRoute () {
        $currRoute = null;

        if ($_SERVER['REQUEST_URI'] == '/') {
            return $this->routes[0];
        }

        foreach ($this->routes as $route) {
            if ($route->path != '/' && substr($_SERVER['REQUEST_URI'], 0, strlen($route->path)) == $route->path) {
                $currRoute = $route;
                $route->vars = $this->getParameters($route->mask);
                break;
            }
        }

        if ($currRoute == null) {
            header('Location: /');
        } else {
            return $currRoute;
        }
    }

    private function getParameters ($mask) {
        $explodedURI = explode('/', $_SERVER['REQUEST_URI']);
        $explodedMask = explode('/', $mask);

        $args = array();
        for ($i = 0; $i < count($explodedMask); $i++) {
            if (strpos($explodedMask[$i], '[') !== false) {
                if (isset($explodedURI[$i])) {
                    $args[$explodedMask[$i]] = $explodedURI[$i];
                }
            }
        }

        return $args;
    }
}