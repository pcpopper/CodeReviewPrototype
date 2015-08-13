<?php


namespace CodeReviewPrototype\App\Controllers;


use CodeReviewPrototype\App\Models\ErrorModel;

class CodeReviewParseController {

    public static function checkBrackets ($section, $index, $brackets, $errors, $bracketsCount) {
        // check that there is a close bracket for every opening
        foreach ($brackets as $open => $close) { // loop through the brackets
            if (strpos($section, $open) !== false) { // check if there is an open bracket
                $countOpen = substr_count($section, $open);
                $countClose = substr_count($section, $close);

                if ($countOpen != $countClose) { // check if the counts of the open and closing brackets are the same
                    $error = false;

                    $openCount = self::checkOtherSectionBrackets('next', $bracketsCount[$index], $open, $close, $countOpen, $countClose);
                    if ($index > 0) { $closeCount = self::checkOtherSectionBrackets('prev', $bracketsCount[$index - 1], $open, $close, $countOpen, $countClose); }

                    if ($openCount > 0) { $error = true; }
                    if (isset($closeCount) && $closeCount > 0) { $error = true; }

                    if ($error) {
                        if (!is_array($errors[$index + 1])) { $errors[$index + 1] = array(); }
                        if ($openCount = 1) {
                            $errors[$index + 1] = array_merge($errors[$index + 1], self::findSectionBracketErrors($open, $close, $section, 'closing'));
                        }
//                        if (isset($closeCount) && $closeCount > 0) { $errors[$index + 1][] = $close; }
                    }
                }
            }

            ksort($errors[$index + 1]);
        }

        return $errors;
    }
    private static function checkOtherSectionBrackets ($func, $bracketsCount, $open, $close, $countOpen, $countClose) {
        $countOtherOpen = $bracketsCount->$open;
        $countOtherClose = $bracketsCount->$close;

        $out = false;
        if ($func == 'next' && $countOpen > $countClose && $countOtherOpen >= $countOtherClose) {
            $out = $countOpen - $countClose;
        } else if ($func == 'prev' && $countOpen < $countClose && $countOtherOpen <= $countOtherClose) {
            $out = $countClose - $countOpen;
        }

        return $out;
    }
    private static function findSectionBracketErrors ($bracket1, $bracket2, $section, $missing) {
        $lines = explode("\n", $section);
        $odd = array();
        foreach ($lines as $index => $line) {
            $diff = substr_count($line, $bracket1) - substr_count($line, $bracket2); // get the difference of the counts

            if ($diff > 0) {
                for ($i = 0; $i < $diff; $i++) { // loop through the line to get the location of each bracket
                    $odd[$index . ',' . self::getBracketsErrorLocation($line, $bracket1, $i)] = new ErrorModel(1, "Missing $missing bracket '$bracket2'");
                }
            } else if ($diff < 0) {
                for ($i = 0; $i > $diff; $i--) { // remove the unneeded bracket errors
                    array_pop($odd);
                }
            }
        }

        return $odd;
    }
    private static function getBracketsErrorLocation ($line, $bracket, $number) {
        $exploded = explode($bracket, $line);
        $count = 0;

        foreach ($exploded as $index => $group) {
            if ($index - 1 == $number) { break; }
            $count += strlen($group) + 1;
        }

        return $count + 1;
    }

    public static function checkEndOfLineSemicolon ($lines, $index) {
        $lastChar = substr($lines[$index], -1, 1);
        $exceptions = array(';','{','.','(');
        $out = false;

        if (!in_array($lastChar, $exceptions)) { $out = true; }
        if ($index != count($lines) && $out == true && substr($lines[$index+1], 0, 1) == '+') {
            $exceptions = array('-',')');
            $firstChar = substr(trim(preg_replace("/^\+/", " ", $lines[$index+1])), 0, 1);
            if (in_array($firstChar, $exceptions)) { $out = false; }
        }

        return $out;
    }
}