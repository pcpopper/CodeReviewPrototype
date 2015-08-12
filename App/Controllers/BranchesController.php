<?php


namespace CodeReviewPrototype\App\Controllers;


use CodeReviewPrototype\App\Settings\Settings;

class BranchesController {

    private $templatesController = null;
    private $route = null;
    private $branch = null;
    private $root = null;
    private $parent = null;
    private $boxTemplate = null;
    private $rowTemplate = null;
    private $dividerTemplate = null;
    private $numbers = null;
    private $parsedLines = null;

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

        $branchTemplate = $this->getTemplates();
        $files = $this->getFiles();
        $diff = $this->getDiff();
//        $diff = $codeReviewController->codeReview($diff);

        $replacements = array(
            'branch' => $this->branch,
            'boxes' => $this->parseFiles($files, $diff),
        );

        return TemplatesController::replaceInTemplate($branchTemplate, $replacements);
    }

    private function getTemplates () {
        $branchTemplate = $this->templatesController->getTemplate('Branch');

        preg_match("/##dividerTemplate(.+)##/", preg_replace("/\n/", "..", $branchTemplate), $this->dividerTemplate);
        $this->dividerTemplate = preg_replace("/\.\./", "\n", $this->dividerTemplate[1]);
        $branchTemplate = preg_replace("/\.\./", "\n", preg_replace("/##dividerTemplate(.+)##/", '', preg_replace("/\n/", "..", $branchTemplate)));

        preg_match("/##rowTemplate(.+)##/", preg_replace("/\n/", "..", $branchTemplate), $this->rowTemplate);
        $this->rowTemplate = preg_replace("/\.\./", "\n", $this->rowTemplate[1]);
        $branchTemplate = preg_replace("/\.\./", "\n", preg_replace("/##rowTemplate(.+)##/", '', preg_replace("/\n/", "..", $branchTemplate)));

        preg_match("/##boxTemplate(.+)##/", preg_replace("/\n/", "..", $branchTemplate), $this->boxTemplate);
        $this->boxTemplate = preg_replace("/\.\./", "\n", $this->boxTemplate[1]);
        return preg_replace("/\.\./", "\n", preg_replace("/##boxTemplate(.+)##/", '', preg_replace("/\n/", "..", $branchTemplate)));
    }

    private function getFiles () {
        $cmd = "cd $this->root && git diff --name-status $this->parent..$this->branch";
        $files = shell_exec($cmd);
        $files = preg_replace("/M\t/", "", $files);
        $files = explode("\n", $files);
        array_pop($files);

        return $files;
    }

    private function getDiff () {
        $cmd = 'cd ' . Settings::ROOT_PATH;
        $currBranch = shell_exec("$cmd && git rev-parse --abbrev-ref HEAD");

        $cmd .= " && git diff $this->parent..$this->branch";
        $diffRaw = shell_exec($cmd);
        $diffRaw = explode('diff ', $diffRaw);
        array_shift($diffRaw);

        $diff = array();
        foreach ($diffRaw as $file) {
            preg_match("/--- a\/(.+)/", $file, $filenameRaw);
            $filename = $filenameRaw[1];

            if (substr_count($file, '@@') == 2) {
                $fileDiffRaw = explode('@@', $file);
                $fileStatRaw = explode(' ', trim($fileDiffRaw[1]));

                $additions = substr_count($fileDiffRaw[2], "\n+");
                $subtractions = substr_count($fileDiffRaw[2], "\n-");

                preg_match("/-(\d+)/", $fileStatRaw[0], $remBeginRaw);
                preg_match("/-(\d+)/", $fileStatRaw[1], $addBeginRaw);

                $diff[$filename] = (object)array(
                    'add' => $additions,
                    'sub' => $subtractions,
                    'remBegin' => (isset($remBeginRaw[1])) ? $remBeginRaw[1] : 1000000000,
                    'addBegin' => (isset($addBeginRaw[1])) ? $addBeginRaw[1] : 1000000000,
                    'diff' => $fileDiffRaw[2],
                );
            } else if (substr_count($file, '@@') >= 2) {
                $fileDiffRaw = explode('@@', $file);
                array_shift($fileDiffRaw);

                $diffRaw = array();
                $additions = 0;
                $subtractions = 0;
                for ($i = 0; $i < count($fileDiffRaw); $i += 2) {
                    $fileStatRaw = explode(' ', trim($fileDiffRaw[$i]));

                    $additions += substr_count($fileDiffRaw[$i + 1], "\n+");
                    $subtractions += substr_count($fileDiffRaw[$i + 1], "\n-");

                    preg_match("/-(\d+)/", $fileStatRaw[0], $remBeginRaw);
                    preg_match("/-(\d+)/", $fileStatRaw[1], $addBeginRaw);

                    $diffRaw[] = (object)array(
                        'remBegin' => (isset($remBeginRaw[1])) ? $remBeginRaw[1] : 1000000000,
                        'addBegin' => (isset($addBeginRaw[1])) ? $addBeginRaw[1] : 1000000000,
                        'diff' => $fileDiffRaw[$i + 1],
                    );
                }

                $diff[$filename] = (object)array(
                    'add' => $additions,
                    'sub' => $subtractions,
                    'multiple' => $diffRaw,
                );
            }
        }

        shell_exec('cd ' . Settings::ROOT_PATH . " && git checkout $currBranch");

        return (object)$diff;
    }

    private function parseFiles ($files, $diff) {
        $parsed = '';
        foreach ($files as $file) {
            $curr = $diff->$file;
            $this->parseDiff($curr);

            $replacements = array(
                'head' => $file,
                'sub' => $curr->sub,
                'add' => $curr->add,
                'rows' => $this->parsedLines,
            );
            $parsed .= TemplatesController::replaceInTemplate($this->boxTemplate, $replacements);
        }

        return $parsed;
    }

    private function parseDiff ($curr) {
        $this->parsedLines = (isset($curr->multiple)) ? $this->parseMultiple($curr) : $this->parseSingle($curr);
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
        $oldCount = $newCount = min($curr->remBegin, $curr->addBegin) + 1;

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
            } else if (substr($line, 0, 1) == '/') {
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

        // replace tabs
//        $line = preg_replace("/\t/", "    ", $line);

        // replace minus sign
        $line = preg_replace("/^-/", " ", $line);

        // replace plus sign
        $line = preg_replace("/^\+/", " ", $line);

        return $line;
    }
}
