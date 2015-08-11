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

    public function __construct($templatesController, $route) {
        $this->templatesController = $templatesController;
        $this->route = $route;
        $this->branch = implode('/', (array)$route->vars);
        $this->root = Settings::ROOT_PATH;
        $this->parent = (isset($route->vars->child) && $route->vars->child != '') ? $this->getParent($route->vars->parent) : null;
    }

    private function getParent($parent) {
        foreach (explode(' ', Settings::BRANCH_PARENTS) as $parents) {
            $exploded = explode('>', $parents);
            if ($exploded[0] == $parent) {
                return $exploded[1];
            }
        }
    }

    public function buildPage() {
        $branchTemplate = $this->getTemplates();
        $files = $this->getFiles();
        $diff = $this->getDiff();

        $replacements = array(
            'branch' => $this->branch,
            'boxes' => $this->parseFiles($files, $diff),
        );

        return TemplatesController::replaceInTemplate($branchTemplate, $replacements);
    }

    private function getTemplates() {
        $branchTemplate = $this->templatesController->getTemplate('Branch');

        preg_match("/##boxTemplate(.+)##/", preg_replace("/\n/", "..", $branchTemplate), $this->boxTemplate);
        $this->boxTemplate = preg_replace("/\.\./", "\n", $this->boxTemplate[1]);

        return preg_replace("/\.\./", "\n", preg_replace("/##boxTemplate(.+)##/", '', preg_replace("/\n/", "..", $branchTemplate)));
    }

    private function getFiles() {
        $cmd = "cd $this->root && git diff --name-status $this->parent..$this->branch";
        $files = shell_exec($cmd);
        $files = preg_replace("/M\t/", "", $files);
        $files = explode("\n", $files);
        array_pop($files);

        return $files;
    }

    private function getDiff() {
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

    private function parseFiles($files, $diff) {
        $parsed = '';
        foreach ($files as $file) {
            $curr = $diff->$file;
            $numbers = $this->getNumbers($curr);

            $replacements = array(
                'head' => $file,
                'sub' => $curr->sub,
                'add' => $curr->add,
                'old' => $numbers->old,
                'new' => $numbers->new,
                'diff' => $this->parseDiff($curr),
            );
            $parsed .= TemplatesController::replaceInTemplate($this->boxTemplate, $replacements);
        }

        return $parsed;
    }

    private function getNumbers($curr) {
        if (isset($curr->multiple)) {
            foreach ($curr->multiple as $diff) {
                $lines = explode("\n", $diff->diff);
                array_pop($lines);
                $rem = $add = min($diff->remBegin, $diff->addBegin) + 1;

                $numbers = $this->parseLines($lines, $add, $rem);

                $numbers['new'][] = '<hr>';
                $numbers['old'][] = '<hr>';
            }

            array_pop($numbers['new']);
            array_pop($numbers['old']);
        } else {
            $lines = explode("\n", $curr->diff);
            array_pop($lines);
            $rem = $add = min($curr->remBegin, $curr->addBegin) + 1;

            $numbers = $this->parseLines($lines, $add, $rem);
        }

        $numbers = (object)$numbers;

        $numbers->old = implode('<br>', $numbers->old);
        $numbers->new = implode('<br>', $numbers->new);

        $numbers->old = str_replace('<hr><br>', '<hr>', $numbers->old);
        $numbers->new = str_replace('<hr><br>', '<hr>', $numbers->new);

        return $numbers;
    }

    private function parseLines($lines, $add, $rem) {
        $numbers = array(
            'old' => array(),
            'new' => array(),
        );

        foreach ($lines as $line) {
            if (substr(trim($line), 0, 1) == '+' && substr($line, 0, 1) != '\\') {
                $numbers['new'][] = $add;
                $numbers['old'][] = '';
                $add++;
            } else if (substr(trim($line), 0, 1) == '-' && substr($line, 0, 1) != '\\') {
                $numbers['new'][] = '';
                $numbers['old'][] = $rem;
                $rem++;
            } else if (substr($line, 0, 1) != '\\') {
                $numbers['new'][] = $add;
                $numbers['old'][] = $rem;
                $add++;
                $rem++;
            } else {
                $numbers['new'][] = '';
                $numbers['old'][] = '';
            }
        }

        return $numbers;
    }

    private function parseDiff($curr) {
        return (isset($curr->multiple)) ? $this->parseMultiple($curr) : $this->replaceDiff($curr->diff);
    }

    private function parseMultiple($curr) {
        $parsed = array();
        foreach ($curr->multiple as $diffRaw) {
            $parsed[] = $this->replaceDiff($diffRaw->diff);
        }

        return implode('<hr>', $parsed);
    }

    private function replaceDiff($diff) {
        $diff = '<pre>' . $diff . "</pre>";

        // replace tabs
        $diff = preg_replace("/\t/", "    ", $diff);

        // replace minus sign
        $diff = preg_replace("/\n-(.+)/", "\n <span class=\"sub\">$1</span>", $diff);

        // replace plus sign
        $diff = preg_replace("/\n\+(.+)/", "\n <span class=\"add\">$1</span>", $diff);

        // replace new lines
        $diff = preg_replace("/\n/", "<br>", $diff);

        // replace no new line
//        $diff = str_replace('\\', "", $diff);

        return $diff;
    }
}
