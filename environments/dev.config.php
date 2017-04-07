<?php

$DEPLOY_CONFIG = array(
  'RELEASES_TO_KEEP' => 3,
  'SERVER' => 'ldpd-service-prod1.cul.columbia.edu',
  'USER' => 'ldpdserv',
  'WP_DATA_DIR' => '/cul/cul0/ldpd/wordpress/data/culblogs-dev',
  'WP_DEPLOY_DIR' => '/cul/cul0/ldpd/wordpress/test-docroots/culblogs-dev',
  'SYMLINKED_FILES' => array('wp-config.php', 'robots.txt')
);

?>