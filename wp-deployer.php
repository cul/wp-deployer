<?php

require 'functions.php';
require 'deploy-type-new.php';

if(empty($argv[1])) {
  echo color_text("Please specify an environment (e.g. dev)", RED) . "\n";
  usage();
  exit;
}

if(empty($argv[2])) {
  echo color_text("Please specify a deployment type (e.g. new)", RED) . "\n";
  usage();
  exit;
}

$env = $argv[1];
$deployment_type = $argv[2];

$DEPLOY_CONFIG = load_config_for_environment($env);

echo "Environemnt is: $env" . "\n";
echo "Deployment type is: $deployment_type" . "\n";

$ssh_connection = get_ssh_connection($DEPLOY_CONFIG);

if($deployment_type == 'new') {
  deploy_new_app($ssh_connection, $DEPLOY_CONFIG);
}

?>