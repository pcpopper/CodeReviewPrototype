<?php


namespace CodeReviewPrototype\App\Controllers;


class CodeReviewController {

    public function __construct () {}

    public function codeReview ($differences) {
        $out = '';
        foreach ($differences as $difference) {
            if (isset($difference->multiple)) {} else {
                $out = $this->parseDiff($difference->diff);
            }
        }

        return $out;
    }

    private function parseDiff ($diff) {
        $lines = explode("\n", $diff);
        $additions = $this->getAdditions($lines);

    }

    private function getAdditions ($lines) {
        $additions = array();
        $i = 0;
        foreach ($lines as $line) {
            if (substr($line, 0, 1) == '+') {
                $additions[$i] = $line;
            }
            $i++;
        }

        return $additions;
    }

    private function parseDefaults ($additions) {

    }
}