<?php

require_once dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$cli = new \App\Estimations\Cli\CommandLineInterface($argv);
$cli->process();
