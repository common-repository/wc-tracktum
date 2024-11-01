<?php
namespace Tracktum;

class Ajax {

	function __construct() {
		add_action( 'wp_ajax_wctracktum_save_settings', [ $this, 'save_settings' ] );
	}

	public function save_settings() {
        if ( ! isset( $_POST['wctracktum-settings-nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['wctracktum-settings-nonce'] ), 'wctracktum-settings-action' )) {
           print 'Sorry, your nonce did not verify.';
           exit;
        } else {

            $settings = wp_unslash( $_POST['settings'] );

    		if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

    		if ( ! isset( $settings ) ) {
                wp_send_json_error();
            }

            $integration_settings = [];

            if ( ! empty( $settings ) ) {
                foreach ( $settings as $field_id => $setting ) {
                    $is_enabled = isset( $setting['enabled'] ) ? true : false;
                    $setting = array_merge( $setting, array( 'enabled' => $is_enabled ) );
                    $integration_settings[ $field_id ] = $setting;
                }
            }

            update_option( 'wctracktum_settings', stripslashes_deep( $integration_settings ) );

            wp_send_json_success( array(
                'message' => __( 'Settings has been saved successfully!', 'wc-tracktum' )
            ) );
        }
	}
}