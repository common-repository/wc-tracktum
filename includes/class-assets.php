<?php
namespace Tracktum;

class Assets {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend' ] );
        add_action( 'admin_enqueue_scripts',[ $this, 'register_backend' ] , 1800 );
	}

	public function register_frontend() {
        wp_register_style( 'tracktum_style', tracktum_ASSETS . '/css/tracktum.css',  [], false, $media = 'all' );
        wp_enqueue_style( 'tracktum_style' );
        wp_register_script( 'tracktum_script', tracktum_ASSETS . '/js/tracktum.js', [], false, false );
        wp_enqueue_script( 'tracktum_script' );
    }

    public function register_backend() {
        wp_enqueue_script( 'tracktum_admin_script', tracktum_ASSETS . '/js/tracktum-admin.js' );
        wp_enqueue_style( 'tracktum_admin_style', tracktum_ASSETS . '/css/tracktum-admin.css' );
    }
}