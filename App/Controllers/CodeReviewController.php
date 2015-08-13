<?php


namespace CodeReviewPrototype\App\Controllers;


class CodeReviewController {

    private $dictionary = null;
    private $errors = null;

    public function __construct () {
        $dictionaryController = new DictionaryController();
        $this->dictionary = $dictionaryController->getDictionary();
    }

    public function codeReview ($differences) {
        $i = $m = 0;
        foreach ($differences as $file =>$difference) {
            if (!empty($difference->multiple)) {
                foreach ($difference->multiple as $index => $singular) {
                    $differences->$file->multiple[$index] = $this->parseDiff($singular->diff);
//                    echo '<pre>',var_dump(htmlspecialchars($singular->diff)),'</pre>';
                }
            } else {
                $differences->$file->diff = $this->parseDiff($difference->diff);
//                echo '<pre>',var_dump(htmlspecialchars($difference->diff)),'</pre>';
            }

            $i++;
        }

        return $differences;
    }

    private function parseDiff ($diff) {
        $lines = explode("\n", $diff);
        $lines = $this->getBlocks($lines);
        $this->parseBulk($lines);

        return implode("\n", $lines);
    }

    private function parseBulk ($lines) {
        $imploded = (is_array($lines)) ? implode("\n", $lines) : $lines;

        $sections = explode('^^', $imploded); // breaks up the imploded whole into sections
        array_shift($sections); // removes the first unneeded section
        $sections = array_map('array_shift', array_chunk($sections, 2)); // removes the other unneeded sections

        $this->errors = array();
        foreach ($sections as $index => $section) { // loop through the sections

            // check that there is a close bracket for every opening
            $brackets = array('('=>')', '{'=>'}', '['=>']');
            foreach ($brackets as $open => $close) { // loop through the brackets
                if (strpos($section, $open) !== false) { // check if there is an open bracket
                    if (substr_count($section, $open) != substr_count($section, $close)) { // check if the counts of the open and closing brackets are the same
                        $this->errors[] = (object) array (
                            'section' => $index,
                            'char' => $open,
                        );
                    }
                }
            }
            echo '<pre>',var_dump($this->errors),'</pre>';
        }
    }

    private function getBlocks ($lines) {
        $i = 0;
        $block = false;
        foreach ($lines as $line) {
            if (substr($line, 0, 1) == '+' && !$block) {
                $block = true;
                $lines[$i] = '^^' . $line;
            } else if (substr($line, 0, 1) != '+' && $block) {
                $lines[$i] = '^^' . $line;
                $block = false;
            }

            $i++;
        }

        return $lines;
    }

    private function parseDefaults ($line) {
        if (!substr_count($line, '(') == substr_count($line, ')')) {
            $line = $this->setError($line);
        }

        return $line;
    }

    private function setError ($line) {
        return preg_replace("/^\+/", "~~", $line);

    }
}