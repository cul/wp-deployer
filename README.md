### WP Deployer

WordPress deployment script for the CUL environment.

### Usage:

```sh
wp-deployer.php [environment] [deployment_type]
```

`environment` can be any value like dev, test or prod (or whatever you name the files that you put in the environments directory)

`deployment_type` can be one of the following: new | update | restore

Running the command `wp-deployer.php dev new` would expect a corresponding 'dev' environment config file to exist at `environments/dev.config.php`

### Deployment Types

new: Downloads and deploys a new instance of WordPress.

update: Updates an existing wordpress deployment, pulling in the latest versions of custom themes and plugins.

restore: Deploys a WordPress instance from a backup.

### Requirements

- PHP 5.6 or later (though only tested on PHP 5.6 so far)
- PHP ssh2 extension (http://php.net/manual/en/book.ssh2.php)
