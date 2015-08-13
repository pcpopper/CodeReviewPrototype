<?php


namespace CodeReviewPrototype\App\Models;


class DifferenceModel {

    public $add = null;
    public $sub = null;
    public $remBegin = null;
    public $addBegin = null;
    public $functionClass = null;
    public $diff = null;
    public $multiple = array();

    public function __construct ($data) {
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
    }
}