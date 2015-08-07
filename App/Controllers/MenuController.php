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
                $trimmedBranch = trim($branch);
                $replacedBranch = trim(str_replace('*', '', $trimmedBranch));

                if (!in_array($trimmedBranch, $this->branches)) {
                    $this->branches[trim($branch)] = array(
                        'value'     => "/branch/$value",
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