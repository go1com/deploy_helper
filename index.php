<?php

namespace go1\ci_helper;

use GuzzleHttp\Client;

require_once __DIR__ . '/vendor/autoload.php';

$env = isset($argv[1]) ? $argv[1] : null;
$service = isset($argv[2]) ? $argv[2] : null;
if (!$env || !$service) {
    echo "Usage: php deploy_helper.phar ENV SERVICE. Example: php deploy_helper.phar production rules\n";
    exit;
}

$url = ('production' === $env) ? 'https://api.mygo1.com/v3' : "http://api.{$env}.mygo1.com/v3";
$url .= "/{$service}-service/install";
$try = 0;

while (true) {
    if (10 >= $try++) {
        $client = new Client;
        $response = $client->get($url, ['http_errors' => false]);
        if (!in_array($response->getStatusCode(), [200, 204, 400])) {
            echo "[FAILED] Status code: {$response->getStatusCode()}. Try again in 5 seconds.\n";
            sleep(5);
            continue;
        }

        echo "[OK] Status code: {$response->getStatusCode()} \n";

        break;
    }

    echo "[ERROR] Too many fails.\n";
}
