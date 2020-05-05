<?php

namespace Deployer;

require 'deploy-shopware6.php';

// Deployer specific
set('application', 'my_shopware6_project');
set('bin/php', '/usr/bin/php');
set('writable_mode', 'chmod');
add('shared_files', ['.env']);
add('executable_files', ['bin/console']);
add('shared_dirs', [
    'files',
    'var/log',
]);
add('create_shared_dirs', [
    'files',
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
set('shopware_install_url', 'https://www.shopware.com/de/Download/redirect/version/sw6/file/install_6.1.5_1585830011.zip');
set('shopware_update_url', 'https://www.shopware.com/de/Download/redirect/version/sw6/file/update_6.1.5_1585830011.zip');
set('shopware_target_version', '6.1.5'); // Change to check if update is required
// source => target -- Will copy the source into the target during build
set('source_directory', 'web');
set('plugins', [
    'K10rDeployment',
]);
set('composer_plugins', []);
set('sales_channels', [ // TODO: Define correct URLs. URLs are currently only used locally during deployment. The Sales Channels are also only created locally, not remote
        'af66829f5b4443dca0fc1360395fc3fd' => [
            'theme' => 'Storefront',
            'url' => 'https://www.customer.tld/'
        ],
    ]
);
set('theme_ids',
    [
        'Storefront' => '62d77e288bd549ce8fc4903b72ee1861',
    ]
);

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
//after('shopware6:plugins:install:remote', 'cachetool:clear:apc');
//after('shopware6:plugins:install:remote', 'cachetool:clear:apcu');

inventory('inventory.yml');
