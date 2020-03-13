<?php

namespace Deployer;

require '../global-base.php';
require 'sw6-base.php';

//region install/update
task('shopware6:install:download', function() {
    run('curl -sL {{shopware_install_url}} -o {{shopware_build_path}}/download.zip');
    run('cd {{shopware_build_path}} && unzip -qq download.zip && rm -rf download.zip');
})->isLocal();

task('shopware6:install:execute', function() {
    run('cp -r .env.dist {{shopware_build_path}}/.env');
    run('cd {{shopware_build_path}} && {{bin/php}} {{console}} system:install -fnq --create-database');
})->isLocal();

task('shopware6:update:download', function () {
    if(get('execute_update')) {
        run('curl -sL {{shopware_update_url}} -o {{shopware_build_path}}/download.zip');
        run('cd {{shopware_build_path}} && unzip -qq download.zip && rm -rf download.zip');
    }
})->isLocal();

task('shopware6:update:execute', function () {
    if(get('execute_update')) {
//        TODO
    }
})->isLocal();
//endregion

task('deploy', [
    'deploy:info',
    'shopware:build:prepare',
    'shopware6:install:download',
    'shopware6:install:execute',
    'shopware6:update:download',
    'shopware6:update:execute',
    'shopware:filesystem:deploy',
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
