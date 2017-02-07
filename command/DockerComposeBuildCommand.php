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
            ->addOption('stash-url', null, InputOption::VALUE_REQUIRED)
            ->addOption('service', null, InputOption::VALUE_REQUIRED)
            ->addOption('dry-run', null, InputOption::VALUE_OPTIONAL, '', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dry = $input->hasParameterOption('--dry-run');
        $stashUrl = rtrim($input->getOption('stash-url'), '/');
        $service = $input->getOption('service');
        $file = getcwd() . '/docker-compose.yml';

        if (!is_file($file)) {
            throw new RuntimeException("Docker compose file not found: {$file}");
        }

        if ($compose = $this->doExecute($stashUrl, $service, $file)) {
            if ($compose = Yaml::dump($compose, 4)) {
                $dry
                    ? $output->writeln($compose)
                    : file_put_contents($file, $compose);
            }
        }
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

        if ($compose = $res->getBody()->getContents()) {
            if ($compose = json_decode($compose, true)) {
                return $compose;
            }
        }
    }
}
