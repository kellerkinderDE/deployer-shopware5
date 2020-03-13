<?php

namespace Deployer;

require '../global-base.php';
require 'sw5-base.php';

//region install/update
task('shopware5:install:download', function () {
    /**
     * Will be removed after the install script provides a no-interaction mode
     * @see https://github.com/shopware/composer-project/pull/33/files
     */
    run('cd {{shopware_build_path}} && curl https://raw.githubusercontent.com/jinnoflife/sw5-composer-project/feature/no_interaction/app/bin/functions.sh > app/bin/functions.sh');
    run('cd {{shopware_build_path}} && curl https://raw.githubusercontent.com/jinnoflife/sw5-composer-project/feature/no_interaction/app/bin/install.sh > app/bin/install.sh');
    run('cp -r .env.dist {{shopware_build_path}}/.env');
    run('cd {{shopware_build_path}} && composer install --ignore-platform-reqs');
})->isLocal();

task('shopware5:install:execute', function () {
    run('cd {{shopware_build_path}} && ./app/bin/install.sh -n');
})->isLocal();

task('shopware5:update', function () {
    if((bool)get('execute_update')) {
        run('cd {{shopware_build_path}} && composer update --ignore-platform-reqs');
    }
})->isLocal();
//endregion

task('deploy', [
    'deploy:info',
    'shopware:build:prepare',
    'shopware:filesystem:deploy',
    'shopware5:install:download',
    'shopware5:install:execute',
    'shopware5:update',
    'shopware5:plugins:require:deployment',
    'shopware5:plugins:install:local',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'rsync:warmup',
    'rsync',
    'deploy:symlink',
]);

task('deploy:test', [
    'deploy',
    'deploy:unlock',
    'cleanup',
    'success',
])->onStage('test')->desc('Deploys a test shopware project');

task('deploy:staging', [
    'deploy',
    'shopware5:plugins:install:remote',
    'kellerkinder:shopware:configure',
    'kellerkinder:shopware:staging',
    'cachetool:clear:opcache',
    'deploy:unlock',
    'cleanup',
    'success',
])->onStage('staging')->desc('Deploys a staging shopware project');

task('deploy:production', [
    'deploy',
    'shopware5:plugins:install:remote',
    'kellerkinder:shopware:configure',
    'kellerkinder:shopware:production',
    'cachetool:clear:opcache',
    'deploy:unlock',
    'cleanup',
    'success',
])->onStage('production')->desc('Deploys a staging shopware project');
