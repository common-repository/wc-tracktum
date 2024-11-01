<?php
namespace Tracktum;

class Admin {

	function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu_page' ) );
	}

	public function admin_menu_page() {
		 add_menu_page(
        	__( 'Tracktum', 'wc-tracktum' ),
        	'Tracktum',
        	'manage_options',
        	'tracktum',
        	array( $this, 'tracktum_menu' )
    	);
	}

	public function enqueue_scripts() {
        wp_enqueue_style( 'wctracktum-style', plugins_url( 'assets/css/tracktum-admin.css', tracktum_FILE ), false, filemtime( tracktum_PATH . '/assets/css/tracktum-admin.css' ) );
        wp_enqueue_script( 'wctracktum-admin', plugins_url( 'assets/js/tracktum-admin.js', tracktum_FILE ), array( 'jquery', 'wp-util' ), filemtime( tracktum_PATH . '/assets/js/tracktum-admin.js' ), true );

        wp_localize_script(
            'wctracktum-admin', 'wctracktum_tracking', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
            )
        );
	}

	public function tracktum_menu() {
		$integrations = tracktum()->manager->get_integrations();
		include tracktum_INCLUDES . '/views/admin.php';
	}
}