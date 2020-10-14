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
     * URL dir for plugin
     */
    public $url;

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

        // Set URL
        $this->url = plugin_dir_url(__FILE__);
    }

    /**
     * Start plugin.
     */
    public function init() {
    	if(!defined('WASM_DIR'))
    	    define('WASM_DIR', dirname(__FILE__));
    	
    	if(!defined('WASM_DIR_URL'))
    	    define('WASM_DIR_URL', plugin_dir_url( __FILE__ ));
    	
    	if(!defined('WASM_DIR_PATH'))
    	    define('WASM_DIR_PATH', plugin_dir_path( __FILE__ ));
    }

    /**
     * Run on activation
     */
    public static function activate() {

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
