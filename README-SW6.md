# deployer for Shopware 6

This README describes a [deployer](https://deployer.org/) configuration for Shopware 6.

## Preparations

### Repository
Copy the contents of `shopware6/` to your shop project. Within your shop project download the latest [composer.phar](https://getcomposer.org/download/) and [deployer.phar](https://deployer.org/download) to the ``bin`` directory.

The `web` directory of your repository will be used as shopware root directory. Place all custom files for your shopware project here, e.g. plugins and custom themes. The directory structure should mirror Shopware's directory structure.

We are using deployer's rsync strategy for deployment. This means a pristine deployment will be built locally and then rsync'ed to your server. This allows for easier verification and removes the need for Git on your server. Rsync is required both on your client (e.g. GitLab CI runner or Docker image) and your server.

Note: You may want to clear OPcache, APC or APCu during deployments. Configure the `cachetool` variable in your `inventory.yml` and uncomment the tasks in `deploy.php` for that.

## Configurations

### inventory.yml

This file defines the different hosts you may want to deploy to. Adjust those hosts to your needs.
See [deployer documentation](https://deployer.org/docs/hosts) for further information.

### deploy.php

This file defines the basic configuration via deployers `set`-method. Make sure to adjust the following options to your needs:
* `repository`: URL to the repository you want to deploy.
* `bin/php`: Absolute path to the php executable on your server.
* `shopware_install_url`: URL to the Shopware installation package you want to deploy.
* `shopware_target_version`: Shopware version you want to update to. Will be used to check whether the current installation needs to be updated.
* `shopware_update_url`: URL to the Shopware update package you want to update to.
* `plugins`: An array of plugins that will be installed and updated during deployment. Those plugins need to be present in the codebase.
* `composer_plugins`: An array of composer package names that will be required during deployment.
* `sales_channels`: An array of sales channel IDs with their respective themes and URLs. This information is used during the local step of deployments to prepare the JS/CSS compilation.
* `theme_ids`: An array of theme names and their respective IDs. This information is used during the local step of deployments to prepare the JS/CSS compilation.

## kellerkinder tasks (deploy-shopware.php)

### shopware6:install:download
Downloads the defined Shopware installation package, unpacks it and rsyncs it into the shopware root directory.

### shopware6:install:execute
Installs the Shopware installation package locally.

### shopware6:plugins:require:deployment:local
Requires some plugins via composer.

_Note:_ They will not be installed in this task.

### TODO

## Directories on the server
Within your deploy path deployer will create several directories:

* `.dep`: Information about the releases
* `current`: Symlink that will always be linked to the latest release.
* `releases`: This directory contains the last 10 releases. If you want to keep more or less releases simply overwrite the `keep_releases` setting via `deploy.php`. 
* `shared/`: Place your `.env` here. Also a good place for the `public/.htaccess`, if it's not identical to the one shipped with shopware.
* `shared/files`: Place your media files here. deployer will symlink them into the shopware root directory across all releases.
* `shared/var/log`: Contains all log files of Shopware.

The shared directories and files are configured in `deploy.php`. You can add more by using `add` in the `deploy.php`, e.g. `add('shared_files', ['public/.htaccess']);`.

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
