<?php


namespace CodeReviewPrototype\App\Controllers;

use CodeReviewPrototype\App\Controllers\TemplatesController;
use CodeReviewPrototype\App\Controllers\MenuController;

class ViewsController {

    private $methods                = array();
    private $properties             = null;
    private $viewClass              = null;
    private $htmlStart              = null;
    private $templatesController    = null;

    public function renderPage ($route) {
        $this->templatesController = new TemplatesController();
        $this->htmlStart = $this->templatesController->getTemplate('HTMLStart');

        $this->getViewClass($route);
        $this->parseProperties();
        $this->renderHTML();
        $this->renderMenu();
        $this->runClassMethods();
    }

    private function getViewClass ($route) {
        $className = 'CodeReviewPrototype\App\Views\\'.$route->name.'View';
        $this->viewClass = new $className($route);

        $methodsRaw = get_class_methods($this->viewClass);
        foreach ($methodsRaw as $method) {
            if (strpos($method, 'render') !== false) {
                $this->methods[] = $method;
            }
        }

        $this->properties = (object) get_class_vars($className);
    }

    private function parseProperties () {
        $this->htmlStart = preg_replace("/##title##/", $this->properties->title, $this->htmlStart);

        $css = '';
        foreach ($this->properties->css as $file) {
            $css .= "<link rel=\"stylesheet\" href=\"/css/$file\">\n";
        }

        $this->htmlStart = preg_replace("/##includes##/", $css, $this->htmlStart);
    }

    private function renderHTML () {
        echo $this->htmlStart;
    }

    private function renderMenu () {
        $menuHTML = $this->templatesController->getTemplate('MenuBar');
        $menuController = new MenuController($menuHTML);
        echo $menuController->renderMenu();
    }

    private function runClassMethods () {
        echo '    <div id="pageWrapper">'.
            '<div id="buffer" class="out"></div>'.
            '<div id="content">';

        foreach ($this->methods as $method) {
            $this->viewClass->$method($this->templatesController);
        }
    }
}
