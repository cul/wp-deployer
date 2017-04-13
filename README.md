### WP Deployer

wp-mittens: A WordPress deployment script for the CUL environment.

_Name based on the present active participle of the Latin word mittō ("to send")._

### Usage:

```sh
wp-mittens.php [environment] [deployment_type]
```

`environment` can be any value like dev, test or prod (or whatever you name the files that you put in the environments directory)

`deployment_type` only has one valid value at the moment: new (but one day, there may be 'update' and 'restore' options too)

Running the command `wp-deployer.php dev new` would expect a corresponding 'dev' environment config file to exist at `environments/dev.config.php`

### Deployment Types

new: Downloads and deploys a new instance of WordPress.

update (not available yet): Updates an existing wordpress deployment, pulling in the latest versions of custom themes and plugins.

restore (not available yet): Deploys a WordPress instance from a backup.

### Requirements

- PHP 5.6 or later (though only tested on PHP 5.6 so far)
- PHP ssh2 extension (http://php.net/manual/en/book.ssh2.php)
