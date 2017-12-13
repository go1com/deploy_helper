<?php

namespace go1\deploy_helper\command;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServiceUpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('service:update')
            ->addArgument('service', InputArgument::REQUIRED)
            ->addArgument('environment', InputArgument::REQUIRED)
            ->addOption('delay', null, InputOption::VALUE_OPTIONAL, '', 5)
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, '', 20);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $input->getArgument('service');
        $environment = $input->getArgument('environment');
        $delay = $input->getOption('delay');
        $limit = $input->getOption('limit');
        $url = ('production' === $environment) ? 'https://api.mygo1.com/v3' : "http://api-{$environment}.mygo1.com/v3";
        $url .= "/{$service}-service/install";

        return $this->update($output, $url, $delay, $limit);
    }

    private function update(OutputInterface $output, $url, $delay, $limit)
    {
        if ($limit > 0) {
            $res = (new Client)->post($url, ['http_errors' => false]);
            if (!in_array($res->getStatusCode(), [200, 204, 400])) {
                $output->writeln("[FAILED] Status code: {$res->getStatusCode()}. Try again in 5 seconds.");
                sleep($delay);

                return $this->update($output, $url, $delay, $limit - 1);
            }

            return $output->writeln('[ERROR] Too many fails.');
        }

        return $output->writeln('[ERROR] Too many fails.');
    }
}
