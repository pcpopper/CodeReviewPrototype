<?php


namespace CodeReviewPrototype\App\Views;

use CodeReviewPrototype\App\Controllers\BranchesController;

class BranchesView {

    public $title = 'Branches - ';
    public $css = array();
    public $js = array();
    public $options = array(
        'menuOut' => true,
        'menuCurrent' => '');

    private $route = null;

    public function __construct ($route) {
        $this->route = $route;
        $this->options['menuCurrent'] = (isset($route->vars->child) && $route->vars->child != '') ? $route->vars->child : $route->vars->parent;
        $this->title .= implode('/', (array) $route->vars);
    }

    public function renderBranch ($templatesController) {
        $branchesController = new BranchesController($templatesController, $this->route);
        echo $branchesController->buildPage();
    }
}