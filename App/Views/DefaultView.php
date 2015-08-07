<?php


namespace CodeReviewPrototype\App\Views;


class DefaultView {

    public $title = 'Home';
    public $css = array();
    public $js = array();
    public $options = array(
        'menuOut' => true,
        'menuCurrent' => 'Home');

    public function renderLoremIpsum ($templatesController) {
        echo $templatesController->getTemplate('LoremIpsum');
    }
}