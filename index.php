<?php

namespace go1\deploy_helper;

use go1\deploy_helper\command\DockerComposeBuildCommand;
use go1\deploy_helper\command\DockerFileGeneratingCommand;
use go1\deploy_helper\command\EndpointBuilderCommand;
use go1\deploy_helper\command\HipchatNotificationCommand;
use go1\deploy_helper\command\ServiceUpdateCommand;
use go1\deploy_helper\command\TranslationExtractorCommand;
use Symfony\Component\Console\Application;

is_file($loader = __DIR__ . '/vendor/autoload.php') && require_once $loader;
is_file($loader = __DIR__ . '/../../../vendor/autoload.php') && require_once $loader;

$app = new Application('GO1');
$app->addCommands([
    new ServiceUpdateCommand,
    new DockerFileGeneratingCommand,
    new DockerComposeBuildCommand,
    new EndpointBuilderCommand,
    new HipchatNotificationCommand,
    new TranslationExtractorCommand,
]);

return $app->run();
