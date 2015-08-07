<?php


namespace CodeReviewPrototype\App\Views;


class DefaultView {

    public static $title = 'Home';
    public static $css = array('styles.css');
    public static $options = array(
        'menuOut' => true,
        'menuCurrent' => 'Home');

    public function renderLoremIpsum ($templatesController) {
        echo $templatesController->getTemplate('LoremIpsum');
    }
}