<?php

namespace Deployer;

require_once 'recipe/common.php';

add('shared_files', ['{{shopware_public_dir}}/config.php']);
add(
    'shared_dirs',
    [
        '{{shopware_public_dir}}/media',
        '{{shopware_public_dir}}/files',
    ]
);
add(
    'create_shared_dirs',
    [
        '{{shopware_public_dir}}/media/archive',
        '{{shopware_public_dir}}/media/image',
        '{{shopware_public_dir}}/media/image/thumbnail',
        '{{shopware_public_dir}}/media/music',
        '{{shopware_public_dir}}/media/pdf',
        '{{shopware_public_dir}}/media/unknown',
        '{{shopware_public_dir}}/media/video',
        '{{shopware_public_dir}}/media/temp',
        '{{shopware_public_dir}}/files/documents',
        '{{shopware_public_dir}}/files/downloads',
    ]
);
add(
    'writable_dirs',
    [
        '{{shopware_public_dir}}/var/cache',
        '{{shopware_public_dir}}/web/cache',
        '{{shopware_public_dir}}/recovery',
        '{{shopware_public_dir}}/themes',
    ]
);
add(
    'executable_files',
    [
        '{{shopware_public_dir}}/bin/console',
    ]
);
set('shopware_public_dir', 'web');
set('shopware_public_path', '{{release_path}}/{{shopware_public_dir}}');
set('shopware_temp_dir', '{{release_path}}/shopware-tmp');
set('shopware_temp_zip_file', '{{release_path}}/shopware.zip');
set('fcgi_sockets', []);
set('keep_releases', 10);


task(
    'deploy:writable:create_dirs',
    function () {
        foreach (get('writable_dirs') as $dir) {
            run("cd {{release_path}} && mkdir -p $dir");
        }
    }
)->desc('Create required directories, configure via deploy.php');
before('deploy:writable', 'deploy:writable:create_dirs');

task(
    'deploy:shared:sub',
    function () {
        $sharedPath = "{{deploy_path}}/shared";
        foreach (get('create_shared_dirs') as $dir) {
            // Create shared dir if it does not exist.
            run("mkdir -p $sharedPath/$dir");
        }
    }
)->desc('Creating shared subdirs');
after('deploy:shared', 'deploy:shared:sub');

task(
    'kellerkinder:shopware:download',
    function () {
        run('wget -nc -O {{shopware_temp_zip_file}} {{shopware_download_path}}');
        run('mkdir {{shopware_temp_dir}}');
        run('unzip {{shopware_temp_zip_file}} -d {{shopware_temp_dir}}');
        run('rm {{shopware_temp_zip_file}}');
        run('rm -rf {{shopware_temp_dir}}/recovery');
        run('rsync --ignore-existing --recursive {{shopware_temp_dir}}/ {{shopware_public_path}}/');
        run('rm -rf {{shopware_temp_dir}}');
        run('mkdir -p {{shopware_public_path}}/recovery/install/data');
        run('touch {{shopware_public_path}}/recovery/install/data/install.lock');
    }
)->desc('Download and include Shopware core data into project')->setPrivate();

task(
    'kellerkinder:shopware:update',
    function () {
        if (test('cd {{shopware_public_path}} && {{bin/php}} bin/console k10r:update:needed {{shopware_update_version}} -q')) {
            run('wget -nc -O {{shopware_temp_zip_file}} {{shopware_update_path}}');
            run('mkdir {{shopware_temp_dir}}');
            run('unzip {{shopware_temp_zip_file}} -d {{shopware_temp_dir}}');
            run('rm {{shopware_temp_zip_file}}');
            run('rsync --recursive {{shopware_temp_dir}}/ {{shopware_public_path}}/');
            run('rm -rf {{shopware_temp_dir}}');

            run('cd {{shopware_public_path}} && {{bin/php}} recovery/update/index.php -n');
            run('rm -rf {{shopware_public_path}}/update-assets');
            run('rm -rf {{shopware_public_path}}/recovery');
            run('mkdir -p {{shopware_public_path}}/recovery/install/data');
            run('touch {{shopware_public_path}}/recovery/install/data/install.lock');
        }
    }
)->desc('Check if a Shopware update is needed and execute it');

task(
    'kellerkinder:vendors',
    function () {
        foreach ([
                     'k10r/staging:1.0.0',
                     'k10r/deployment:1.1.0',
                 ] as $dependency) {
            run(
                "cd {{shopware_public_path}} && {{bin/composer}} require --optimize-autoloader --prefer-dist --no-ansi --update-no-dev {$dependency}"
            );
        }
    }
)->desc('Add required composer-based Shopware plugins for deployment')->setPrivate();


task(
    'deploy:vendors',
    function () {
        run('cd {{shopware_public_path}} && {{bin/composer}} {{composer_options}}');
    }
)->desc('Install composer dependencies');

