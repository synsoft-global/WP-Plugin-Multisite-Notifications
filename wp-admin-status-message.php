<?php
/**
 * Plugin Name:	WP admin status message
 * Plugin URI:	https://github.com/synsoft-global/WP-Plugin-Multisite-Notifications
 * Description:	Post a status message on the WordPress
admin dashboard page.
 * Version:		1.0.0
 * Author:		Synsoft Global
 * Author URI:	https://www.synsoftglobal.com/
 * License:		GPLv2 or later
 * License URI:	https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:	wp-admin-status-message
 * Domain Path: /languages
 */

/**
 * If this file is called directly, then abort execution.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 
 */
class WP_Admin_Status_Message {
	
    /**
     * Current version
     */
    public $version = '1.0.0';

    /**
     * The single instance of the class
     */
    protected static $_instance = null;

    /**
     * Main instance
     *
     * Ensures only one instance is loaded or can be loaded.
     *
     * @return WP_Admin_Status_Message - Main instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Constructor.
     */
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    /**
     * Start plugin.
     */
    public function init() {
    	if(!defined('WASM_DIR'))
    	    define('WASM_DIR', dirname(__FILE__));

        // Network options page
        add_action('network_admin_menu', array($this, 'wpsm_network_admin_menu'));
        // Process the saving of the network options
        add_action('network_admin_edit_wpsm_update_network_options',  array($this, 'wpsm_update_network_options'));

        // Site options page
        add_action('admin_menu', array( $this, 'wasm_admin_menu'));

        // Status message widget in dashboard
        add_action('wp_dashboard_setup',  array( $this, 'wasm_dashboard_widgets'));

        // Status message notice in dashboard
        add_action( 'admin_notices', array($this, 'wasm_status_notice'));
        
        // Styles
        add_action( 'admin_head', array($this, 'wasm_status_notice_css'));
        add_action( 'admin_head', array($this, 'wasm_admin_enqueue_scripts'));
    }

    /**
     * Run on activation
     */
    public static function activate() {

    }

    /**
     * Add plugin settings submenu under settings for network
     */
    public function wpsm_network_admin_menu($value='') {
        // Create our options page.
        add_submenu_page('settings.php', __('Status Message Options', 'wp-admin-status-message'),
            __('Status Message Options', 'wp-admin-status-message'), 'manage_network_options',
            'wpsm_network_options_page', array($this, 'wpsm_network_options_page_callback'));

        // Create a section
        add_settings_section('default', __('Status Message of all Sites'), false,
            'wpsm_network_options_page');

        // Create and register our option
        register_setting('wpsm_network_options_page', 'wpsm_site_status_msg');
        add_settings_field('wpsm_site_status_msg', __('Status Message', 'wp-admin-status-message'),
            array($this, 'wpsm_network_status_msg_callback'), 'wpsm_network_options_page',
            'default');
    }

    /**
     * Displays option field
     */
    public function wpsm_network_status_msg_callback() { ?>
        <textarea rows="2" cols="64" name="wpsm_site_status_msg" maxlength="255"><?php echo esc_html(get_site_option('wpsm_site_status_msg')); ?></textarea><br>
        <label><?php _e('Enter the status message to set on all sites.', 'wp-admin-status-message') ?></label>
    <?php }

    /**
     * Displays the options page
     */
    public function wpsm_network_options_page_callback() {
        if (isset($_GET['updated'])): ?>
            <div id="message" class="updated notice is-dismissible"><p><?php _e('Options saved.') ?></p></div>
        <?php endif; ?>
        <div class="wrap">
            <h1><?php _e('Status Message Network Options', 'wp-admin-status-message'); ?></h1>
            <form method="POST" action="edit.php?action=wpsm_update_network_options">
                <?php
                settings_fields('wpsm_network_options_page');
                do_settings_sections('wpsm_network_options_page');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }


    /**
     * This function here is hooked up to a special action and
     * necessary to process the saving of the options.
     */
    public function wpsm_update_network_options() {
        // Make sure we are posting from our options page
        check_admin_referer('wpsm_network_options_page-options');

        // This is the list of registered options.
        global $new_whitelist_options;
        $options = $new_whitelist_options['wpsm_network_options_page'];

        // Go through the posted data and save only our options (generic way)
        foreach ($options as $option) {
            if (isset($_POST[$option])) {
                // Save our option with the site's options
                update_site_option($option, $_POST[$option]);
                
                // Override all the sites messages
                if ($option=='wpsm_site_status_msg') {
                    global $wpdb;
                    $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
                    $original_blog_id = get_current_blog_id();

                    foreach ( $blog_ids as $blog_id ) {
                        switch_to_blog( $blog_id );
                        update_option( $option, $_POST[$option]);
                    }

                    switch_to_blog( $original_blog_id );
                }
            }
        }

        // At last we redirect back to our options page.
        wp_redirect(add_query_arg(array('page' => 'wpsm_network_options_page',
          'updated' => 'true'), network_admin_url('settings.php')));
        exit;
    }

