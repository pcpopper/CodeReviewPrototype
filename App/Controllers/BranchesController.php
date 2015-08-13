<?php


namespace CodeReviewPrototype\App\Controllers;


use CodeReviewPrototype\App\Models\DifferenceModel;
use CodeReviewPrototype\App\Settings\Settings;

class BranchesController {

    private $templatesController = null;
    private $route = null;
    private $branch = null;
    private $root = null;
    private $parent = null;

    private $branchTemplate = null;
    private $boxTemplate = null;
    private $rowTemplate = null;
    private $dividerTemplate = null;
    private $errorTemplate = null;

    public function __construct ($templatesController, $route) {
        $this->templatesController = $templatesController;
        $this->route = $route;
        $this->branch = implode('/', (array)$route->vars);
        $this->root = Settings::ROOT_PATH;
        $this->parent = (isset($route->vars->child) && $route->vars->child != '') ? $this->getParent($route->vars->parent) : null;
    }

    private function getParent ($parent) {
        foreach (explode(' ', Settings::BRANCH_PARENTS) as $parents) {
            $exploded = explode('>', $parents);
            if ($exploded[0] == $parent) {
                return $exploded[1];
            }
        }
    }

    public function buildPage () {
        $codeReviewController = new CodeReviewController();

        $this->getTemplates();
        $files = $this->getFiles();
        $diffs = $this->getDiff();
        $diff = $codeReviewController->codeReview($diffs);

        $replacements = array(
            'branch' => $this->branch,
            'boxes' => $this->parseFiles($files, $diff),
        );

        return TemplatesController::replaceInTemplate($this->branchTemplate, $replacements);
    }

    private function getTemplates () {
        $this->boxTemplate = $this->templatesController->getTemplate('Branches/Boxes');
        $this->branchTemplate = $this->templatesController->getTemplate('Branches/Branch');
        $this->dividerTemplate = $this->templatesController->getTemplate('Branches/Divider');
        $this->rowTemplate = $this->templatesController->getTemplate('Branches/Rows');
        $this->errorTemplate = $this->templatesController->getTemplate('Errors');
    }

    private function getFiles () {
        if (Settings::TESTING) {
            $files = array('errorFilled');
        } else {
            $cmd = "cd $this->root && git diff --name-status $this->parent..$this->branch";
            $files = shell_exec($cmd);
            $files = preg_replace("/M\t/", "", $files);
            $files = explode("\n", $files);
            array_pop($files);
        }

        return $files;
    }

    private function getDiff () {
        if (Settings::TESTING) {
            $filename = Settings::ERROR_FILE_PATH;
            $handle = fopen($filename, "r");
            $diffRaw = fread($handle, filesize($filename));
            fclose($handle);
        } else {
            $cmd = 'cd ' . Settings::ROOT_PATH;
            $currBranch = shell_exec("$cmd && git rev-parse --abbrev-ref HEAD");

            $cmd .= " && git diff $this->parent..$this->branch";
            $diffRaw = shell_exec($cmd);
        }

        $diffRaw = explode('diff ', $diffRaw);
        array_shift($diffRaw);

        $diff = array();
        foreach ($diffRaw as $file) {
            preg_match("/--- a\/(.+)/", $file, $filenameRaw);
            $filename = $filenameRaw[1];

            if (substr_count($file, '@@') == 2) {
                $fileDiffRaw = explode('@@', $file);
                preg_match("/-(\d+).+\+(\d+)/", $fileDiffRaw[1], $fileStatRaw);

                $additions = substr_count($fileDiffRaw[2], "\n+");
                $subtractions = substr_count($fileDiffRaw[2], "\n-");

                $exploded = explode("\n", $fileDiffRaw[2]);
                $functionClass = trim(array_shift($exploded));

                $diff[$filename] = new DifferenceModel(array(
                    'add' => $additions,
                    'sub' => $subtractions,
                    'remBegin' => $fileStatRaw[1],
                    'addBegin' => $fileStatRaw[2],
                    'functionClass' => $functionClass,
                    'diff' => implode("\n", $exploded),
                ));
            } else if (substr_count($file, '@@') >= 2) {
                $fileDiffRaw = explode('@@', $file);
                array_shift($fileDiffRaw);

                $diffRaw = array();
                $additions = 0;
                $subtractions = 0;
                for ($i = 0; $i < count($fileDiffRaw); $i += 2) {
                    preg_match("/-(\d+).+\+(\d+)/", $fileDiffRaw[$i], $fileStatRaw);

                    $exploded = explode("\n", $fileDiffRaw[$i + 1]);
                    $functionClass = trim(array_shift($exploded));

                    $diffRaw[] = new DifferenceModel(array(
                        'remBegin' => $fileStatRaw[1],
                        'addBegin' => $fileStatRaw[2],
                        'functionClass' => $functionClass,
                        'diff' => implode("\n", $exploded),
                    ));
                }

                $diff[$filename] = new DifferenceModel(array(
                    'add' => $additions,
                    'sub' => $subtractions,
                    'multiple' => $diffRaw,
                ));
            }
        }

        if (!Settings::TESTING) {
            shell_exec('cd ' . Settings::ROOT_PATH . " && git checkout $currBranch");
        }

        return (object) $diff;
    }

