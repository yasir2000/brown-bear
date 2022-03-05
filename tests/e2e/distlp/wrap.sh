#!/usr/bin/env bash

# This is the central script to execute in order to execute "whole platform integration tests"
# It is meant to be called without any arguments
# This will bring-up the platform, run the tests, stop and remove everything

set -ex

MAX_TEST_EXECUTION_TIME='30m'
TIMEOUT="$(command -v gtimeout || echo timeout)"

case "${1:-}" in
    "mysql57")
    export DB_HOST="mysql57"
    ;;
    "mysql80")
    export DB_HOST="mysql80"
    ;;
    *)
    echo "A database type must be provided as parameter. Allowed values are:"
    echo "* mysql57"
    echo "* mysql80"
    exit 1
esac

test_results_folder='./test_results_distlp'
if [ -n "$2" ]; then
    test_results_folder="$2"
fi

DOCKERCOMPOSE="docker-compose -f docker-compose-distlp-tests.yml -f tests/e2e/docker-compose-db-${DB_HOST}.yml -p distlp-tests-${BUILD_TAG:-dev}"

cypress_version="$(python3 -c 'import json,sys;print(json.load(sys.stdin)["version"], end="")' < ./node_modules/cypress/package.json)"

clean_env() {
    $DOCKERCOMPOSE down --remove-orphans --volumes || true
}

wait_until_tests_are_executed() {
    local test_cli_container_id="$($DOCKERCOMPOSE ps -q test-cli)"
    local test_cypress_container_id="$($DOCKERCOMPOSE ps -q test-cypress)"

    $TIMEOUT "$MAX_TEST_EXECUTION_TIME" docker wait "$test_cli_container_id" "$test_cypress_container_id" || \
        echo 'Tests take to much time to execute. End of execution will not be waited for!'
}

mkdir -p "$test_results_folder" || true
rm -rf "$test_results_folder/*" || true
clean_env

TEST_RESULT_OUTPUT="$test_results_folder" CYPRESS_VERSION="$cypress_version" $DOCKERCOMPOSE up -d --build

wait_until_tests_are_executed

backend_svn_container_id="$($DOCKERCOMPOSE ps -q backend-svn)"
mkdir -p "$test_results_folder/logs/backend-svn"
docker cp ${backend_svn_container_id}:/var/log/nginx/ "$test_results_folder/logs/backend-svn"
docker cp ${backend_svn_container_id}:/var/log/httpd/ "$test_results_folder/logs/backend-svn"
docker cp ${backend_svn_container_id}:/var/log/tuleap/ "$test_results_folder/logs/backend-svn"
docker cp ${backend_svn_container_id}:/var/opt/remi/php80/log/php-fpm/ "$test_results_folder/logs/backend-svn/"
$DOCKERCOMPOSE logs backend-svn > "$test_results_folder/logs/backend-svn/backend-svn.log"

backend_web_container_id="$($DOCKERCOMPOSE ps -q backend-web)"
mkdir -p "$test_results_folder/logs/backend-web"
docker cp ${backend_web_container_id}:/var/log/nginx/ "$test_results_folder/logs/backend-web"
docker cp ${backend_web_container_id}:/var/log/tuleap/ "$test_results_folder/logs/backend-web"
docker cp ${backend_web_container_id}:/var/opt/remi/php80/log/php-fpm/ "$test_results_folder/logs/backend-web"
$DOCKERCOMPOSE logs backend-web > "$test_results_folder/logs/backend-web/backend-web.log"

$DOCKERCOMPOSE logs test-cli > "$test_results_folder/logs/test-cli.log"
$DOCKERCOMPOSE logs test-cypress > "$test_results_folder/logs/test-cypress.log"
$DOCKERCOMPOSE logs reverse-proxy > "$test_results_folder/logs/reverse-proxy.log"

clean_env
