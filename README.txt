Deploy helper
====

## Build the command

1. Install https://github.com/clue/phar-composer
2. Build the source code: phar-composer build ./ -v

## Usage

curl -sSL -o deploy_helper.phar https://github.com/go1com/deploy_helper/releases/download/v0.1/deploy_helper.phar
php deploy_helper.php production rules
