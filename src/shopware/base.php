<?php

namespace Deployer;

// Command paths
set('console', 'bin/console');
set('shopware_build_path', '/tmp/build');
set('build_script_storefront', 'bin/build-storefront.sh');
set('build_script_administration', 'bin/build-administration.sh');

task('shopware:build:prepare', function () {
    run('mkdir -p {{shopware_build_path}}');
});

task('shopware:filesystem:deploy', function () {
    foreach (get('deploy_filesystem') as $dirName) {
        run("cp -r {$dirName}/* {{shopware_build_path}}/");
    }
});
