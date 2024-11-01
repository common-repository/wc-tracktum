<?php
namespace Tracktum;

abstract class Integration {

	protected $id;
	protected $name;
	protected $enabled;
	protected $settings = [];
	protected $supports = [];

	public function get_id() {
		return $this->id;
	}

	public function get_name() {
		return $this->name;
	}

	public function is_enabled() {
		$settings = $this->get_integration_settings();

        if ( $settings && $settings[ 'enabled' ] == true ) {
            return true;
        }

        return false;
	}

	public function supports( $feature ) {
		if ( in_array( $feature, $this->supports ) ) {
            return true;
        }

        return false;
	}

	public function get_integration_settings() {
		$integration_settings = get_option( 'wctracktum_settings', array() );

        if ( isset( $integration_settings[ $this->id ] ) ) {
            return $integration_settings[ $this->id ];
        }

        return false;
	}

	public function event_enabled( $event ) {
		$settings = $this->get_integration_settings();

        if ( isset( $settings[0]['events'] ) && array_key_exists( $event, $settings[0]['events'] ) && $settings[0]['events'][ $event ] == 'on' ) {
            return true;
        }

        return false;
	}

	protected function get_content_ids_from_cart( $cart ) {
        $product_ids = array();

        foreach ($cart as $item) {
            $product_ids[] = $item['data']->get_id();
        }

        return $product_ids;
    }

   	public function get_product_categories( $product_id ) {
		$category_path    = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'all' ) );
        $content_category = array_values( array_map( function($item) {
            return $item->name;
        }, $category_path ) );

        $content_category_slice = array_slice( $content_category, -1 );
        $categories             = empty( $content_category ) ? '""' : implode(', ', $content_category);

        return array(
            'name'       => array_pop( $content_category_slice ),
            'categories' => $categories
        );
	}


	abstract public function get_settings();
	abstract public function add_script();
}