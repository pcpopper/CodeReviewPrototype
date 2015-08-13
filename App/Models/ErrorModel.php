<?php


namespace CodeReviewPrototype\App\Models;


class ErrorModel {

    public $len = null;
    public $msg = null;

    public function __construct ($len, $msg) {
        $this->len = $len;
        $this->msg = $msg;
    }
}