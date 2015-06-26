<?php
namespace CodeReviewPrototype\App;

Class CodeReviewPrototype {
    public $rootPath = '/Users/pcpopper/Sites/CodeReviewPrototype';
    public $cd;

    public function __construct () {
        $this->cd = "cd $this->rootPath";
    }

    public function getDiff ($branch) {
        $currBranch = shell_exec("$this->cd && git rev-parse --abbrev-ref HEAD");

        $cmd = $this->cd . " && git checkout $branch && git diff";

        $out = $this->getAdditions(htmlspecialchars(shell_exec($cmd)));

        shell_exec("$this->cd && git checkout $currBranch");

        return $out;
    }

    public function getAdditions ($buffer) {
        $lines = explode("\n", $buffer);

        $out = array();
        foreach ($lines as $line) {
            if (substr($line, 0, 2) == '+ ') {
                $out[] = str_replace('+ ', '', $line);
            }
        }

        return implode("\n", $out);
    }

    public function getBranches () {
        $branchesRaw = shell_exec("$this->cd && git branch");
        $branches = explode("\n", $branchesRaw);

        $out = array();
        foreach ($branches as $branch) {
            $out[] = array(
                'value' =>  (substr(trim($branch), 0, 1) == '*') ? '' : trim($branch),
                'name'  =>  (substr(trim($branch), 0, 1) == '*') ? trim(str_replace('*', '', $branch)).' (current branch)' : trim($branch)
            );
        }

        array_pop($out);
        return $out;
    }
}