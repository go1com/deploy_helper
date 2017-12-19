<?php

namespace go1\deploy_helper\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DockerFileGeneratingCommand extends Command
{
    protected function configure()
    {
        $this->setName('generate:docker-file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        file_put_contents(getcwd() . '/Dockerfile', implode("\n", [
            'FROM go1com/php:7-nginx',
            'COPY . /app',
            'RUN rm -rf /app/.git/ && chmod -Rf +w /app/cache/',
            'WORKDIR /app',
        ]));
    }
}
