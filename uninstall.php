<?php

// If uninstall not called from WordPress, then exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ){
   exit;  
}
// If uninstall called from WordPress, then delete option
if ( get_option( 'dialogcf_options' ) != false ){
   delete_option( 'dialogcf_options' );
}

?>