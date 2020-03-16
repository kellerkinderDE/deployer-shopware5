<?php

namespace Deployer;

require 'recipe/common.php';
require 'recipe/rsync.php';
require 'recipe/cachetool.php';

set('console', 'bin/console');
set('shopware_build_path', '/tmp/build');

task('shopware:build:prepare', function () {
    run('mkdir -p {{shopware_build_path}}');
})->setPrivate()->isLocal();

task('shopware:filesystem:deploy', function () {
    run("cp -r {{source_directory}}/* {{shopware_build_path}}/");
})->setPrivate()->isLocal();

//region install/update
task('shopware5:install:download', function () {
    run('curl -sL {{shopware_install_url}} -o {{shopware_build_path}}/download.zip');
    run('cd {{shopware_build_path}} && unzip -qq download.zip && rm -rf download.zip');
})->setPrivate()->isLocal();

task('shopware5:install:execute', function () {
    run('cd {{shopware_build_path}} && {{php}} {{shopware_build_path}}/recovery/install/index.php --no-interaction \
    --db-host="mysql" --db-port="3306" --db-name="ci_db" --db-user="shopware" --db-password="app" \
    --shop-locale="de_DE" --shop-host="shop.local" --shop-path="/" --shop-name="test-shop" \
    --shop-email="test@example.com" --shop-currency="EUR" --admin-username="some-username" \
    --admin-password="some-password" --admin-email="test@example.com" --admin-name="Test Admin" \
    --admin-locale="de_DE"');
    run('touch {{shopware_build_path}}/recovery/install/data/install.lock');
})->setPrivate()->isLocal();

task('shopware5:update:local', function () {
    if (test('cd {{shopware_build_path}} && {{bin/php}} bin/console k10r:update:needed {{shopware_target_version}} -q')) {
        run('curl -sL {{shopware_update_url}} -o {{shopware_build_path}}/download.zip');
        run('mkdir update-temp');
        run('cd {{shopware_build_path}} && unzip -qq download.zip -d update-temp && rm download.zip');
        run('rsync --ignore-existing --recursive update-temp/ {{shopware_build_path}}/');
        run('rm -rf update-temp');

        run('cd {{shopware_build_path}} && {{bin/php}} recovery/update/index.php -n');
        run('rm -rf {{shopware_build_path}}/update-assets');
        run('rm -rf {{shopware_build_path}}/recovery');
        run('mkdir -p {{shopware_build_path}}/recovery/install/data');
        run('touch {{shopware_build_path}}/recovery/install/data/install.lock');
    }
});

task('shopware5:update:remote', function () {
    if (test('cd {{release_path}} && {{bin/php}} bin/console k10r:update:needed {{shopware_target_version}} -q')) {
        run('curl -sL {{shopware_update_url}} -o {{release_path}}/download.zip');
        run('mkdir update-temp');
        run('cd {{release_path}} && unzip -qq download.zip -d update-temp && rm download.zip');
        run('rsync --ignore-existing --recursive update-temp/ {{release_path}}/');
        run('rm -rf update-temp');

        run('cd {{release_path}} && {{bin/php}} recovery/update/index.php -n');
        run('rm -rf {{release_path}}/update-assets');
        run('rm -rf {{release_path}}/recovery');
        run('mkdir -p {{release_path}}/recovery/install/data');
        run('touch {{release_path}}/recovery/install/data/install.lock');
    }
});
//endregion

//region plugin commands
task('shopware5:plugins:install:local', function () {
    run('cd {{shopware_build_path}} && {{bin/php}} {{console}} sw:plugin:refresh -q');
    foreach (get('plugins') as $plugin) {
        run("cd {{shopware_build_path}} && {{bin/php}} {{console}} sw:plugin:install {$plugin} --activate");
    }
    run('cd {{shopware_build_path}} && {{bin/php}} {{console}} sw:cache:clear');
})->setPrivate()->isLocal();

task('shopware5:plugins:install:remote', function () {
    run('cd {{release_path}} && {{bin/php}} {{console}} sw:plugin:refresh -q');
    foreach (get('plugins') as $plugin) {
        run("cd {{release_path}} && {{bin/php}} {{console}} sw:plugin:install {$plugin} --activate");
    }
});

task('shopware5:plugins:require:deployment:local', function () {
    foreach (get('composer_plugins') as $dependency) {
        run("cd {{shopware_build_path}} && {{bin/composer}} -q require --ignore-platform-reqs --optimize-autoloader --prefer-dist --no-ansi --update-no-dev --no-scripts {$dependency}");
    }
})->desc('Add required composer-based Shopware plugins for deployment in deployment environment')->setPrivate()->isLocal();