    private function parseFiles ($files, $diff) {
        $parsed = '';
        foreach ($files as $file) {
            $curr = $diff->$file;

            $replacements = array(
                'head' => $file,
                'sub' => $curr->sub,
                'add' => $curr->add,
                'rows' => (!empty($curr->multiple)) ? $this->parseMultiple($curr) : $this->parseSingle($curr),
            );
            $parsed .= TemplatesController::replaceInTemplate($this->boxTemplate, $replacements);
        }

        return $parsed;
    }

    private function parseMultiple ($curr) {
        $parsed = array();
        foreach ($curr->multiple as $diffRaw) {
            $parsed[] = $this->parseSingle($diffRaw);
        }

        return implode($this->dividerTemplate, $parsed);
    }

    private function parseSingle ($curr) {
        $linesRaw = explode("\n", $curr->diff);
        array_pop($linesRaw);
        $oldCount = $curr->remBegin;
        $newCount = $curr->addBegin;

        $lines = '';
        $i = 0;
        foreach ($linesRaw as $line) {
            $class = '';
            if (substr($line, 0, 1) == '+') {
                $class = 'add';
                $new = $newCount++;
                $old = '';
            } else if (substr($line, 0, 2) == '~~') {
                $class = 'error';
                $new = $newCount++;
                $old = '';
            } else if (substr($line, 0, 1) == '-') {
                $class = 'sub';
                $new = '';
                $old = $oldCount++;
            } else if (substr(trim($line), 0, 1) == '\\') {
                $new = '';
                $old = '';
            } else {
                $new = $newCount++;
                $old = $oldCount++;
            }

            $lines .= TemplatesController::replaceInTemplate($this->rowTemplate, array (
                'class' => $class,
                'old' => $old,
                'new' => $new,
                'diffContent' => $this->replaceDiff($line),
            ));
            $i++;
        }

        return $lines;
    }

    private function replaceDiff ($line) {
        $line = htmlspecialchars($line);

        // replace error sign
        $line = preg_replace("/^~~/", "", $line);

        // replace minus sign
        $line = preg_replace("/^-/", " ", $line);

        // replace plus sign
        $line = preg_replace("/^\+/", " ", $line);

        // replace errors
        while (strpos($line, '@@') !== false) {
            preg_match("/(.*)@@(.+)@@(.*)/", $line, $output_array);
            $errorRaw = explode(',', $output_array[2]);
            $output_array[2] = TemplatesController::replaceInTemplate($this->errorTemplate, array('msg'=>$errorRaw[1],'code'=>$errorRaw[0]));

            $line = preg_replace("/(.+)@@.+@@(.*)/", "$1$output_array[2]$2", $line);
        }

        return $line;
    }
}
