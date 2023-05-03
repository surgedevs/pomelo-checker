<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'srcautoload.php';

$router = new Router();

$router->addRoute('/api/v1/request/{name}', 'Controllers\APIV1Controller@request');
$router->addRoute('/api/v1/text/{name}', 'Controllers\APIV1Controller@text');
$router->addRoute('/api/v1/query', 'Controllers\APIV1Controller@query');

$router->addRoute('/api/v1/list', 'Controllers\APIV1Controller@list');
$router->addRoute('/api/v1/list/{code}', 'Controllers\APIV1Controller@list');

$router->addRoute('/pomelo.php', 'Controllers\oldPageController@redirect');

$router->addRoute('/', 'Controllers\HomepageController@index');
$router->addRoute('/{name}', 'Controllers\HomepageController@checker');
$router->addRoute('/{name}/stringify', 'Controllers\HomepageController@checkerStringify');

$router->route($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);