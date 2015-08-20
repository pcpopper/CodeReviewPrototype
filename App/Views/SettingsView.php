<?php

namespace CodeReviewPrototype\App\Views;


use CodeReviewPrototype\App\Controllers\SettingsController;

class SettingsView {

    public $title = 'Settings';
    public $css = array();
    public $js = array('settings');
    public $options = array(
        'menuOut' => false,
        'menuCurrent' => 'Settings');

    private $route = null;

    public function __construct ($route) {
        $this->route = $route;
    }

    public function renderSettings ($viewsController, $templatesController) {
        $settingsController = new SettingsController($templatesController, $this->route);
        $page = $settingsController->buildPage();
        if (is_array($page) && $page[0] == 'json') {
            $viewsController->renderJson($page);
        } else {
            $viewsController->renderPage($page);
        }
    }
}