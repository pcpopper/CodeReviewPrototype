<?php


namespace CodeReviewPrototype\App\Controllers;


class MenuController {

    private $rootPath = '/Users/JohnDoe/Sites/OBERD';
    private $cd = null;
    private $branches = array();
    private $buttonTemplate = null;
    private $menuTemplate = null;

    public function __construct ($menuTemplate) {
        $this->cd = "cd $this->rootPath";
        $this->menuTemplate = $menuTemplate;
    }

    public function renderMenu () {
        preg_match("/##buttonTemplate=(.+)##/", $this->menuTemplate, $this->buttonTemplate);
        $this->buttonTemplate = $this->buttonTemplate[1];
        $this->menuTemplate = preg_replace("/##buttonTemplate=(.+)##/", "", $this->menuTemplate);

        $this->menuTemplate = preg_replace("/##topButtons##/", $this->buildTopButtons(), $this->menuTemplate);
        $this->getBranches();
        return preg_replace("/##links##/", $this->parseBranches(), $this->menuTemplate);
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
                    $this->branches[$replacedBranch]['children'][] = array(
                        'value'     => $value,
                        'name'      => $trimmedChild,
                        'current'   => (substr($trimmedParent, 0, 1) == '*') ? true : false,
                        'child'     => true,
                        'parent'    => (substr($trimmedParent, 0, 1) == '*') ? $replacedBranch : $trimmedParent,
                    );
                } else {
                    $this->branches[$replacedBranch] = array(
                        'name'      => (substr($trimmedParent, 0, 1) == '*') ? $replacedBranch : $trimmedParent,
                        'parent'    => true,
                        'children'  => array(
                            array(
                                'value'     => $value,
                                'name'      => $trimmedChild,
                                'current'   => (substr($trimmedParent, 0, 1) == '*') ? true : false,
                                'child'     => true,
                                'parent'    => (substr($trimmedParent, 0, 1) == '*') ? $replacedBranch : $trimmedParent,
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
        $button = $this->buttonTemplate;

        $current = (isset($branch->current) && $branch->current) ? ' (current)' : '';
        $button = preg_replace("/@@current@@/", $current, $button);

        $child = (isset($branch->child) && $branch->child) ? ' child' : '';
        $button = preg_replace("/@@child@@/", $child, $button);

        $parent = (isset($branch->parent) && $branch->parent) ? ' parent' : '';
        $button = preg_replace("/@@parent@@/", $parent, $button);

        $button = preg_replace("/@@name@@/", $branch->name, $button);
        $button = preg_replace("/@@id@@/", ucfirst($branch->name), $button);

        $value = (isset($branch->value) && $branch->value) ? "<a href=\"/branch/$branch->value\">" : '';
        $button = preg_replace("/@@a@@/", $value, $button);
        $value = (isset($branch->value) && $branch->value) ? "</a>" : '';
        $button = preg_replace("/@@\/a@@/", $value, $button);

        return $button."\n";
    }
}