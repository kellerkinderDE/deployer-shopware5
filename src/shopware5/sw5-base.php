<?php

namespace Deployer;

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
