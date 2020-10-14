<?php
/**
 * Exit if accessed directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Save settings
if (isset( $_POST['_wasm_nonce'] )) {
	if(wp_verify_nonce( $_POST['_wasm_nonce'], 'wasm-settings-validate' )) {
		if (isset($_POST['wasm_status_message'])) {
			if (is_multisite()) {
				if (is_super_admin() && isset($_POST['wasm_overrite']) && $_POST['wasm_overrite']==='yes') {
		            global $wpdb;
				    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
				    $original_blog_id = get_current_blog_id();

				    foreach ( $blog_ids as $blog_id ) {
				        switch_to_blog( $blog_id );
				        update_site_option('wasm_status_message', $_POST['wasm_status_message']);
				    }

				    switch_to_blog( $original_blog_id );
		        } else {
		            update_site_option('wasm_status_message', $_POST['wasm_status_message']);
		        }
			} else {
				update_option('wasm_status_message', $_POST['wasm_status_message']);
			}
		} ?>
		<div class="notice notice-success is-dismissible">
	        <p><?php _e( 'Status message saved successfully.', 'wp-admin-status-message' ); ?></p>
	    </div>
	<?php } else { ?>
		<div class="notice notice-error is-dismissible">
	        <p><?php _e( 'Sorry, your nonce did not verify. Please try again.', 'wp-admin-status-message' ); ?></p>
	    </div>
	<?php }
} ?>

<div class="wrap">
	<h1><?php esc_html_e('Status Message Options', 'wp-admin-status-message'); ?></h1>
	<form action="" method="post">
	    <?php wp_nonce_field( 'wasm-settings-validate', '_wasm_nonce' ); ?>
    	
	    <table class="form-table">
	        <tr>
	            <th scope="row"><label for="wasm_status_message"><?php esc_html_e('Dashboard Status Message', 'wp-admin-status-message'); ?></label></th>
	            <td>
	                <input name="wasm_status_message" class="regular-text" type="text" id="wasm_status_message" value="<?php echo is_multisite() ? esc_attr( get_site_option( 'wasm_status_message') ) : esc_attr( get_option( 'wasm_status_message') ); ?>" />
	                <p class="description"><?php esc_html_e('Set the status message for admin dashboard.', 'wp-admin-status-message'); ?></p>
	            </td>
	        </tr>
	        <?php if (is_multisite() && is_super_admin()) { ?>
		        <tr>
		            <th scope="row"><?php esc_html_e('Overrite on all sites', 'wp-admin-status-message'); ?></th>
		            <td><label><input name="wasm_overrite" type="checkbox" value="yes"> <?php esc_html_e('Yes, overrite all dashboard messages with above message for all site.', 'wp-admin-status-message'); ?></label></td>
		        </tr>
	    	<?php } ?>
	    </table>
	    
	    <?php submit_button(); ?>

	</form>
</div>