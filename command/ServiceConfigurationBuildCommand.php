<?php

namespace go1\deploy_helper\command;

use GuzzleHttp\Client;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ServiceConfigurationBuildCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('config:variables')
            ->addArgument('stash_url', InputArgument::REQUIRED)
            ->addArgument('service', InputArgument::REQUIRED)
            ->addArgument('access_token', InputArgument::REQUIRED)
            ->addOption('ci-step', InputOption::VALUE_OPTIONAL);
    }

    /**
     * Example config.default.php file
     * ---------------------
     *
     *  return [
     *      'cacheOptions' => (getenv('CACHE_BACKEND') && 'memcached' === getenv('CACHE_BACKEND'))
     *          ? ['backend' => 'memcached', 'host' => getenv('CACHE_HOST'), 'port' => getenv('CACHE_PORT')]
     *          : ['backend' => 'filesystem', 'directory' => __DIR__ . '/cache'],
     *      'stash' => [
     *          'CACHE_BACKEND' => 'GO1_CACHE_BACKEND',
     *          'CACHE_HOST'    => 'GO1_CACHE_HOST',
     *          'CACHE_PORT'    => 'GO1_CACHE_PORT',
     *      ]
     *  ];
     *
     * The command will fetch #stash for these variables: GO1_CACHE_BACKEND, GO1_CACHE_HOST, GO1_CACHE_PORT.
     * Then the value will be passed to .gitlab-ci.yml
     *
     * .gitlab-ci.yml before:
     * ---------------------
     *
     *  deploy-step:
     *      variables:
     *          CACHE_BACKEND: filesystem # Other variables are not existing
     *
     * .gitlab-ci.yml after:
     * ---------------------
     *
     *  deploy-step:
     *      variables:
     *          CACHE_BACKEND: memcached           # The value is replaced if previous variable found.
     *          CACHE_HOST: memcached.go1.service  # The value is appended if variable not found.
     *          CACHE_PORT: 11211
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stashUrl = rtrim($input->getArgument('stash_url'), '/');
        $service = $input->getArgument('service');
        $accessToken = $input->getArgument('access_token');
        $file = getcwd() . '/config.default.php';
        $step = $input->getOption('ci-step') ?: null;

        if (!is_file($file)) {
            throw new RuntimeException("Configuration not found: {$file}");
        }

        $cnf = require getcwd() . '/config.default.php';
        if (!isset($cnf['stash'])) {
            $output->writeln("<error>No #stash configuration found.</error>");

            return null;
        }

        $mapping = $cnf['stash'];
        $replace = [];

        foreach ($this->fetch($stashUrl, $accessToken, $service, array_values($mapping)) as $stashKey => $value) {
            foreach ($mapping as $find => $key) {
                if ($key === $stashKey) {
                    $replace[$find] = $value;
                }
            }
        }

        $this->replace($step, $replace);
    }

    private function fetch(string $stashUrl, string $accessToken, $service, array $variables): array
    {
        $response = (new Client)
            ->post("{$stashUrl}/fetch/{$service}/{$accessToken}", [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => ['variables' => $variables],
            ]);

        if (200 == $response->getStatusCode()) {
            return json_decode($response->getBody()->getContents());
        }
    }

    private function replace(string $step = null, array $replace = [])
    {
        $definitions = getcwd() . '/.gitlab-ci.yml';
        $definitions = Yaml::parse(file_get_contents($definitions));
        if (is_array($definitions)) {
            foreach ($definitions as $key => &$definition) {
                if (is_null($step) || ($key === $key)) {
                    if (isset($definition['variables'])) {
                        foreach (array_keys($definition['variables']) as $variableName) {
                            if (isset($replace[$variableName])) {
                                $definition['variables'][$variableName] = $replace[$variableName];
                            }
                        }
                    }
                }
            }
        }
    }
}
