<?php
/*
Plugin Name: Conversion for Tracktum on Woocommerce
Description: Tracktum Woocommerce Conversion For Google, Facebook, Pinterest
Version: 1.3
Author: Md Kamrul islam
Author URI: https://profiles.wordpress.org/rajib00002/
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wc-tracktum
Domain Path: /languages
*/

if ( !defined( 'ABSPATH' ) ) exit;
require_once __DIR__ . '/vendor/autoload.php';

class Tracktum {

    public $version = '1.3';

	private $container = array();

	public function __construct() {
		$this->define_constants();

        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

        add_action( 'woocommerce_loaded', [ $this, 'init_hooks' ] );
	}

	public function __get( $prop ) {
        if ( array_key_exists( $prop, $this->container ) ) {
            return $this->container[ $prop ];
        }

        return $this->{$prop};
    }

    /**
     * Magic isset to bypass referencing plugin.
     *
     * @param $prop
     *
     * @return mixed
     */
    public function __isset( $prop ) {
        return isset( $this->{$prop} ) || isset( $this->container[ $prop ] );
    }

    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new Self();
        }

        return $instance;
    }

    public function define_constants() {
        define( 'tracktum_VERSION', $this->version );
        define( 'tracktum_FILE', __FILE__ );
        define( 'tracktum_PATH', dirname( tracktum_FILE ) );
        define( 'tracktum_INCLUDES', tracktum_PATH . '/includes' );
        define( 'tracktum_URL', plugins_url( '', tracktum_FILE ) );
        define( 'tracktum_ASSETS', tracktum_URL . '/assets' );
    }

    public function activate() {

    }

    public function deactivate() {

    }

    public function init_hooks() {
        add_action( 'admin_notices', [ $this, 'check_woocommerce_exist' ] );
        add_action( 'init', array( $this, 'localization_setup' ) );
        $this->init_classes();
    }

    public function init_classes() {
        $this->container['ajax']    = new Tracktum\Ajax();
        $this->container['event_']  = new Tracktum\Event();
        $this->container['admin']   = new Tracktum\Admin();
        $this->container['manager'] = new Tracktum\Integration_Manager();
        $this->container['assets']  = new Tracktum\Assets();
    }

    public function localization_setup() {
        load_plugin_textdomain( 'tracktum', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    public function check_woocommerce_exist() {
        if ( ! function_exists( 'WC' ) ) {
            ?>
                <div class="error notice is-dismissible">
                    <p> <?php esc_html_e( '<b>Tracktum</b> requires <a target="_blank" href="https://wordpress.org/plugins/woocommerce/">Woocommerce</a>', 'wc-tracktum' ); ?></p>
                </div>
            <?php
        }
    }
}


function tracktum() {
    return Tracktum::init();
}

tracktum();