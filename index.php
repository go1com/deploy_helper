<?php

namespace go1\ci_helper;

use go1\deploy_helper\command\EndpointBuilderCommand;
use go1\deploy_helper\command\HipchatNotificationCommand;
use go1\deploy_helper\command\ServiceUpdateCommand;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/vendor/autoload.php';

$app = new Application('GO1');
$app->addCommands([
    new ServiceUpdateCommand,
    new EndpointBuilderCommand,
    new HipchatNotificationCommand,
]);

return $app->run();
