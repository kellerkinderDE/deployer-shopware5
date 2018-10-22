<?php

namespace Deployer;

require_once 'deploy-shopware.php';

/**
 * Set your repository URL here.
 */
set('repository', 'URL to your repository goes here');

set('git_tty', true);
set('ssh_multiplexing', true);
set('writable_use_sudo', false);

/**
 * Warming the cache may take some time therefore it is not enabled by default. Set to true if you wish to warm up the cache after every deployment.
 */
set('warm_cache_after_deployment', false);

/**
 * Adjust the path to the php executable if necessary
 */
set('bin/php', '/usr/bin/php');
set('bin/composer', '{{bin/php}} {{release_path}}/bin/composer.phar');

/**
 * Adjust shopware package links and update version to match the correct shopware version
 */
set(
    'shopware_download_path',
    'http://releases.s3.shopware.com.s3.amazonaws.com/install_5.5.1_4a48054b7c53187c807d6a6d82ec88ffb72b5e6a.zip'
);
set('shopware_update_version', '5.5.1');
set(
    'shopware_update_path',
    'http://releases.s3.shopware.com.s3.amazonaws.com/update_5.5.1_d9e440b141186b6ee91b2f6e6a98a37455c5e2ce.zip'
);

/**
 * These plugins will be installed and updated during deployment. Make sure they are availabe in the filesystem.
 */
set(
    'plugins',
    [
        'Cron',
        'K10rProject',
    ]
);

/**
 * Use this to set theme configurations during deployment
 */
set(
    'theme_config',
    [
        //'offcanvasCart' => '0',
    ]
);

/**
 * Enable and adjust the socket if you need to reset OPCache
 */
//add('fcgi_sockets', ['/var/run/php-fpm.sock']);

inventory('deploy-hosts.yml');
