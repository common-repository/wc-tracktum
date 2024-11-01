<?php
namespace Tracktum;

class Integration_Manager {

    private $integrations = array();

    public function __construct() {
        $this->register_integration();
    }

    public function register_integration() {
        $this->integrations['facebook']   = require_once tracktum_INCLUDES . '/integrations/class-integration-facebook.php';
        $this->integrations['google']     = require_once tracktum_INCLUDES . '/integrations/class-integration-google.php';
        $this->integrations['pinterest']  = require_once tracktum_INCLUDES . '/integrations/class-integration-pinterest.php';
        $this->integrations              = apply_filters( 'tracktum_integrations', $this->integrations );
    }

    public function get_integrations() {
    	if ( empty( $this->integrations ) ) {
            return;
        }

        return $this->integrations;
    }
}