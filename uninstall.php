<?php
/**
 *  Uninstall clean up
 *
 * @package SSPC
 */

 // If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('sspc_settings'); 

// For multisite installations, delete site options
if (is_multisite()) {
    delete_site_option('sspc_settings');
}
