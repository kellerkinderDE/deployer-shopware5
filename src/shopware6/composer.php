<?php

namespace Deployer;

require '../global-base.php';
require 'sw6-base.php';

//region install/update
task('shopware6:install:download', function() {
    run('cd {{shopware_build_path}} && composer install --ignore-platform-reqs');
})->isLocal();

task('shopware6:install:execute', function() {
    run('cp -r .env.dist {{shopware_build_path}}/.env');
    run('cd {{shopware_build_path}} && {{bin/php}} {{console}} system:install -fnq --create-database');
})->isLocal();

task('shopware6:update:download', function () {
    if(get('execute_update')) {
        run('cd {{shopware_build_path}} && composer update --ignore-platform-reqs');
    }
})->isLocal();

task('shopware6:update:execute', function () {
    if(get('execute_update')) {
        run('cd {{shopware_build_path}} && {{bin/php}} {{console}} system:update -fnq');
    }
})->isLocal();
//endregion

task('deploy', [
    'deploy:info',
    'shopware:build:prepare',
    'shopware:filesystem:deploy',
    'shopware6:install:download',
    'shopware6:install:execute',
    'shopware6:update:download',
    'shopware6:update:execute',
    'shopware6:plugins:install:local',
    'shopware6:build:production:all',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'rsync:warmup',
    'rsync',
    'deploy:shared',
    'deploy:writable',
    'deploy:clear_paths',
    'deploy:symlink',
])->desc('Deploy your project');

task('deploy:test', [
    'deploy',
    'deploy:unlock',
    'cleanup',
    'success',
])->onStage('test')->desc('Deploys a test shopware project');

task('deploy:staging', [
    'deploy',
    'shopware6:plugins:install:remote',
    'cachetool:clear:opcache',
    'deploy:unlock',
    'cleanup',
    'success',
])->onStage('staging')->desc('Deploys a staging shopware project');

task('deploy:production', [
    'deploy',
    'shopware6:plugins:install:remote',
    'cachetool:clear:opcache',
    'deploy:unlock',
    'cleanup',
    'success',
])->onStage('production')->desc('Deploys a staging shopware project');
