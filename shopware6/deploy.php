<?php

namespace Deployer;

require 'deploy-shopware6.php';

// Deployer specific
set('application', 'my_shopware6_project');
set('bin/php', '/usr/bin/php');
set('sudo_cmd', ''); // add a sudo command like sudo -u username if needed
add('shared_files', ['.env']);
add('executable_files', ['bin/console']);
add('shared_dirs', [
    'files',
    'var/log',
]);
add('create_shared_dirs', [
    'files',
    'var/cache',
    'var/log',
]);
add('writable_dirs', [
    'var/cache',
    'var/log',
    'files',
    'public',
]);
set('allow_anonymous_stats', false);
set('keep_releases', 10);

// Shopware / deployment specific
set('shopware_install_url', 'https://www.shopware.com/de/Download/redirect/version/sw6/file/install_6.1.3_1582123990.zip');
set('shopware_update_url', 'https://www.shopware.com/de/Download/redirect/version/sw6/file/update_6.1.3_1582123990.zip');
set('shopware_target_version', '6.1.3'); // Change to check if update is required
// source => target -- Will copy the source into the target during build
set('source_directory', 'web');
set('plugins', [
    'K10rExamplePlugin',
]);

/**
 * Warming the cache may take some time therefore it is not enabled by default. Set to true if you wish to warm up the cache after every deployment.
 */
set('warm_cache_after_deployment', false);

// TODO: Add CLI helper to set shop/theme/plugin configuration

/**
 * Uncomment if you want to be able to redeploy after errors without human interaction
 */
//after('deploy:failed', 'deploy:unlock');

/**
 * Uncomment if you need to clear opcache during deployment. Remember to also set the cachetool parameter per host in the inventory.yml. E.g.: cachetool: /var/run/php5-fpm.sock
 */
//after('shopware6:plugins:install:remote', 'cachetool:clear:opcache');

inventory('inventory.yml');