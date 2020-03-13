<?php

namespace Deployer;

require 'recipe/common.php';
require 'recipe/rsync.php';
require 'recipe/cachetool.php';
require 'vendor/k10r/deployer/src/shopware/shopware6.php';

// Deployer specific
set('application', 'my_shopware6_project');
set('php', '/usr/local/bin/php');
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
set('execute_update', false);
set('shopware_install_url', 'https://www.shopware.com/de/Download/redirect/version/sw6/file/install_6.1.3_1582123990.zip');
set('shopware_update_url', 'https://www.shopware.com/de/Download/redirect/version/sw6/file/install_6.1.3_1582123990.zip');
// source => target -- Will copy the source into the target during build
set('deploy_filesystem', ['web']);
set('plugins', ['K10rExamplePlugin']);


// etc

after('deploy:failed', 'deploy:unlock');

inventory('inventory.yml');
