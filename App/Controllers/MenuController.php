<?php


namespace CodeReviewPrototype\App\Controllers;


use CodeReviewPrototype\App\Settings\Settings;

class MenuController {

    private $branches = array();
    private $buttonTemplate = null;
    private $menuTemplate = null;

    public function __construct ($menuTemplate) {
        $this->menuTemplate = $menuTemplate;
    }

    public function renderMenu () {
        preg_match("/##buttonTemplate=(.+)##/", $this->menuTemplate, $this->buttonTemplate);
        $this->buttonTemplate = $this->buttonTemplate[1];

        $this->getBranches();

        $replacements = array(
            'buttonTemplate=(.+)'   => '',
            'topButtons'            => $this->buildTopButtons(),
            'links'                 => $this->parseBranches(),
        );
        return TemplatesController::replaceInTemplate($this->menuTemplate, $replacements);
    }

    private function buildTopButtons () {
        $topButtons = array (
            (object) array('value' => '/', 'name' => 'Home'),
        );

        $buttons = '';
        foreach ($topButtons as $button) {
            $buttons .= $this->buildButton((object) array('name' => $button->name, 'value' => $button->value));
        }

        return $buttons;
    }

    private function getBranches () {
        $root = Settings::ROOT_PATH;
        $branchesRaw = shell_exec("cd $root && git branch");
        $branches = explode("\n", $branchesRaw);

        foreach ($branches as $branch) {
            $exploded = explode('/', $branch);
            $value = trim(str_replace('*', '', $branch));

            if (count($exploded) > 1) {
                $trimmedParent = trim($exploded[0]);
                $trimmedChild = trim($exploded[1]);
                $replacedBranch = trim(str_replace('*', '', $exploded[0]));

                if (array_key_exists($replacedBranch, $this->branches)) {
                    $this->branches[$replacedBranch]['children'][] = array(
                        'value'     => "/branch/$value",
                        'name'      => $trimmedChild,
                        'current'   => (substr($trimmedParent, 0, 1) == '*') ? true : false,
                        'child'     => true,
                    );
                } else {
                    $this->branches[$replacedBranch] = array(
                        'name'      => (substr($trimmedParent, 0, 1) == '*') ? $replacedBranch : $trimmedParent,
                        'parent'    => true,
                        'children'  => array(
                            array(
                                'value'     => "/branch/$value",
                                'name'      => $trimmedChild,
                                'current'   => (substr($trimmedParent, 0, 1) == '*') ? true : false,
                                'child'     => true,
                            ),
                        )
                    );
                }
            } elseif ($exploded[0] != '' && trim($exploded[0]) != 'origin') {
//                $trimmedBranch = trim($branch);
//                $replacedBranch = trim(str_replace('*', '', $trimmedBranch));
//
//                if (!in_array($trimmedBranch, $this->branches)) {
//                    $this->branches[trim($branch)] = array(
//                        'name'      => (substr($trimmedBranch, 0, 1) == '*') ? $replacedBranch : $trimmedBranch,
//                        'current'   => (substr($trimmedBranch, 0, 1) == '*') ? true : false,
//                    );
//                }
            }
        }
    }

    private function parseBranches () {
        $buttons = '';
        foreach ($this->branches as $branch) {
            $branch = (object) $branch;
            $buttons .= $this->buildButton($branch);
            if (isset($branch->children)) {
                foreach ($branch->children as $childBranch) {
                    $childBranch = (object) $childBranch;
                    $buttons .= $this->buildButton($childBranch);
                }
            }
        }

        return $buttons;
    }

    private function buildButton ($branch) {
        $current = (isset($branch->child) && $branch->child) ? ' (current)' : ' <small><small><b>(current)</b></small></small>';
        $current = (isset($branch->current) && $branch->current) ? $current : '';

        $child = (isset($branch->child) && $branch->child) ? ' child' : '';
        $parent = (isset($branch->parent) && $branch->parent) ? ' parent' : '';
        $value = (isset($branch->value) && $branch->value) ? " onclick=\"location.href='$branch->value'\"" : '';

        $replacements = array (
            'current'   => $current,
            'child'     => $child,
            'parent'    => $parent,
            'name'      => $branch->name,
            'id'        => ucfirst($branch->name),
            'link'      => $value,
        );
        $button = TemplatesController::replaceInTemplate($this->buttonTemplate, $replacements, '@@');

        return $button."\n";
    }
}