<?php
require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/../cfg.php';

$configurator = new Nette\Configurator;
$configurator->enableTracy(PATH_LOG);
$configurator->setTimeZone('Europe/Prague');
$configurator->setTempDirectory(PATH_TEMP);

$configurator->createRobotLoader()
	->addDirectory(PATH_APP)
	->register();

$configurator->addConfig(__DIR__ . '/config.neon');

$container = $configurator->createContainer();
return $container;