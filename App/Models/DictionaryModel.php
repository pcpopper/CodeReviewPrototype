<?php


namespace CodeReviewPrototype\App\Models;


class DictionaryModel {

    public $function = null;
    public $errorMsg = null;
    public $vars = array();

    public function __construct ($raw) {
        $this->function = $raw[0];
        $this->errorMsg = $raw[1];

        for ($i = 2; $i < count($raw); $i++) {
            $this->vars[] = $raw[$i];
        }
    }
}