<?php
namespace Controllers;

use HTMLTemplateRenderer;

class oldPageController
{
    public function redirect()
    {
        if (isset($_GET['username'])) {
            header('Location: ' . $_GET['username'], true, 301);
            exit;
        }
    }
}