task('shopware5:plugins:require:deployment:remote', function () {
    foreach (get('composer_plugins') as $dependency) {
        run("cd {{release_path}} && {{bin/composer}} -q require --ignore-platform-reqs --optimize-autoloader --prefer-dist --no-ansi --update-no-dev --no-scripts {$dependency}");
    }
})->desc('Add required composer-based Shopware plugins for deployment on remote server')->setPrivate()->isLocal();
//endregion

//region Config
task('shopware5:config:plugins:local', function () {
    foreach (get('plugin_config') as $pluginName => $pluginConfigs) {
        foreach ($pluginConfigs as $pluginConfig) {
            run(
                sprintf(
                    'cd {{shopware_build_path}} && {{bin/php}} {{console}} sw:plugin:config:set "%s" "%s" "%s" "%s"',
                    $pluginName,
                    $pluginConfig['name'],
                    $pluginConfig['value'],
                    isset($pluginConfig['shopId']) ? "--shop {$pluginConfig['shopId']}" : ''
                )
            );
        }
    }
})->setPrivate()->isLocal();

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

task('shopware5:config:theme:local', function () {
    run("cd {{shopware_build_path}} && {{bin/php}} {{console}} sw:theme:synchronize -q");
    foreach (get('theme_config') as $themeName => $themeSettings) {
        foreach ($themeSettings as $setting => $value) {
            run(sprintf(
                'cd {{shopware_build_path}} && {{bin/php}} {{console}} k10r:theme:update -q --theme "%s" --setting "%s" --value "%s"',
                $themeName,
                $setting,
                $value
            ));
        }
    }
})->desc('Configure theme on deployment environment')->setPrivate()->isLocal();

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

task('shopware5:config:shop:local', function () {
    //run("cd {{shopware_build_path}} && {{bin/php}} {{console}} k10r:snippets:update");
    foreach (get('shopware_config') as $config) {
        run(sprintf(
            'cd {{shopware_build_path}} && {{bin/php}} {{console}} k10r:config:set "%s" "%s" "%s"',
            $config['name'],
            $config['value'],
            isset($config['shopId']) ? "--shop {$config['shopId']}" : ''
        ));
    }
})->desc('Update snippets and set Shopware core configuration in deployment environment')->setPrivate()->isLocal();

task('shopware5:config:shop:remote', function () {
    run("cd {{release_path}} && {{bin/php}} {{console}} k10r:snippets:update");
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
    run(sprintf('cd {{shopware_build_path}} && {{bin/php}} {{console}} k10r:store:update --host %s --path %s --name %s --title %s',
            escapeshellarg(get('shop_host')),
            escapeshellarg(get('shop_path')),
            escapeshellarg(get('shop_name')),
            escapeshellarg(get('shop_title'))
        )
    );
})->desc('Set staging configuration');

task('kellerkinder:shopware:production', function () {})->desc('Set production configuration');
//endregion

task('deploy', [
    'deploy:info',
    'shopware:build:prepare',
    'shopware5:install:download',
    'shopware5:install:execute',
    'shopware5:update:local',
    'shopware:filesystem:deploy',
    'shopware5:plugins:require:deployment:local',
    'shopware5:plugins:install:local',
    'shopware5:config:plugins:local',
    'shopware5:config:shop:local',
    'shopware5:config:theme:local',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'rsync:warmup',
    'rsync',
]);

task('deploy:test', [
    'deploy',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success',
])->onStage('test')->desc('Tests a Shopware project deployment');

task('deploy:staging', [
    'deploy',
    'shopware5:plugins:require:deployment:remote',
    'deploy:symlink',
    'shopware5:update:remote',
    'shopware5:plugins:install:remote',
    'shopware5:config:plugins:remote',
    'shopware5:config:shop:remote',
    'shopware5:config:theme:remote',
    'kellerkinder:shopware:staging',
    'kellerkinder:shopware:cache',
    'cachetool:clear:opcache',
    'deploy:unlock',
    'cleanup',
    'success',
])->onStage('staging')->desc('Deploys a staging Shopware project');

task('deploy:production', [
    'deploy',
    'shopware5:plugins:require:deployment:remote',
    'deploy:symlink',
    'shopware5:update:remote',
    'shopware5:plugins:install:remote',
    'shopware5:config:plugins:remote',
    'shopware5:config:shop:remote',
    'shopware5:config:theme:remote',
    'kellerkinder:shopware:production',
    'kellerkinder:shopware:cache',
    'cachetool:clear:opcache',
    'deploy:unlock',
    'cleanup',
    'success',
])->onStage('production')->desc('Deploys a production Shopware project');
