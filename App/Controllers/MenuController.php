<?php


namespace CodeReviewPrototype\App\Controllers;


class MenuController {

    private $rootPath = '/Users/JohnDoe/Sites/OBERD';
    private $cd = null;
    private $branches = array();

    public function __construct ($menuTemplate) {
        $this->cd = "cd $this->rootPath";

        $this->getBranches();
        return preg_replace("/##links##/", $this->parsebranches(), $menuTemplate);
    }

    private function getBranches () {
        $branchesRaw = shell_exec("$this->cd && git branch");
        $branches = explode("\n", $branchesRaw);

        foreach ($branches as $branch) {
            $exploded = explode('/', $branch);
            $value = trim(str_replace('*', '', $branch));

            if (count($exploded) > 1) {
                $trimmedParent = trim($exploded[0]);
                $trimmedChild = trim($exploded[1]);
                $replacedBranch = trim(str_replace('*', '', $exploded[0]));

                if (array_key_exists($replacedBranch, $this->branches)) {
                    echo 'true';
                    $this->branches[$replacedBranch]['branches'][] = array(
                        'value'     => $value,
                        'name'      => $trimmedChild,
                        'current'   => (substr($trimmedParent, 0, 1) == '*') ? true : false,
                    );
                } else {
                    $this->branches[$replacedBranch] = array(
                        'name'      => (substr($trimmedParent, 0, 1) == '*') ? $replacedBranch : $trimmedParent,
                        'branches'  => array(
                            array(
                                'value'     => $value,
                                'name'      => $trimmedChild,
                                'current'   => (substr($trimmedParent, 0, 1) == '*') ? true : false,
                            ),
                        )
                    );
                }
            } elseif ($exploded[0] != '' && trim($exploded[0]) != 'origin') {
                $trimmedBranch = trim($branch);
                $replacedBranch = trim(str_replace('*', '', $trimmedBranch));

                if (!in_array($trimmedBranch, $this->branches)) {
                    $this->branches[trim($branch)] = array(
                        'value'     => $value,
                        'name'      => (substr($trimmedBranch, 0, 1) == '*') ? $replacedBranch : $trimmedBranch,
                        'current'   => (substr($trimmedBranch, 0, 1) == '*') ? true : false,
                    );
                }
            }
        }
    }

    private function parsebranches () {

    }
}