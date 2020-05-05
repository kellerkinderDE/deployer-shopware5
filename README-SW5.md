# deployer for Shopware 5

This README describes a [deployer](https://deployer.org/) configuration for Shopware 5.

## Preparations

### Repository
Copy the contents of `shopware5/` to your shop project. Within your shop project download the latest [composer.phar](https://getcomposer.org/download/).

The `web` directory of your repository will be used as shopware root directory. Place all custom files for your shopware project here, e.g. plugins and custom themes. The directory structure should mirror Shopware's directory structure. You can also add core files, if you have to do any core hacks (although we discourage from that in general).

We are using deployer's rsync strategy for deployment. This means a pristine deployment will be built locally and then rsync'ed to your server. This allows for easier verification and removes the need for Git on your server. Rsync is required both on your client (e.g. GitLab CI runner or Docker image) and your server.

We recommend to use [K10rProject](https://github.com/kellerkinderDE/K10rProject) for database migrations (via [NetcomMigrations](https://github.com/eTribes-Connect-GmbH/NetcomMigrations)) and snippet management, so this config depends on it. Please add it to your repository if you want to use it and enable it via the plugin list in `deploy.php`.

## Configurations

### inventory.yml

This file defines the different hosts you may want to deploy to. Adjust those hosts to your needs.
See [deployer documentation](https://deployer.org/docs/hosts) for further information.

### deploy.php

This file defines the basic configuration via deployers `set`-method. Make sure to adjust the following options to your needs:
* `repository`: URL to the repository you want to deploy.
* `bin/php`: Absolute path to the php executable on your server.
* `bin/composer`: Path to composer. Adjust if you do not want to use the composer.phar.
* `shopware_download_path`: URL to the Shopware installation package you want to deploy.
* `shopware_update_version`: Shopware version you want to update to. Will be used to check whether the current installation needs to be updated.
* `shopware_update_path`: URL to the Shopware update package you want to update to.
* `plugins`: An array of plugins that will be installed and updated during deployment. Those plugins need to be present in the codebase.
* `composer_plugins`: An array of composer package names that will be required during deployment.
* `plugin_config`: An array of plugin configuration settings that will be set during deployment.
* `theme_config`: An array of theme configuration settings that will be set during deployment.
* `shopware_config`: An array of shopware configuration settings that will be set during deployment.

## kellerkinder tasks (deploy-shopware.php)

### shopware5:install:download
Downloads the defined shopware installation package, unpacks it and rsyncs it into the shopware root directory.

### shopware5:plugins:require:deployment:local
Requires some plugins via composer. In this example config those will be [K10rStaging](https://github.com/kellerkinderDE/K10rStaging), [FroshMailCatcher](https://github.com/FriendsOfShopware/FroshMailCatcher) and [K10rDeployment](https://github.com/kellerkinderDE/K10rDeployment)

_Note:_ They will not be installed in this task.

### shopware5:update:local
This task will check whether the currently installed shopware version matches the `shopware_update_version` (see above) and will download the shopware update package.

### shopware5:update:remote
This task will update Shopware with a previously downloaded update package.

### shopware5:plugins:install:remote
This will (re)install `K10rDeployment` which gives us some useful commands for the deployment process. Afterwards it installs and updates all plugins specified in the `plugins` variable in `deploy.php`.

### shopware5:config:plugins:remote
This task will configure plugins according to the `plugin_config` variable in `deploy.php`.

### shopware5:config:shop:remote
This task will set Shopware configuration options. Add your own configurations via `shopware_config` in `deploy.php`.
For more information on how configurations are set, check [K10rDeployment](https://github.com/kellerkinderDE/K10rDeployment).

### shopware5:config:theme:remote
This task will set theme configuration options according to the `theme_config` variable in `deploy.php`.

### kellerkinder:shopware:cache
Clears the cache using `sw:cache:clear` and compiles the themes afterwards. Can also be used to warm up the cache which is not activated by default. It can be activated by setting `warm_cache_after_deployment` to `true` in the `deploy.php`.

Note: You may want to clear OPcache, APC or APCu during deployments. Configure the `cachetool` variable in your `inventory.yml` and uncomment the tasks in `deploy.php` for that.

### kellerkinder:shopware:production and kellerkinder:shopware:staging
These two commands are used to set configurations that differ between the stages.

The staging task will for example install and activate `K10rStaging` and `FroshMailCatcher` which will show a notice that the user is in a staging environment and catch mails.

These tasks are usually used to set plugin configurations differing between stage and production, e.g. PayPal in production or sandbox mode or to deactivate a tracking plugin that will only be used production.

## Directories on the server
Within your deploy path deployer will create several directories:

* `.dep`: Information about the releases
* `current`: Symlink that will always be linked to the latest release.
* `releases`: This directory contains the last 10 releases. If you want to keep more or less releases simply overwrite the `keep_releases` setting via `deploy.php`. 
* `shared/`: Place your `config.php` and `sw-domain-hash.html` here. Also a good place for the `.htaccess`, if it's not identical to the one shipped with shopware.
* `shared/media`: Place your media files here. deployer will symlink them into the shopware root directory across all releases.
* `shared/files`: Same as above but for the document and download files.
* `shared/var/log`: Contains all log files of Shopware.

The shared directories and files are configured in `deploy.php`. You can add more by using `add` in the `deploy.php`, e.g. `add('shared_files', ['.htaccess']);`.

## GitLab CI

Since we use [GitLab CI](https://about.gitlab.com/features/gitlab-ci-cd/) to manage our code and deploy the projects we also ship the `.gitlab-ci.yml.example` which will work perfectly together with this deployer config.

To get this to work you will need some configuration in GitLab:
* Create the `SSH_PRIVATE_KEY` variable in the GitLab project settings. This variable needs to contain the *private* SSH key with access to the server.
* Add the deployment key with the matching *public* SSH key.
* Add the *public* SSH key to the `.authorized_keys` file of the server.

## Contribution
Feel free to send pull requests if you have any optimizations. They will be highly appreciated.

## License
MIT licensed, see `LICENSE`
