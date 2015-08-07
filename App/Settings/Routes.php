<?php


namespace CodeReviewPrototype\App\Settings;


class Routes {

    public static $routes = array(
        array('name'=>'Default',    'path'=>'/',            'mask'=>'/'),
        array('name'=>'Branches',   'path'=>'/branch',      'mask'=>'/branch/[parent]/[child]'),
//            array('name'=>'', 'mask'=>'', 'path'=>''),
    );
}