task(
    'kellerkinder:shopware:plugins',
    function () {
        run('cd {{shopware_public_path}} && {{bin/php}} bin/console sw:plugin:refresh -q');
        run('cd {{shopware_public_path}} && {{bin/php}} bin/console sw:plugin:reinstall K10rDeployment');
        run('cd {{shopware_public_path}} && {{bin/php}} bin/console k10r:plugin:deactivate SwagUpdate');

        foreach (get('plugins') as $plugin) {
            run(
                "cd {{shopware_public_path}} && {{bin/php}} bin/console k10r:plugin:install --activate {$plugin}"
            );
        }
    }
)->desc('Install Shopware plugins after deployment. Configure plugins via deploy.php.');

task(
    'kellerkinder:shopware:config',
    function () {
        run("cd {{shopware_public_path}} && {{bin/php}} bin/console k10r:snippets:update");
    }
)->desc('Update snippets and set Shopware core configuration');


task(
    'kellerkinder:shopware:theme',
    function () {
        run(
            "cd {{shopware_public_path}} && {{bin/php}} bin/console sw:theme:synchronize -q"
        );
        foreach (get('theme_config') as $setting => $value) {
            run(
            /**
             * Adjust YourCustomTheme to your theme name
             */
                "cd {{shopware_public_path}} && {{bin/php}} bin/console k10r:theme:update -q --theme YourCustomTheme --shop 1 --setting {$setting} --value {$value}"
            );
        }
    }
)->desc('Configure theme via deploy.php');

task(
    'kellerkinder:shopware:cache',
    function () {
        run('cd {{shopware_public_path}} && {{bin/php}} bin/console sw:cache:clear -q');
        run('cd {{shopware_public_path}} && {{bin/php}} bin/console k10r:theme:compile -q');
        /**
         * Warming the cache may take some time therefore it is not enabled by default. Enable if you wish to warm up the cache after every deployment.
         */
//        run('cd {{shopware_public_path}} && {{bin/php}} bin/console sw:warm:http:cache -c -q');
    }
)->desc('Clear Shopware cache and warm up HTTP cache');

task(
    'kellerkinder:shopware:opcache',
    function () {
        foreach (get('fcgi_sockets') as $socket) {
            run("cd {{release_path}} && if [ -f bin/cachetool.phar ]; then {{bin/php}} bin/cachetool.phar opcache:reset --fcgi={$socket}; else true; fi");
        }
    }
)->desc('Clear PHP OPcache');

task(
    'kellerkinder:shopware:staging',
    function () {
        run(
            'cd {{shopware_public_path}} && {{bin/php}} bin/console k10r:plugin:install --activate K10rStaging'
        );
        run(
            sprintf('cd {{shopware_public_path}} && {{bin/php}} bin/console k10r:store:update --host %s --path %s --name %s --title %s',
                escapeshellarg(get('shop_host')),
                escapeshellarg(get('shop_path')),
                escapeshellarg(get('shop_name')),
                escapeshellarg(get('shop_title'))
            )
        );
    }
)->desc('Set staging configuration');

task(
    'kellerkinder:shopware:production',
    function () {}
)->desc('Set production configuration');

/**
 * Main task
 */
task(
    'kellerkinder:shopware:install',
    [
        'kellerkinder:shopware:download',
        'kellerkinder:vendors',
    ]
)->desc('Download current Shopware distribution and copy it into deployed directory, install composer-based Shopware plugins');

task(
    'kellerkinder:shopware:configure',
    [
        'kellerkinder:shopware:opcache',
        'kellerkinder:shopware:plugins',
        'kellerkinder:shopware:config',
        'kellerkinder:shopware:cache',
    ]
)->desc('Install remaining Shopware plugins, set configuration values and clear cache');

task(
    'deploy:production',
    [
        'deploy:prepare',
        'deploy:lock',
        'deploy:release',
        'deploy:update_code',
        'deploy:shared',
        'kellerkinder:shopware:install',
        'deploy:writable',
        'deploy:vendors',
        'deploy:clear_paths',
        'deploy:symlink',
        'kellerkinder:shopware:update',
        'kellerkinder:shopware:configure',
        'kellerkinder:shopware:production',
        'deploy:unlock',
        'cleanup',
        'success',
    ]
)->desc('Deploys a production shopware project');

task(
    'deploy:staging',
    [
        'deploy:prepare',
        'deploy:lock',
        'deploy:release',
        'deploy:update_code',
        'deploy:shared',
        'kellerkinder:shopware:install',
        'deploy:writable',
        'deploy:vendors',
        'deploy:clear_paths',
        'deploy:symlink',
        'kellerkinder:shopware:update',
        'kellerkinder:shopware:configure',
        'kellerkinder:shopware:staging',
        'deploy:unlock',
        'cleanup',
        'success',
    ]
)->desc('Deploys a staging shopware project');
