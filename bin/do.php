<?php

use Kucbel\Console\Application;
use Nette\DI\Container;

/** @var Container $container */
$container = require __DIR__ . '/../app/bootstrap.php';

/** @var Application $application */
$application = $container->getService('console');
$application->run();