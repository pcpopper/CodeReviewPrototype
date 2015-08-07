<?php


namespace CodeReviewPrototype\App\Controllers;


use CodeReviewPrototype\App\Settings\Settings;

class BranchesController {

    private $templatesController = null;
    private $route = null;
    private $branch = null;
    private $root = null;
    private $parent = null;

    public function __construct ($templatesController, $route) {
        $this->templatesController = $templatesController;
        $this->route = $route;
        $this->branch = implode('/', (array) $route->vars);
        $this->root = Settings::ROOT_PATH;
        $this->parent = (isset($route->vars->child) && $route->vars->child != '') ? Settings::BRANCH_PARENTS->$route->vars->parent : null;
    }

    public function buildPage () {
        $branchTemplate = $this->templatesController->getTemplate('Branch');

        $out = $this->getDiff();

        return $out;
    }

    public function getFiles () {
        $cmd = "cd $this->root && git diff --name-status ";
    }

    public function getDiff () {
        $cmd = 'cd ' . Settings::ROOT_PATH;
        $currBranch = shell_exec("$cmd && git rev-parse --abbrev-ref HEAD");

        $cmd .= " && git diff ";

//        $out = $this->getAdditions(htmlspecialchars(shell_exec($cmd)));
//
//        shell_exec('cd ' . Settings::ROOTPATH . " && git checkout $currBranch");

        return $cmd;
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
}