<?php

namespace Deployer;

require '../global-base.php';

//region install/update
task('shopware5:install:download', function () {
    run('curl -sL {{shopware_install_url}} -o {{shopware_build_path}}/download.zip');
    run('cd {{shopware_build_path}} && unzip -qq download.zip && rm -rf download.zip');
})->isLocal();

task('shopware5:install:execute', function () {
    $dbConfig = get('shopware_db_config');
    set('dbHost', $dbConfig['dbHost']?: 'mysql');
    set('dbPort', $dbConfig['dbPort']?: '3306');
    set('dbName', $dbConfig['dbName']?: 'ci_db_5');
    set('dbUser', $dbConfig['dbUser']?: 'root');
    set('dbPassword', $dbConfig['dbPassword'] ?: 'root');

    run('cd {{shopware_build_path}} && {{php}} {{shopware_build_path}}/recovery/install/index.php --no-interaction \
    --db-host="{{dbHost}}" --db-port="{{dbPort}}" --db-user="{{dbName}}" --db-password="{{dbPassword}}" --db-name="{{dbName}}"
    --shop-locale="de_DE" --shop-host="HOST" --shop-path="/" --shop-name="SHOPNAME" \
    --shop-email="EMAIL" --shop-currency="EUR" --admin-username="ADMIN-USERNAME" \
    --admin-password="ADMIN-PASSWORT" --admin-email="ADMIN-EMAIL" --admin-name="ADMIN-NAME" \
    --admin-locale="de_DE"');
    run('touch {{shopware_build_path}}/recovery/install/data/install.lock');
})->isLocal();

task('shopware5:update:download', function () {
    if((bool)get('execute_update')) {
        run('curl -sL {{shopware_update_url}} -o {{shopware_build_path}}/download.zip');
        run('cd {{shopware_build_path}} && unzip -qq download.zip && rm -rf download.zip');
        run('rm -rf {{shopware_build_path}}/update-assets');
    }
})->isLocal();

task('shopware5:update:execute', function () {
    if((bool)get('execute_update')) {
        run('rm -rf {{shopware_build_path}}/recovery/install/data/install.lock');
        run('cd {{shopware_build_path}} && {{php}} recovery/update/index.php -n');
        run('rm -rf {{shopware_build_path}}/update-assets');
        run('touch {{shopware_build_path}}/recovery/install/data/install.lock');
    }
})->isLocal();
//endregion

task('deploy', [
    'deploy:info',
    'shopware:build:prepare',
    'shopware5:install:download',
    'shopware5:install:execute',
    'shopware5:update:download',
    'shopware5:update:execute',
    'shopware:filesystem:deploy',
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
