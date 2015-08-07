<?php


namespace CodeReviewPrototype\App\Views;


class BranchesView {

    public $title = 'Branches - ';
    public $css = array();
    public $js = array();
    public $options = array(
        'menuOut' => true,
        'menuCurrent' => '');

    private $branchWhole = '';

    public function __construct ($route) {
        $this->branchWhole = implode('/', (array) $route->vars);
        $this->options['menuCurrent'] = (isset($route->vars->child) && $route->vars->child != '') ? $route->vars->child : $route->vars->parent;
        $this->title .= $this->branchWhole;
    }

    public function renderBranch () {

    }
}