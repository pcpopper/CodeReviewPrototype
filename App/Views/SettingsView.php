<?php
/**
 * Created by PhpStorm.
 * User: pcpopper
 * Date: 8/17/15
 * Time: 3:15 PM
 */

namespace CodeReviewPrototype\App\Views;


use CodeReviewPrototype\App\Controllers\SettingsController;

class SettingsView {

    public $title = 'Settings';
    public $css = array();
    public $js = array('settings');
    public $options = array(
        'menuOut' => true,
        'menuCurrent' => 'Settings');

    private $route = null;

    public function __construct ($route) {
        $this->route = $route;
//        $this->title .= $route->vars;
    }

    public function renderSettings ($templatesController) {
        $settingsController = new SettingsController($templatesController, $this->route);
        echo $settingsController->buildPage();
    }
}