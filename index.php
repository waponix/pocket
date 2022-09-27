<?php
require_once 'autoload.php';

use src\Pocket\Pocket;
use src\Request\HttpRequest;
use src\Request\Query;
use src\Request\Bag;

Pocket::configure([
    'parameterSource' => './parameters.json',
    'serviceMapping' => './services.json'
]);
$pocket = Pocket::getInstance();

var_dump($pocket->get(HttpRequest::class));