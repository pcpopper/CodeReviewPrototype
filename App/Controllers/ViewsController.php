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
    private $css                    = array('styles');
    private $js                     = array('jquery-2.1.4.min', 'menu');

    public function renderPage ($route) {
        $this->templatesController = new TemplatesController();
        $this->htmlStart = $this->templatesController->getTemplate('HTMLStart');

        $this->getViewClass($route);
        $this->parseProperties();
        $this->renderHTML();
        $this->renderMenu();
        $this->runClassMethods();
        echo $this->templatesController->getTemplate('HTMLEnd');
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

        $this->properties = (object) get_object_vars($this->viewClass);
    }

    private function parseProperties () {
        $includes = '';
        $this->properties->css = array_merge($this->properties->css, $this->css);
        foreach ($this->properties->css as $file) {
            $includes .= "<link rel=\"stylesheet\" href=\"/css/$file.css\">\n";
        }

        $this->properties->js = array_merge($this->properties->js, $this->js);
        foreach ($this->properties->js as $file) {
            $includes .= "<script type=\"text/javascript\" src=\"/js/$file.js\"></script>";
        }

        $options = array();
        foreach ($this->properties->options as $key => $value) {
            $options[] = "\"$key\": \"$value\"";
        }

        $this->htmlStart = TemplatesController::replaceInTemplate($this->htmlStart, array(
            'title'     => $this->properties->title,
            'includes'  => $includes,
            'options'   => implode(',', $options),
        ));
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
