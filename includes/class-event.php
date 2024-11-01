<?php
namespace Tracktum;

Class Event {

	private $integrations;

	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init_integrations' ] );
        // purchase events
        add_action( 'woocommerce_add_to_cart', array( $this, 'added_to_cart' ), 10, 4 );
        add_action( 'woocommerce_after_checkout_form', array( $this, 'initiate_checkout' ) );
        add_action( 'woocommerce_thankyou', array( $this, 'checkout_complete' ) );
        // view events
        add_action( 'woocommerce_after_single_product', array( $this, 'product_view' ) );
        add_action( 'woocommerce_after_shop_loop', array( $this, 'category_view' ) );

        // Search Event
        add_action( 'pre_get_posts', array( $this, 'product_search' ) );
        // Wishlist Event
        add_filter( 'yith_wcwl_added_to_wishlist', array( $this, 'product_wishlist' ) );
        add_action( 'woocommerce_wishlist_add_item', array( $this, 'product_wishlist' ) );

        // add_action( 'woocommerce_after_cart',	[ $this, 'remove_to_cart' ] );
        add_action( 'woocommerce_before_checkout_billing_form', array( $this, 'checkout_step_1_tracking' ) );
        add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'checkout_step_2_tracking' ) );
        add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'checkout_step_3_tracking') );
        add_action( 'woocommerce_after_checkout_form', array( $this, 'form_field_tracking' ) );
        // add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'add_to_cart' ) );
        add_action( 'admin_footer', array( $this, 'admin_ga_optimize_enabled' ) );


        add_action( 'woocommerce_product_options_reviews', array( $this, 'product_options' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'product_options_save' ), 10, 2 );
        add_action( 'woocommerce_thankyou', array( $this, 'thankyou_page' ) );

        // registration events
        add_action( 'woocommerce_registration_redirect', array( $this, 'wc_redirect_url' ) );
        add_action( 'template_redirect', array( $this, 'track_registration' ) );

        add_action( 'wp_head', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_footer', array( $this, 'products_impression_clicks' ) );
	}

	public function init_integrations() {
		$this->integrations = tracktum()->manager->get_integrations();
	}

	public function run_event( $event, $value = "" ) {
		foreach ( $this->integrations as $integration ) {
            if ( method_exists( $integration, $event ) ) {
                $integration->$event( $value );
            }
        }
	}

	public function product_options() {

	}

	public function product_options_save( $post_id ) {

	}

	public function wc_redirect_url() {

	}

	public function track_registration() {
		$this->run_event('registration');
	}

	public function thankyou_page() {

	}

	public function enqueue_scripts() {
		foreach ( $this->integrations as $integration ) {
			$integration->add_script();
		}
	}

	public function added_to_cart( ) {
		$this->run_event('add_to_cart');
	}

	public function initiate_checkout() {
		$this->run_event('initiate_checkout');
	}

	public function checkout_complete() {
		$this->run_event( 'checkout', $order_id );
	}

	public function product_view() {
		$this->run_event('product_view');
	}

	public function category_view() {
		$this->run_event('category_view');
	}

	public function product_search( $query ) {
		if ( ! is_admin() && $query->is_main_query() && $query->is_search() ) {
             $this->run_event( 'search' );
        }
	}

	public function product_wishlist() {
		$this->run_event('add_to_wishlist');
	}

    public function  products_impression_clicks() {

    }

	public function remove_cart() {
		$this->run_event('remove_from_cart');
	}

	public function checkout_step_1_tracking() {
		$this->run_event('begin_checkout');
	}

	public function checkout_step_2_tracking() {
		$this->run_event('checkout_progress');
	}

	public function checkout_step_3_tracking() {
		$this->run_event('checkout_progress');
	}

	public function form_field_tracking() {

	}

	public function get_ordered_items() {

	}

	public function admin_ga_optimize_enabled() {

	}

	public function process_order_tag() {

	}

	public function process_product_tag() {

	}
}