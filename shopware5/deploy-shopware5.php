<?php

namespace Deployer;

require 'recipe/common.php';
require 'recipe/rsync.php';
require 'recipe/cachetool.php';

set('console', 'bin/console');
set('shopware_build_path', '/tmp/build');

task('shopware:build:prepare', function () {
    run('mkdir -p {{shopware_build_path}}');
})->setPrivate()->local();

task('shopware:filesystem:deploy', function () {
    run('rsync --recursive {{source_directory}}/ {{shopware_build_path}}/');
})->setPrivate()->local();

//region install/update
task('shopware5:install:download', function () {
    run('curl -sL {{shopware_install_url}} -o {{shopware_build_path}}/download.zip');
    run('cd {{shopware_build_path}} && unzip -qqu download.zip && rm -rf download.zip');
})->setPrivate()->local();

task('shopware5:install:execute', function () {
    run('touch {{shopware_build_path}}/recovery/install/data/install.lock');
})->setPrivate()->local();

task('shopware5:update:local', function () {
    if (test('cd {{shopware_build_path}} && {{bin/php}} bin/console k10r:update:needed {{shopware_target_version}} -q')) {
        run('curl -sL {{shopware_update_url}} -o {{shopware_build_path}}/download.zip');
        run('mkdir update-temp');
        run('cd {{shopware_build_path}} && unzip -qq download.zip -d update-temp && rm download.zip');
        run('rsync --ignore-existing --recursive update-temp/ {{shopware_build_path}}/');
        run('rm -rf update-temp');
    }
})->setPrivate()->local();

task('shopware5:update:remote', function () {
    if (test('[ -d {{release_path}}/update-assets ]')) {
        run('cd {{release_path}} && {{bin/php}} recovery/update/index.php -n');
        run('rm -rf {{release_path}}/update-assets');
        run('rm -rf {{release_path}}/recovery');
        run('mkdir -p {{release_path}}/recovery/install/data');
        run('touch {{release_path}}/recovery/install/data/install.lock');
    }
});
//endregion

//region plugin commands
task('shopware5:plugins:install:remote', function () {
    run('cd {{release_path}} && {{bin/php}} {{console}} sw:plugin:refresh -q');
    run('cd {{release_path}} && {{bin/php}} bin/console sw:plugin:reinstall K10rDeployment');
    foreach (get('plugins') as $plugin) {
        run("cd {{release_path}} && {{bin/php}} {{console}} k10r:plugin:install {$plugin} --activate");
    }
});

task('shopware5:plugins:require:deployment:local', function () {
    foreach (get('composer_plugins') as $dependency) {
        run("cd {{shopware_build_path}} && {{bin/composer}} -q require --ignore-platform-reqs --optimize-autoloader --prefer-dist --no-ansi --update-no-dev --no-scripts {$dependency}");
    }
})->desc('Add required composer-based Shopware plugins for deployment in deployment environment')->setPrivate()->local();
//endregion

//region Config

task('shopware5:config:plugins:remote', function () {
    foreach (get('plugin_config') as $pluginName => $pluginConfigs) {
        foreach ($pluginConfigs as $pluginConfig) {
            run(
                sprintf(
                    'cd {{release_path}} && {{bin/php}} {{console}} sw:plugin:config:set "%s" "%s" "%s" "%s"',
                    $pluginName,
                    $pluginConfig['name'],
                    $pluginConfig['value'],
                    isset($pluginConfig['shopId']) ? "--shop {$pluginConfig['shopId']}" : ''
                )
            );
        }
    }
});

task('shopware5:config:theme:remote', function () {
    run("cd {{release_path}} && {{bin/php}} {{console}} sw:theme:synchronize -q");
    foreach (get('theme_config') as $themeName => $themeSettings) {
        foreach ($themeSettings as $setting => $value) {
            run(sprintf(
                'cd {{release_path}} && {{bin/php}} {{console}} k10r:theme:update -q --theme "%s" --setting "%s" --value "%s"',
                $themeName,
                $setting,
                $value
            ));
        }
    }
})->desc('Configure theme on remote server');

task('shopware5:config:shop:remote', function () {
    foreach (get('shopware_config') as $config) {
        run(sprintf(
            'cd {{release_path}} && {{bin/php}} {{console}} k10r:config:set "%s" "%s" "%s"',
            $config['name'],
            $config['value'],
            isset($config['shopId']) ? "--shop {$config['shopId']}" : ''
        ));
    }
})->desc('Update snippets and set Shopware core configuration on remote server');
//endregion

//region Cache
task('kellerkinder:shopware:cache', function () {
    run('cd {{release_path}} && {{bin/php}} {{console}} sw:cache:clear -q');
    run('cd {{release_path}} && {{bin/php}} {{console}} sw:theme:cache:generate -q');

    if (get('warm_cache_after_deployment', false)) {
        run('cd {{release_path}} && {{bin/php}} {{console}} sw:warm:http:cache -c -q');
    }
})->desc('Clear Shopware cache and warm up HTTP cache');
//endregion

//region env configuration
task('kellerkinder:shopware:staging', function () {
    run('cd {{shopware_build_path}} && {{bin/php}} {{console}} k10r:plugin:install --activate K10rStaging');
    run('cd {{shopware_build_path}} && {{bin/php}} {{console}} k10r:plugin:install --activate FroshMailCatcher');
})->desc('Set staging configuration');

task('kellerkinder:shopware:production', function () {})->desc('Set production configuration');
//endregion

task('deploy', [
    'deploy:info',
    'shopware:build:prepare',
    'shopware5:install:download',
    'shopware5:install:execute',
    'shopware5:plugins:require:deployment:local',
    'shopware5:update:local',
    'shopware:filesystem:deploy',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'rsync',
    'deploy:shared',
]);

task('deploy:test', [
    'deploy',
    'deploy:unlock',
    'cleanup',
    'success',
])->onStage('test')->desc('Tests a Shopware project deployment');

task('deploy:staging', [
    'deploy',
    'deploy:writable',
    'deploy:symlink',
    'shopware5:update:remote',
    'shopware5:plugins:install:remote',
    'shopware5:config:plugins:remote',
    'shopware5:config:shop:remote',
    'shopware5:config:theme:remote',
    'kellerkinder:shopware:staging',
    'kellerkinder:shopware:cache',
    'deploy:unlock',
    'cleanup',
    'success',
])->onStage('staging')->desc('Deploys a staging Shopware project');

task('deploy:production', [
    'deploy',
    'deploy:writable',
    'deploy:symlink',
    'shopware5:update:remote',
    'shopware5:plugins:install:remote',
    'shopware5:config:plugins:remote',
    'shopware5:config:shop:remote',
    'shopware5:config:theme:remote',
    'kellerkinder:shopware:production',
    'kellerkinder:shopware:cache',
    'deploy:unlock',
    'cleanup',
    'success',
])->onStage('production')->desc('Deploys a production Shopware project');
