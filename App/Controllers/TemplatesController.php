<?php


namespace CodeReviewPrototype\App\Controllers;


class TemplatesController {

    public function getTemplate ($template) {
        if (empty($template)) {
            throw new \Exception ('A template must be provided.');
        }

        $filename = "../App/Templates/$template.html";
        if (file_exists($filename)) {
            $handle = fopen($filename, "r");
            $contents = fread($handle, filesize($filename));
            fclose($handle);

            return $contents;
        } else {
            throw new \Exception ("The called template '$template' could not be found.");
        }
    }

    public static function replaceInTemplate ($template, $replacements, $mask = '##') {
        foreach ($replacements as $needle => $replacement) {
            $searchString = $mask.$needle.$mask;
            $template = preg_replace("/$searchString/", $replacement, $template);
        }

        return $template;
    }
}