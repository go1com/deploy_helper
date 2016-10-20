<?php

namespace go1\deploy_helper\command;

use GuzzleHttp\Client;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class EndpointBuilderCommand extends Command
{
    private $client;

    public function __construct()
    {
        parent::__construct();

        $this->client = new Client(['http_errors' => true]);
    }

    protected function configure()
    {
        $this
            ->setName('service:endpoint')
            ->addOption('endpoint', null, InputOption::VALUE_REQUIRED)
            ->addOption('service', null, InputOption::VALUE_REQUIRED)
            ->addOption('source', null, InputOption::VALUE_REQUIRED)
            ->addOption('username', null, InputOption::VALUE_REQUIRED)
            ->addOption('password', null, InputOption::VALUE_REQUIRED);
    }

    private function jwt($userUrl, $username, $password)
    {
        $login = (new Client(['http_errors' => false]))->post("$userUrl/account/login", [
            'headers' => ['Content-Type' => 'application/json'],
            'json'    => [
                'username' => $username,
                'password' => $password,
            ],
        ]);

        return (200 == $login->getStatusCode())
            ? json_decode($login->getBody()->getContents())->jwt
            : false;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $endpointUrl = rtrim($input->getOption('endpoint'), '/');
        $service = $input->getOption('service');
        $source = $input->getOption('source');
        $access = 'root'; # Just root for now.
        $timestamp = time();

        $jwt = null;
        if (strpos($endpointUrl, 'mygo1.com')) {
            $userUrl = str_replace('/endpoint-', '/user-', $endpointUrl);
            $username = $input->getOption('username');
            $password = $input->getOption('password');
            $jwt = $this->jwt($userUrl, $username, $password);
            if (!$jwt) {
                throw new RuntimeException('Invalid user.');
            }
        }

        $this->push($service, $source, $endpointUrl, $access, $timestamp, $jwt);
    }

    private function push($service, $source, $endpointUrl, $access, $timestamp, $jwt = null)
    {
        $headers = $jwt ? ['Authorization' => "Bearer $jwt"] : [];

        foreach (glob("$source/resources/*.*") as $file) {
            return $this->pushEndpoints($file, $endpointUrl, $service, $access, $headers, $timestamp);
        }

        foreach (glob("$source/definitions/*.*") as $file) {
            return $this->pushDefinitions($file, $endpointUrl, $service, $headers, $timestamp);
        }

        $timestamp -= 2;
        $url = "{$endpointUrl}/endpoint/{$service}/{$timestamp}";
        (new Client(['http_errors' => true]))->delete($url, ['headers' => $headers]);
    }

    private function pushEndpoints($file, $endpointUrl, $service, $access, $headers, $timestamp)
    {
        $json = file_get_contents($file);
        $json = strpos($file, '.yml') ? Yaml::parse($json) : json_decode($json, true);
        if (!$json) {
            throw new RuntimeException("Failed to read: $file");
        }

        foreach ($json as $pattern => $paths) {
            foreach ($paths as $method => $endpoint) {
                $this->client->put("{$endpointUrl}/endpoint/{$service}/{$method}/$access", [
                    'headers' => $headers,
                    'json'    => [
                        'pattern'     => $pattern,
                        'description' => isset($endpoint['description']) ? $endpoint['description'] : '',
                        'parameters'  => isset($endpoint['parameters']) ? $endpoint['parameters'] : null,
                        'responses'   => isset($endpoint['responses']) ? $endpoint['responses'] : null,
                        'timestamp'   => $timestamp,
                    ],
                ]);
            }
        }
    }

    private function pushDefinitions($file, $endpointUrl, $service, $headers, $timestamp)
    {
        $json = file_get_contents($file);
        $json = strpos($file, '.yml') ? Yaml::parse($json) : json_decode($json, true);
        if (!$json) {
            throw new RuntimeException("Failed to read: $file");
        }

        foreach ($json as $name => $definition) {
            $type = $definition['type'];

            $this->client->put("{$endpointUrl}/definition/{$service}/{$name}/{$type}", [
                'headers' => $headers,
                'json'    => $json = [
                    'properties' => isset($definition['properties']) ? $definition['properties'] : '',
                    'timestamp'  => $timestamp,
                ],
            ]);
        }
    }
}
