<?php


namespace CodeReviewPrototype\App\Controllers;


use CodeReviewPrototype\App\Models\DictionaryModel;
use CodeReviewPrototype\App\Models\DictionaryTypesModel;
use CodeReviewPrototype\App\Settings\Settings;

class DictionaryController {

    public function __construct () {}

    public function getDictionary () {
        $filename = Settings::DICTIONARY_PATH;
        if (file_exists($filename)) {
            $handle = fopen($filename, "r");
            $contents = fread($handle, filesize($filename));
            fclose($handle);

            $contents = $this->parseDictionary($contents);

            return $contents;
        } else {
            throw new \Exception ("The dictionary could not be loaded.");
        }
    }

    private function parseDictionary ($contents) {
        $lines = explode("\n", $contents);

        $parsed = new DictionaryTypesModel();
        foreach ($lines as $line) {
            $cols = explode(",", $line);
            $type = array_shift($cols);
            array_push($parsed->$type, new DictionaryModel($cols));
        }

        return (object) $parsed;
    }
}