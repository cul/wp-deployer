<?php

function deploy_new_app($ssh_connection, $DEPLOY_CONFIG) {
  $wp_latest_version = get_wp_latest_version();
  
  $user_entered_version = trim(readline("Enter a WordPress version to deploy [$wp_latest_version]: "));
  if(empty($user_entered_version)) {
    $wp_version = $wp_latest_version;
  } else if(wordpress_version_exists_on_github($user_entered_version)) {
    $wp_version = $user_entered_version;
  } else {
    echo color_text('Invalid version (not found on GitHub): ' . $user_entered_version, RED);
    exit;
  }
  
  echo "Setting up new instance of WP with version: $wp_version..." . "\n";
  
  echo "Setting up required deployment directories on server" . "\n";
  // Set up deployment directory if it doesn't exist
  run_ssh_command($ssh_connection, "mkdir -p '" . get_deploy_shared_dir($DEPLOY_CONFIG) . "'");
  run_ssh_command($ssh_connection, "mkdir -p '" . get_deploy_shared_config_dir($DEPLOY_CONFIG) . "'");
  run_ssh_command($ssh_connection, "mkdir -p '" . get_deploy_releases_dir($DEPLOY_CONFIG) . "'");
  run_ssh_command($ssh_connection, "mkdir -p '" . get_deploy_tmp_dir($DEPLOY_CONFIG) . "'");
  run_ssh_command($ssh_connection, "mkdir -p '" . get_zipfile_download_dir($DEPLOY_CONFIG) . "'");
  run_ssh_command($ssh_connection, "mkdir -p '" . get_zipfile_unpack_dir($DEPLOY_CONFIG) . "'");
  
  //Clean up any old downloads or unpacked WP copies
  echo "Cleaning up old wp downloads..." . "\n";
  clean_up_old_zipfiles_and_unpack_directories($ssh_connection, $DEPLOY_CONFIG);
  
  $release_name = get_current_datetime_based_release_name();
  $release_path = get_deploy_releases_dir($DEPLOY_CONFIG) . '/' . $release_name;
  $deploy_current_dir_path = get_deploy_current_dir($DEPLOY_CONFIG);
  
  //Download zip file to tmp dir
  echo "Downloading WP zip file..." . "\n";
  $zip_download_url = get_wordpress_github_zip_download_url($wp_version);
  $zip_file_path = get_zipfile_download_dir($DEPLOY_CONFIG) . '/' . $wp_version . '.zip';
  $download_command = "curl -L '{$zip_download_url}' > '{$zip_file_path}'";
  run_ssh_command($ssh_connection, $download_command);
  
  echo "Unpacking WP zip file to $release_path..." . "\n";
  // Unpack zip file to tmp dir
  $zip_file_unpack_path = get_zipfile_unpack_dir($DEPLOY_CONFIG) . '/' . $wp_version . '-unpacked';
  $unzip_command = "unzip '{$zip_file_path}' -d '{$zip_file_unpack_path}'";
  run_ssh_command($ssh_connection, $unzip_command);
  
  // Move unpacked zip file to releases directory with date/time-based name
  $unpacked_wp_move_command = "mv '{$zip_file_unpack_path}/'$(ls -1 '{$zip_file_unpack_path}') $release_path";
  run_ssh_command($ssh_connection, $unpacked_wp_move_command);
  
  //Remove wp-config-sample.php
  echo "Removing wp-config-sample.php..." . "\n";
  run_ssh_command($ssh_connection, "rm $release_path/wp-config-sample.php");
  
  // Symlink config files to shared/config directory
  $shared_config_dir = get_deploy_shared_config_dir($DEPLOY_CONFIG);
  echo "Symlinking config files to shared/config directory..." . "\n";
  $symlinked_config_files = array('robots.txt', 'wp-config.php');
  foreach($symlinked_config_files as $file_to_symlink) {
    $path_to_shared_config_file = "$shared_config_dir/$file_to_symlink";
    if(!file_exists_on_server($ssh_connection, $path_to_shared_config_file)) {
      echo color_text("Error: Could not find expected file $path_to_shared_config_file. This must be present for the deployment to succeed.  Rolling back.", RED) . "\n";
      delete_release($ssh_connection, $DEPLOY_CONFIG, $release_name);
      die(color_text("Deployment FAILED.", RED) . "\n");
    }
    $wp_config_delete_and_symlink_command = "ln -s $path_to_shared_config_file $release_path/$file_to_symlink";
    run_ssh_command($ssh_connection, $wp_config_delete_and_symlink_command);
  }
  
  // Remove old current symlink and replace with new symlink to the latest release
  echo "Symlinking $deploy_current_dir_path to $release_path..." . "\n";
  $symlink_command = "rm $deploy_current_dir_path; ln -s $release_path " . get_deploy_current_dir($DEPLOY_CONFIG);
  run_ssh_command($ssh_connection, $symlink_command);
  
  //Clean up zip file and unpack directory
  echo "Cleaning up latest wp zip download..." . "\n";
  clean_up_old_zipfiles_and_unpack_directories($ssh_connection, $DEPLOY_CONFIG);
  
  echo "Cleaning up old releases...(keeping {$DEPLOY_CONFIG['RELEASES_TO_KEEP']})..." . "\n";
  $releases_directory = get_deploy_releases_dir($DEPLOY_CONFIG);
  $clean_up_old_releases_command = "cd '$releases_directory' && rm -rf `ls -1r | tail -n +" . ($DEPLOY_CONFIG['RELEASES_TO_KEEP'] + 1) . "`";
  run_ssh_command($ssh_connection, $clean_up_old_releases_command);
  
  die(color_text("Deployment SUCCEEDED.", GREEN) . "\n");
}

?>