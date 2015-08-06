<?php


namespace CodeReviewPrototype\App\Controllers;


class ViewsController {

    private $html = null;
    private $htmlStart = null;
    private $htmlEnd = null;
    private $title = null;

    public function __construct($page) {
        if ($page == '') {
            throw new \Exception ('You must specify a page to load!');
        }

        $this->callChildClass($page);
    }

    private function callChildClass ($page) {
        $className = 'CodeReviewPrototype\App\Views\\'.$page.'View';
        $childClass = new $className($page);

        $methodsRaw = get_class_methods($childClass);
        $methods = array();
        foreach ($methodsRaw as $method) {
            if (strpos($method, 'render') !== false) {
                $methods[] = $method;
            }
        }

        echo '<pre>',var_dump($methods),'</pre>';
    }

    private function makeHTMLStrings () {
        $this->htmlStart = '<!DOCTYPE html>'.
            '<html>'.
            '<head lang="en">'.
            '<meta charset="UTF-8">'.
            "<title>CodeReview Prototype - $this->title</title>".
            '</head>'.
            '<body>';
        $this->htmlEnd = '</body>'.
            '</html>';

    }
}
