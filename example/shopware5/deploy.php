<?php

namespace Deployer;

require 'recipe/common.php';
require 'recipe/rsync.php';
require 'recipe/cachetool.php';
require 'vendor/k10r/deployer/src/shopware/shopware5.php';

// Deployer specific
set('application', 'my_shopware5_project');
set('php', '/usr/local/bin/php');
add('shared_files', ['config.php']);
add('executable_files', ['bin/console']);
add('shared_dirs', [
    'media',
    'files',
    'var/log',
]);
add('writable_dirs', [
    'var/cache',
    'web/cache',
]);
set('allow_anonymous_stats', false);
set('keep_releases', 10);


// Shopware / deployment specific
set('execute_update', false);
set('shopware_install_url', 'https://www.shopware.com/de/Download/redirect/version/sw5/file/install_5.6.6_f8cbea93398b121a4471c35795ce1a8822176d32.zip');
set('shopware_update_url', 'https://www.shopware.com/de/Download/redirect/version/sw5/file/install_5.6.6_f8cbea93398b121a4471c35795ce1a8822176d32.zip');
set('shopware_db_config', [
    'dbHost'     => 'mysql',
    'dbPort'     => '3306',
    'dbName'     => 'ci_db_sw5',
    'dbUser'     => 'root',
    'dbPassword' => 'root',
]);
// Will copy the source into the target during build
set('deploy_filesystem', ['web']);
set('plugins', [
    'K10rExamplePlugin',
    'K10rStaging',
    'K10rDeployment'
]);

set('deployment_plugins', [
    'k10r/staging:1.0.3',
    'k10r/deployment:1.2.0',
]);
/**
 * Warming the cache may take some time therefore it is not enabled by default. Set to true if you wish to warm up the cache after every deployment.
 */
set('warm_cache_after_deployment', false);

/**
 * Use this to set plugin configurations during deployment
 */
set('plugin_config',
    [
//        'SwagImportExport' => [
//            [
//                'name' => 'useCommaDecimal',
//                'value' => 0,
//                'shopId' => 1, // shopId is optional
//            ],
//        ],
    ]
);

/**
 * Use this to set theme configurations during deployment
 */
set('theme_config',
    [
//        'YourCustomThemeName' => [
//            'offcanvasCart' => 0,
//        ],
    ]
);

/**
 * Use this to set shopware configurations during deployment
 */
set('shopware_config',
    [
//        [
//            'name' => 'disableShopwareStatistics',
//            'value' => 1,
//            'shopId' => 1, // shopId is optional
//        ],
    ]
);

after('deploy:failed', 'deploy:unlock');

inventory('inventory.yml');
