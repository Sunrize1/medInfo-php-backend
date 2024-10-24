<?php
require_once 'vendor/autoload.php';
require_once 'connect.php';

use AltoRouter;

$router = new AltoRouter();

$router->setBasePath('/api');