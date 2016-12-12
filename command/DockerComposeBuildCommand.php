<?php

namespace go1\deploy_helper\command;

use GuzzleHttp\Client;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class DockerComposeBuildCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('service:build-docker-compose')
            ->addOption('stash-url', null, InputOption::VALUE_OPTIONAL)
            ->addOption('service', null, InputOption::VALUE_OPTIONAL)
            ->addOption('access-token', null, InputOption::VALUE_OPTIONAL)
            ->addOption('ci-step', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stashUrl = rtrim($input->getOption('stash_url'), '/');
        $service = $input->getOption('service');
        $file = getcwd() . '/docker-compose.yml';

        if (!is_file($file)) {
            throw new RuntimeException("Docker compose file not found: {$file}");
        }

        return $this->doExecute($stashUrl, $service, $file);
    }

    private function doExecute(string $stashUrl, string $service, string $file)
    {
        $res = (new Client)
            ->post($stashUrl, [
                'http_errors' => false,
                'json'        => [
                    'service'     => $service,
                    'environment' => $_ENV,
                    'content'     => Yaml::parse(file_get_contents($file)),
                ],
            ]);

        if (200 != $res->getStatusCode()) {
            throw new RuntimeException('Failed to parse docker-compose file: ' . $res->getBody()->getContents());
        }

        file_put_contents($file, Yaml::dump($res));
    }
}
