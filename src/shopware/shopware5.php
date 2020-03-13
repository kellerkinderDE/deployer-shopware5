<?php

namespace Deployer;

require 'base.php';

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
    --db-host="{{dbHost}}" --db-port="{{dbPort}}" --db-name="{{dbName}}" --db-user="{{dbUser}}" --db-password="{{dbPassword}}" \
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

//region plugin commands
task('shopware5:plugins:install:local', function () {
    run('cd {{shopware_build_path}} && {{bin/php}} {{console}} sw:plugin:refresh -q');
    foreach (get('plugins') as $plugin) {
        run("cd {{shopware_build_path}} && {{bin/php}} {{console}} sw:plugin:install {$plugin} --activate");
    }
    run('cd {{shopware_build_path}} && {{bin/php}} {{console}} sw:cache:clear');
})->isLocal();

task('shopware5:plugins:install:remote', function () {
    run('cd {{releasePath}} && {{bin/php}} {{console}} sw:plugin:refresh -q');
    foreach (get('plugins') as $plugin) {
        run("cd {{releasePath}} && {{bin/php}} {{console}} sw:plugin:install {$plugin} --activate");
    }
});

task('shopware5:plugins:uninstall:remote', function () {
    foreach (get('plugins') as $plugin) {
        run("cd {{releasePath}} && {{bin/php}} {{console}} sw:plugin:uninstall {$plugin} --activate");
    }
    run('cd {{releasePath}} && {{bin/php}} {{console}} cache:clear');
});

task('shopware5:plugins:require:deployment', function () {
    foreach (get('deployment_plugins') as $dependency) {
        run("cd {{shopware_build_path}} && {{bin/composer}} -q require --ignore-platform-reqs --optimize-autoloader --prefer-dist --no-ansi --update-no-dev --no-scripts {$dependency}");
    }
})->desc('Add required composer-based Shopware plugins for deployment')->isLocal();

//endregion

//region Config
task('shopware5:plugins:config', function () {
    foreach (get('plugin_config') as $pluginName => $pluginConfigs) {
        foreach ($pluginConfigs as $pluginConfig) {
            run(
                sprintf(
                    "cd {{shopware_build_path}} && {{bin/php}} {{console}} sw:plugin:config:set %s %s \"%s\" %s",
                    $pluginName,
                    $pluginConfig['name'],
                    $pluginConfig['value'],
                    isset($pluginConfig['shopId']) ? "--shop {$pluginConfig['shopId']}" : ""
                )
            );
        }
    }
});

task('shopware5:config:theme', function () {
    run("cd {{releasePath}} && {{bin/php}} {{console}} sw:theme:synchronize -q");
    foreach (get('theme_config') as $themeName => $themeSettings) {
        foreach ($themeSettings as $setting => $value) {
            run(sprintf(
                "cd {{releasePath}} && {{bin/php}} {{console}} k10r:theme:update -q --theme '%s' --setting '%s' --value '%s' %s",
                $themeName,
                $setting,
                $value
            ));
        }
    }
})->desc('Configure theme via deploy.php');

task('shopware5:config:shop', function () {
    run("cd {{releasePath}} && {{bin/php}} {{console}} k10r:snippets:update");
    foreach (get('shopware_config') as $config) {
        run(sprintf(
            "cd {{releasePath}} && {{bin/php}} {{console}} k10r:config:set %s %s %s",
            $config['name'],
            $config['value'],
            isset($config['shopId']) ? "--shop {$config['shopId']}" : ""
        ));
    }
})->desc('Update snippets and set Shopware core configuration');
//endregion

//region Cache
task('kellerkinder:shopware:cache', function () {
    run('cd {{releasePath}} && {{bin/php}} {{console}} sw:cache:clear -q');
    run('cd {{releasePath}} && {{bin/php}} {{console}} sw:theme:cache:generate -q');

    if (get('warm_cache_after_deployment', false)) {
        run('cd {{releasePath}} && {{bin/php}} {{console}} sw:warm:http:cache -c -q');
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
