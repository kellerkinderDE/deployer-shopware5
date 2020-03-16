<?php

namespace Deployer;

require 'deploy-shopware5.php';

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
set('shopware_install_url', 'https://www.shopware.com/de/Download/redirect/version/sw5/file/install_5.6.6_f8cbea93398b121a4471c35795ce1a8822176d32.zip');
set('shopware_update_url', 'https://www.shopware.com/de/Download/redirect/version/sw5/file/update_5.6.6_a2550f4807e2ae04beb25a0669d8dc400b13c9d2.zip');
set('shopware_target_version', '5.6.6'); // Change to check if update is required
// Will copy the source into the target during build
set('source_directory', 'web');
set('plugins', [
    'K10rStaging',
    'K10rDeployment',
    'K10rExamplePlugin',
    //'K10rProject',
]);

set('composer_plugins', [
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

/**
 * Uncomment if you want to be able to redeploy after errors without human interaction
 */
//after('deploy:failed', 'deploy:unlock');

inventory('inventory.yml');
