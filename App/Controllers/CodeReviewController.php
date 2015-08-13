<?php


namespace CodeReviewPrototype\App\Controllers;


class CodeReviewController {

    private $dictionary = null;
    private $bracketsCount = array();
    private $bracketsCountRaw = null;
    private $brackets = array('('=>')', '{'=>'}', '['=>']');

    public function __construct () {
        $dictionaryController = new DictionaryController();
        $this->dictionary = $dictionaryController->getDictionary();

        $this->bracketsCountRaw = (object) array ();
        foreach ($this->brackets as $open => $close) {
            $this->bracketsCountRaw->$open = 0;
            $this->bracketsCountRaw->$close = 0;
        }
    }

    public function codeReview ($differences) {
        $i = $m = 0;
        foreach ($differences as $file =>$difference) {
            if (!empty($difference->multiple)) {
                foreach ($difference->multiple as $index => $singular) {
                    $differences->$file->multiple[$index] = $this->parseDiff($singular->diff);
                }
            } else {
                $differences->$file->diff = $this->parseDiff($difference->diff);
            }

            $i++;
        }

        return $differences;
    }

    private function parseDiff ($diff) {
        $lines = explode("\n", $diff);
        $lines = $this->getBlocks($lines);
        $errors = $this->parseBulk($lines);
        $lines = $this->parseBulkErrors($lines, $errors);

        return implode("\n", $lines);
    }

    private function getBlocks ($lines) {
        $sectionNum = -1;
        $block = false;
        foreach ($lines as $index => $line) {
            if (substr($line, 0, 1) == '+' && !$block) {
                $block = true;
                $lines[$index] = '^^' . $line;
            } else if (substr($line, 0, 1) != '+' && $block) {
                $lines[$index] = '^^' . $line;
                $block = false;
                $sectionNum++;
                $this->bracketsCount[] = $this->bracketsCountRaw;
            }

            if (substr($line, 0, 1) != '+' && substr($line, 0, 1) != '-' && strlen($line) > 0) {
                foreach ($this->brackets as $open => $close) {
                    $this->bracketsCount[$sectionNum]->$open += substr_count($line, $open);
                    $this->bracketsCount[$sectionNum]->$close += substr_count($line, $close);
                }
            }
        }

        return $lines;
    }

    private function parseBulk ($lines) {
        $imploded = (is_array($lines)) ? implode("\n", $lines) : $lines;

        $sections = explode('^^', $imploded); // breaks up the imploded whole into sections
        array_shift($sections); // removes the first unneeded section
        $sections = array_map('array_shift', array_chunk($sections, 2)); // removes the other unneeded sections

        $errors = range(0, count($sections));
        foreach ($sections as $index => $section) { // loop through the sections
            $errors = CodeReviewParseController::checkBrackets($section, $index, $this->brackets, $errors, $this->bracketsCount);
        }

        array_shift($errors);
        return $errors;
    }

    private function parseBulkErrors ($lines, $errors) {
        $sectionNum = 0;
        $block = false;
        $blocks = array();
        foreach ($lines as $index => $line) {
            if (substr($line, 0, 2) == '^^') {
                $lines[$index] = str_replace('^^', '', $line);

                if ($block) {
                    $block = false;
                    $sectionNum++;
                } else {
                    $block = true;
                    $blocks[$sectionNum] = $index;
                }
            }
        }

        foreach ($errors as $index => $section) {
            if (is_array($section)) {
                foreach (array_reverse($section) as $location => $error) {
                    $location = explode(",", $location);
                    $lineNum = $blocks[$index] + $location[0];
                    $line = (substr($lines[$lineNum], 0, 2) == '~~') ? $lines[$lineNum] : '~~' . $lines[$lineNum];

                    $strOrig = substr($line, $location[1] - 3, $error->len + 3);
                    $strError = substr($line, $location[1], $error->len);
                    $strReplace = substr($line, $location[1] - 3, 3)."@@$strError,$error->msg@@";
                    $lines[$lineNum] = str_replace($strOrig, $strReplace, $line);
                }
            }
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