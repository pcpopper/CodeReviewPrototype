<?php


namespace CodeReviewPrototype\App\Controllers;


class CodeReviewController {

    private $dictionary = null;
    private $bracketsCount = array();
    private $bracketsCountRaw = null;
    private $brackets = array('('=>')', '{'=>'}', '['=>']');
    private $errors = null;
    private $singularErrorString = '%%%';

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
        $lines = $this->parseSingular($lines);
        $errors = $this->parseBulk($lines);
        $lines = $this->parseBulkErrors($lines, $errors);
        $lines = $this->parseSingularErrors($lines);

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
                    $strReplace = substr($line, $location[1] - 3, 3).$this->makeError($strError,$error->msg);
                    $lines[$lineNum] = str_replace($strOrig, $strReplace, $line);
                }
            }
        }

        return $lines;
    }

    private function makeError ($err, $msg) {
        return "@@$err,$msg@@";
    }

    private function parseSingular ($lines) {
        $errors = array();
        foreach ($lines as $index => $line) {
            if (substr($line, 0, 1) == '+') {
                if (CodeReviewParseController::checkEndOfLineSemicolon($lines, $index)) {
                    $errors["$index,".strlen($line)] = $this->makeError(' ', 'Missing the semicolon');
                    $line .= $this->singularErrorString;
                }

                $lines[$index] = $line;
            }
        }

        $this->errors = $errors;

        return $lines;
    }

    private function parseSingularErrors ($lines) {
        $diff = implode("\n", $lines);
        ksort($this->errors);
        $patterns = array_fill(0, count($this->errors), "/%%%/");
        $diff = preg_replace($patterns, $this->errors, $diff);
        return explode("\n", $diff);
    }
}