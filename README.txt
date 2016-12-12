Deploy helper
====

## Build the command

1. Install https://github.com/clue/phar-composer
2. Build the source code: phar-composer build ./ -v

## Get the command

curl -sSL -o deploy_helper.phar https://github.com/go1com/deploy_helper/releases/download/v0.2/deploy_helper.phar

## Run /install

php deploy_helper.phar service:endpoint rules production

## Import endpoints

php deploy_helper.phar service:endpoint
    --endpoint=http://api-dev.mygo1.com/v3/endpoint-service/
    --source=/path/to/user/resources/swagger/
    --service=user
    --username=ADMIN_USERNAME
    --password=ADMIN_PASSWORD

## Variable building

Build variables values from #stash - The center we store all configurations.

php deploy_helper.phar service:build-docker-compose
    --stash-url=http://your.stash.service/build/docker-compose/ACCESS_TOKEN_TO_STASH_SERVICE
    --service=THE_SERVICE_YOU_ARE_BUILDING # Example: microservices:user