    /**
     * Add plugin settings submenu under tools for site
     */
    public function wasm_admin_menu() {
        add_submenu_page('tools.php', __('Status Message Options', 'wp-admin-status-message'), __('Status Message Options', 'wp-admin-status-message'), 'manage_options', 'wpsm_options_page', array($this, 'wpsm_options_page_callback'));

        add_settings_section('default', __('Site Status Message'), false, 'wpsm_options_page');

        register_setting('wpsm_options_page', 'wpsm_site_status_msg');
        add_settings_field('wpsm_site_status_msg', __('Message Text', 'wp-admin-status-message'), array($this, 'wpsm_site_status_msg_callback'), 'wpsm_options_page', 'default');
    }

    /**
     * Displays option field
     */
    public function wpsm_site_status_msg_callback() { ?>
        <textarea rows="2" cols="80" name="wpsm_site_status_msg" maxlength="255"><?php echo get_option('wpsm_site_status_msg'); ?></textarea><br>
        <label><?php _e('Enter the status message to display in admin dashboard Status Message widget.', 'wp-admin-status-message') ?></label>
    <?php }

    /**
     * Displays the options page
     */
    public function wpsm_options_page_callback() {
        if (isset($_GET['settings-updated'])): ?>
            <div id="message" class="updated notice is-dismissible"><p><?php _e('Options saved.') ?></p></div>
        <?php endif; ?>
        <div class="wrap">
            <h1><?php _e('Status Message Options', 'wp-admin-status-message'); ?></h1>
            <form method="POST" action="options.php">
                <?php
                settings_fields('wpsm_options_page');
                do_settings_sections('wpsm_options_page');
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    /**
     * Add status message widget on dashboard
     */
    public function wasm_dashboard_widgets() {
        wp_add_dashboard_widget('dashboard_wasm_status_message_widget', esc_html__('Status Message', 'wp-admin-status-message'), array( $this, 'wasm_dashboard_status_message'));
    }
    
    /**
     * Display status message in widget
     */
    public function wasm_dashboard_status_message() {
        $wasm_status_message = get_option('wpsm_site_status_msg');
        if ($wasm_status_message) {
            echo '<div class="wasm-status-msg-wrap">
                <span class="screen-reader-text">'.
                    __( 'Status Message', 'wp-admin-status-message').
                '</span><span class="dashicons dashicons-format-quote"></span>'.
                wp_kses_post($wasm_status_message).
                '<span class="dashicons dashicons-format-quote wasm-inverted"></span>'.
            '</div>';
        } else  {
            esc_html_e('No status message', 'wp-admin-status-message');
        }
    }

    /**
     * Run on deactivation
     */
    public function wasm_status_notice() {
        $wasm_status_message = wp_kses_post(get_option('wpsm_site_status_msg'));
        if ($wasm_status_message) {
            echo '<div class="wasm-notice wasm-status-msg-wrap">
                <span class="screen-reader-text">'.
                    __( 'Status Message', 'wp-admin-status-message').
                '</span><span class="dashicons dashicons-format-quote"></span>'.
                wp_kses_post($wasm_status_message).
                '<span class="dashicons dashicons-format-quote wasm-inverted"></span>'.
            '</div>';
        }
    }

    /**
     * Status message css
     */
    public function wasm_status_notice_css() {
        global $_wp_admin_css_colors;
        $color_scheme = $_wp_admin_css_colors[get_user_option( 'admin_color')]->colors;
        $color_0 = esc_attr($color_scheme[0]);
        $color_1 = esc_attr($color_scheme[1]);
        $color_2 = esc_attr($color_scheme[2]);
        $color_3 = esc_attr($color_scheme[3]);
        echo "
        <style type='text/css'>
        .wasm-status-msg-wrap {
            background: {$color_2};
            border: 5px solid {$color_1};
            color: #fff;
            font-family: 'Sansita Swashed', cursive;
            font-size: 16px;
            line-height: 1.7;
            text-align: center;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            padding: 12px;
        }
        .wasm-notice {
            margin: 45px 20px 0 2px;
        }
        .wasm-inverted {
            transform: rotate(180deg);
        }
        </style>
        ";
    }

    /**
     * Admin enqueue
     */
    public function wasm_admin_enqueue_scripts() {
        wp_enqueue_style('google-fonts-sansita-swashed', 'https://fonts.googleapis.com/css2?family=Sansita+Swashed&display=swap', '', $this->version);
    }

    /**
     * Run on deactivation
     */
    public static function deactivate() {

    }
}

register_activation_hook(__FILE__, array('WP_Admin_Status_Message', 'activate'));

register_deactivation_hook(__FILE__, array('WP_Admin_Status_Message', 'deactivate') );

WP_Admin_Status_Message::instance();
