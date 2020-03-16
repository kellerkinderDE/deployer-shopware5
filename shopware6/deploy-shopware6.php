<?php

namespace Deployer;

require 'recipe/common.php';
require 'recipe/rsync.php';
require 'recipe/cachetool.php';

set('console', 'bin/console');
set('shopware_build_path', '/tmp/build');
set('build_script_storefront', 'bin/build-storefront.sh');
set('build_script_administration', 'bin/build-administration.sh');
set('rsync', [
    'exclude'      => [
        '.git',
        'deploy.php',
    ],
    'exclude-file' => false,
    'include'      => [],
    'include-file' => false,
    'filter'       => [],
    'filter-file'  => false,
    'filter-perdir'=> false,
    'flags' => 'rzE',
    'options' => [
        'delete',
        'links',
    ],
    'timeout'      => 3600,
]);

task('shopware:build:prepare', function () {
    run('mkdir -p {{shopware_build_path}}');
})->isLocal();

task('shopware:filesystem:deploy', function () {
    run("cp -r {{source_directory}}/* {{shopware_build_path}}/");
})->isLocal();

//region install/update
task('shopware6:install:download', function() {
    run('curl -sL {{shopware_install_url}} -o {{shopware_build_path}}/download.zip');
    run('cd {{shopware_build_path}} && unzip -qq download.zip && rm -rf download.zip');
})->isLocal();

task('shopware6:install:execute', function() {
    run('cp -r .env.dist {{shopware_build_path}}/.env');
    run('cd {{shopware_build_path}} && {{bin/php}} {{console}} system:install -fnq --create-database');
})->isLocal();

task('shopware6:update', function () {
    // TODO: Add CLI helper to check installed version and compare to target version. Download and run update if necessary.
    if(false) {
        run('curl -sL {{release_path}} -o {{release_path}}/download.zip');
        run('mkdir update-temp');
        run('cd {{release_path}} && unzip -qq download.zip -d update-temp && rm download.zip');
        run('rsync --ignore-existing --recursive update-temp/ {{release_path}}/');
        run('rm -rf update-temp');

        run('cd {{release_path}} && {{bin/php}} {{console}} system:update:prepare -n');
        run('cd {{release_path}} && {{bin/php}} {{console}} system:update:finish -n');
    }
});
//endregion

//region build commands
//region production
/** @see https://github.com/shopware/production */
task('shopware6:build:production:prepare', function() {
    run('cd {{shopware_build_path}} && npm install --silent --prefix vendor/shopware/administration/Resources/');
    run('cd {{shopware_build_path}} && npm run --silent --prefix vendor/shopware/administration/Resources lerna -- bootstrap');
    run('cd {{shopware_build_path}} && npm install --silent --prefix vendor/shopware/administration/Resources/app/administration/build/nuxt-component-library/');
})->desc('[SW6] - [BUILD] Storefront')->isLocal();

task('shopware6:build:production:storefront', function() {
    run('cd {{shopware_build_path}} && {{build_script_storefront}}');
})->desc('[SW6] - [BUILD] Storefront')->isLocal();

task('shopware6:build:production:administration', function() {
    run('cd {{shopware_build_path}} && {{build_script_administration}}');
})->desc('[SW6] - [BUILD] Administration')->isLocal();

task('shopware6:build:production:all',
    [
        'shopware6:build:production:prepare',
        'shopware6:build:production:storefront',
        'shopware6:build:production:administration'
    ]
)->desc('Prepare and build storefront and administration')->isLocal();
//endregion
//endregion

//region plugin commands
task('shopware6:plugins:install:local', function () {
    run('cd {{shopware_build_path}} && {{bin/php}} {{console}} plugin:refresh');
    foreach (get('plugins') as $plugin) {
        run("cd {{shopware_build_path}} && {{bin/php}} {{console}} plugin:install {$plugin} --activate");
    }
    run('cd {{shopware_build_path}} && {{bin/php}} {{console}} cache:clear');
})->isLocal();

task('shopware6:plugins:install:remote', function () {
    run('cd {{release_path}} && {{bin/php}} {{console}} plugin:refresh');
    foreach (get('plugins') as $plugin) {
        run("cd {{release_path}} && {{bin/php}} {{console}} plugin:install {$plugin} --activate");
    }
});
//endregion

task('shopware6:cache:warm', function () {
    run('cd {{release_path}} && {{bin/php}} {{console}} cache:clear -q');
    run('cd {{release_path}} && {{bin/php}} {{console}} theme:compile -q');

    if (get('warm_cache_after_deployment', false)) {
        run('cd {{release_path}} && {{bin/php}} {{console}} http:cache:warm:up');
    }
});

task('deploy', [
    'deploy:info',
    'shopware:build:prepare',
    'shopware6:install:download',
    'shopware6:install:execute',
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
    'shopware6:update',
    'shopware6:plugins:install:remote',
    'cachetool:clear:opcache',
    'deploy:unlock',
    'cleanup',
    'success',
])->onStage('staging')->desc('Deploys a staging shopware project');

task('deploy:production', [
    'deploy',
    'shopware6:update',
    'shopware6:plugins:install:remote',
    'cachetool:clear:opcache',
    'deploy:unlock',
    'cleanup',
    'success',
])->onStage('production')->desc('Deploys a staging shopware project');
