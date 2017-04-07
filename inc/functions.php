<?php

DEFINE('RED', "\033[31m");
DEFINE('GREEN', "\033[32m");
DEFINE('YELLOW', "\033[33m");
DEFINE('DEFAULT_COLOR', "\033[0m");

function usage() {
  echo 'Usage: wp-deployer.php [environment dev|test|prod] [deployment_type new|update|restore]' . "\n";
}

function load_config_for_environment($env) {
  //Require config file for specified environment
  $config_file_path = dirname(__FILE__)."/../environments/{$env}.config.php";
  if(!file_exists($config_file_path)) {
    echo color_text("Could not find config file for environment $env at: $config_file_path", RED) . "\n";
    exit;
  }
  require $config_file_path; //Note: This require populates a $DEPLOY_CONFIG variable.
  return $DEPLOY_CONFIG;
}

function clean_up_old_zipfiles_and_unpack_directories($ssh_connection, $DEPLOY_CONFIG) {
  $delete_command = 'rm -rf ' . get_zipfile_download_dir($DEPLOY_CONFIG) . '/* ' . get_zipfile_unpack_dir($DEPLOY_CONFIG) . '/*';
  run_ssh_command($ssh_connection, $delete_command);
}

function get_current_datetime_based_release_name() {
  return date('YmdHis');
}

function get_wp_content_dir($DEPLOY_CONFIG) {
  return "{$DEPLOY_CONFIG['WP_DATA_DIR']}/wp-content";
}

function get_deploy_tmp_dir($DEPLOY_CONFIG) {
  return "{$DEPLOY_CONFIG['WP_DEPLOY_DIR']}/tmp";
}

function get_deploy_shared_dir($DEPLOY_CONFIG) {
  return "{$DEPLOY_CONFIG['WP_DEPLOY_DIR']}/shared";
}

function get_deploy_shared_config_dir($DEPLOY_CONFIG) {
  return get_deploy_shared_dir($DEPLOY_CONFIG) . "/config";
}

function get_deploy_releases_dir($DEPLOY_CONFIG) {
  return "{$DEPLOY_CONFIG['WP_DEPLOY_DIR']}/releases";
}

function get_deploy_current_dir($DEPLOY_CONFIG) {
  return "{$DEPLOY_CONFIG['WP_DEPLOY_DIR']}/current";
}

function get_zipfile_download_dir($DEPLOY_CONFIG) {
  return get_deploy_tmp_dir($DEPLOY_CONFIG) . '/downloads';
}

function get_zipfile_unpack_dir($DEPLOY_CONFIG) {
  return get_deploy_tmp_dir($DEPLOY_CONFIG) . '/unpack';
}

function get_ssh_connection($DEPLOY_CONFIG) {
  $ssh_connection = ssh2_connect($DEPLOY_CONFIG['SERVER'], 22);
  $default_private_key_directory = $_SERVER['HOME'] . '/.ssh';
  $private_key_files = glob($default_private_key_directory . '/id_*sa');
  if(count($private_key_files) > 0) {
    $private_key_file = $private_key_files[0];
    $public_key_file = $private_key_file . '.pub';
  } else {
    die("Could not find private key file in: $default_private_key_directory");
  }
  
  if(!file_exists($public_key_file)) {
    die('Found private key, but could not find public key file at expected location: ' . $public_key_file);
  }
  
  if (ssh2_auth_pubkey_file($ssh_connection, $DEPLOY_CONFIG['USER'], $public_key_file, $private_key_file)) {
    echo "Public Key Authentication Successful: {$DEPLOY_CONFIG['USER']}@{$DEPLOY_CONFIG['SERVER']}\n";
  } else {
    die('Public Key Authentication Failed');
  }
  
  return $ssh_connection;
}

function run_ssh_command($ssh_connection, $command, $echo_command=true) {
  
  echo '  ' . color_text($command, YELLOW) . "\n";
  
  // execute a command
  if (!($stream = ssh2_exec($ssh_connection, $command ))) {
      echo "fail: unable to execute command\n";
  } else {
      // collect returning data from command
      stream_set_blocking($stream, true);
      $data = "";
      while ($buf = fread($stream,4096)) {
          $data .= $buf;
      }
      fclose($stream);
      return trim($data); //remove leading and trailing whitespace
  }
}

function delete_release($ssh_connection, $DEPLOY_CONFIG, $release_name) {
  $path_to_release = get_deploy_releases_dir($DEPLOY_CONFIG) . $release_name;
  echo "Deleting release at: $path_to_release";
  run_ssh_command($ssh_connection, "rm -rf " . $path_to_release);
}

function file_exists_on_server($ssh_connection, $full_path_to_file_on_server) {
  $result = run_ssh_command($ssh_connection, "[ -e '$full_path_to_file_on_server' ] && echo '1' || echo '0'", false);
  return ('1' == $result);
}

function get_wp_latest_version() {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://api.wordpress.org/core/version-check/1.7/");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  $json_response = curl_exec($ch);
  curl_close($ch);
  $data = json_decode($json_response, true);
  
  $version = $data['offers'][0]['version'];
  
  return $version;
}

function wordpress_version_exists_on_github($version) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://github.com/WordPress/WordPress/releases/{$version}");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
  $response = curl_exec($ch);
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  
  return ($http_code == '200');
}

function get_wordpress_github_zip_download_url($version) {
  return 'https://github.com/WordPress/WordPress/archive/' . $version . '.zip';
}

function color_text($text, $color_escape_sequence) {
  return $color_escape_sequence . $text . DEFAULT_COLOR;
}

?>