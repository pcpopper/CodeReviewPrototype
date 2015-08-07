<?php


namespace CodeReviewPrototype\App\Controllers;


class DictionaryController {

    private $filename = "../Settings/dictionary";

    public function __construct () {
    }

    private function getDictionary () {
        if (file_exists($filename)) {
            $handle = fopen($filename, "r");
            $contents = fread($handle, filesize($filename));
            fclose($handle);

            return $contents;
        } else {
            throw new \Exception ("The called template '$template' could not be found.");
        }
    }
}