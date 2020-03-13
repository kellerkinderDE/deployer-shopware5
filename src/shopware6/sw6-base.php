<?php

namespace Deployer;

//region build commands
//region production
/** @see https://github.com/shopware/production */
task('shopware6:build:production:prepare', function() {
    run('cd {{shopware_build_path}} && npm install --silent --prefix vendor/shopware/administration/Resources/');
    run('cd {{shopware_build_path}} && npm run --silent --prefix vendor/shopware/administration/Resources lerna -- bootstrap');
    run('cd {{shopware_build_path}} && npm install --silent --prefix vendor/shopware/administration/Resources/app/administration/build/nuxt-component-library/');
})->desc('[SW6] - [BUILD] Storefront');
task('shopware6:build:production:storefront', function() {
    run('cd {{shopware_build_path}} && {{build_script_storefront}}');
})->desc('[SW6] - [BUILD] Storefront');
task('shopware6:build:production:administration', function() {
    run('cd {{shopware_build_path}} && {{build_script_administration}}');
})->desc('[SW6] - [BUILD] Administration');
task('shopware6:build:production:all',
    [
        'shopware6:build:production:prepare',
        'shopware6:build:production:storefront',
        'shopware6:build:production:administration'
    ]
)->desc('Prepare and build storefront and administration');
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
    run('cd {{shopware_build_path}} && {{bin/php}} {{console}} plugin:refresh');
    foreach (get('plugins') as $plugin) {
        run("cd {{releasePath}} && {{bin/php}} {{console}} plugin:install {$plugin} --activate");
    }
});
task('shopware6:plugins:uninstall:remote', function () {
    run('cd {{shopware_build_path}} && {{bin/php}} {{console}} plugin:refresh');
    foreach (get('plugins') as $plugin) {
        run("cd {{releasePath}} && {{bin/php}} {{console}} plugin:uninstall {$plugin} --activate");
    }
    run('cd {{shopware_build_path}} && {{bin/php}} {{console}} cache:clear');
});
//endregion

task('shopware6:cache:warm', function () {
    run('cd {{shopware_build_path}} && {{bin/php}} {{console}} http:cache:warm:up');
});
