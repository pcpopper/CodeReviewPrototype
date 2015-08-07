<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../vendor/autoload.php';

use CodeReviewPrototype\App\Controllers\ViewsController;
use CodeReviewPrototype\App\Controllers\RouteController;

$routeController = new RouteController();
$route = $routeController->getCurrentRoute();

$viewsController = new ViewsController();
$viewsController->renderPage($route);
