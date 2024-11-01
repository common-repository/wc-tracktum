<?php
namespace Tracktum\Integrations;
use Tracktum\Integration;

class Tracktum_Integration_Pinterest  extends Integration {

  	public function __construct() {
    	$this->id = 'pinterest';
    	$this->name = __( 'Pinterest', 'wc-tracktum');
  	}

  	public function get_settings() {
    	$settings = [
			'id'      => array(
                'type'      => 'text',
                'name'      => 'pixel_id',
                'label'     => __( 'Pixel ID', 'wc-tracktum' ),
                'required'  => true,
                'value'     => '',
                'help'      => sprintf( __( 'Find the Pixel ID from <a href="%s" target="_blank">here</a>.', '' ), 'https://www.pinterest.com/' )
            ),
            'events'    => array(
                'type'    => 'multicheck',
                'name'    => 'events',
                'label'   => __( 'Events', 'wc-tracktum' ),
                'value'   => '',
                'options' => array(
					'AddToCart'    => __( 'Add to Cart', 'wc-tracktum' ),
					'Checkout'     => __( ' Checkout', 'wc-tracktum' ),
					'Signup'       => __( 'Signup', 'wc-tracktum' )
                )
            ),
		];

		return $settings;
  	}

	public function add_script() { 

		if ( ! $this->is_enabled() ) {
            return;
        }

        $integration_settins    = $this->get_integration_settings();
        $pinterest_pixel_id      = ! empty( $integration_settins[0]['pixel_id'] ) ? $integration_settins[0]['pixel_id'] : '';
    ?>
		<script>
		!function(e){if(!window.pintrk){window.pintrk = function () {
		window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var
		  n=window.pintrk;n.queue=[],n.version="3.0";var
		  t=document.createElement("script");t.async=!0,t.src=e;var
		  r=document.getElementsByTagName("script")[0];
		  r.parentNode.insertBefore(t,r)}}("https://s.pinimg.com/ct/core.js");

		          <?php
            if ( is_user_logged_in() ) {
                $user_email = wp_get_current_user()->user_email;

                echo $this->build_event( $pinterest_pixel_id, array( 'em' => $user_email ), 'load' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            } else {
                echo $this->build_event( $pinterest_pixel_id, array(), 'load' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }

            echo $this->build_event( 'PageView', [] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>
		</script>

	<?php 
        $this->add_to_cart_ajax();
	}

	public function build_event( $event_name, $params = array(), $method = 'track' ) {
		return sprintf( "pintrk('%s', '%s', %s);", $method, $event_name, json_encode( $params, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT ) );
	}

	public function product_view() {
        if ( ! $this->event_enabled( 'ViewContent' ) ) {
            return;
        }

        $product      = wc_get_product( get_the_ID() );
        $content_type = 'product';

        if ( ! $product ) {
            return;
        }

        if ( $product->get_type() === 'variable' ) {
            $content_type = 'product_group';
        }
        


        $code = $this->build_event('ViewContent', array(
            'product_name'  =>  $product->get_title(),
            'product_id'   =>  json_encode( array( $product->get_id() ) ),
            // 'content_type'  =>  $content_type,
            'value'         =>  $product->get_price(),
            'currency'      => get_woocommerce_currency()
        ));

        
        if ( $code ) {
            wc_enqueue_js( $code );
        }
	}

	public function category_view() {
        if ( ! $this->event_enabled( 'ViewCategory' ) ) {
            return;
        }

        global $wp_query;

        $products = array_values( array_map( function( $item ) {
            return wc_get_product( $item->ID );
        }, $wp_query->get_posts() ) );

        // if any product is a variant, fire the pixel with
        // content_type: product_group
        $content_type = 'product';
        $product_ids  = array();

        foreach ( $products as $product ) {
            if ( ! $product ) {
                continue;
            }

            $product_ids[] = $product->get_id();

            if ( $product->get_type() === 'variable' ) {
                $content_type = 'product_group';
            }
        }

        $categories = $this->get_product_categories( get_the_ID() );

        $code = $this->build_event('ViewCategory', array(
            'product_name'      =>  $categories['name'],
            'product_category'  =>  $categories['categories'],
            'product_id'       =>  json_encode( array_slice( $product_ids, 0, 10 ) ),
            // 'content_type'      =>  $content_type,
        ));

		wc_enqueue_js($code);
	}

	public function search() {
		if ( ! $this->event_enabled( 'Search' ) ) {
            return;
        }


        if ( ! is_admin() && is_search() && get_search_query() != '' && get_query_var( 'post_type' )  == 'product' ) {
            if ( class_exists( 'WooCommerce' ) ) {
                $this->trigger_search_event();
            } else {
                add_action( 'wp_head', array( $this, 'trigger_search_event' ) );
            }
        }

	}

    public function trigger_search_event() {
        $code = $this->build_event( 'Search', array(
            'search_query' =>  get_search_query()
        ) );

        if ( $code ) {
            wc_enqueue_js( $code );
        }
    }

	public function add_to_cart() {
		if ( ! $this->event_enabled( 'AddToCart' ) ) {
            return;
        }

        error_log('pinterest add tocart');

		$code = $this->build_event('AddToCart', array(
			'value'          => WC()->cart->total ? WC()->cart->total : 0,
			'currency'     	 => get_woocommerce_currency(),
			'order_quantity' => WC()->cart->get_cart_contents_count(),
		));

		wc_enqueue_js($code);
	}

	public function add_to_cart_ajax() {
        
        if ( ! $this->event_enabled( 'AddToCart' ) ) {
            return;
        }

        $integration_settins    = $this->get_integration_settings();
        $pinterest_pixel_id      = ! empty( $integration_settins[0]['pixel_id'] ) ? $integration_settins[0]['pixel_id'] : '';
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                $(document).on('added_to_cart', function (event, fragments, dhash, button) {
                    var currencySymbol = $($(button.get()[0]).closest('.product')
                        .find('.woocommerce-Price-currencySymbol').get()[0]).text();

                    var price = $(button.get()[0]).closest('.product').find('.amount').text();
                    var originalPrice = price.split(currencySymbol).slice(-1).pop();

                    pintrk('track', 'AddToCart', {
                        content_ids: [ $(button).data('product_id') ],
                        value: originalPrice,
                        currency: '<?php echo esc_attr( get_woocommerce_currency() ) ?>'
                    });
                });
            });
        </script>
        <?php
    }

	public function checkout( $order_id ) {
		if ( ! $this->event_enabled( 'Checkout' ) ) {
            return;
        }

        $order        = new \WC_Order( $order_id );
        $content_type = 'product';
        $product_ids  = array();

        foreach ( $order->get_items() as $item ) {
            $product = wc_get_product( $item['product_id'] );
            $product_ids[]['product_id'] = $product->get_id();
            $product_ids[]['product_name'] = $product->get_name();
            $product_ids[]['product_price'] = $product->get_price();

            if ( $product->get_type() === 'variable' ) {
                $content_type = 'product_group';
            }
        }

		$code = $this->build_event('checkout', array(
			'value'          => WC()->cart->total ? WC()->cart->total : 0,
			'order_quantity' => WC()->cart->get_cart_contents_count(),
			'currency'     => get_woocommerce_currency(),
			'line_items'  => json_encode( $product_ids )
		));

		wc_enqueue_js($code);
	}

	public function registration() {

		if( ! $this->event_enabled( 'Signup' ) ) {
			return ;
		}
		
		$code = $this->build_event('Signup');
		// wc_enqueue_js($code);
	}

	public function print_event_script() {

	}
}
return new Tracktum_Integration_Pinterest();