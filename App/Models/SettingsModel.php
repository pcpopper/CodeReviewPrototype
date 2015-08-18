<?php


namespace CodeReviewPrototype\App\Models;


class SettingsModel {

    public $name = null;
    public $info = null;
    public $value = null;

    public function __construct ($name, $info, $value) {
        $this->name = $name;
        $this->info = $info;
        $this->value = $value;
    }
}