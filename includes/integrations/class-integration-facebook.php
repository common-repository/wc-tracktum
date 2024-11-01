<?php
namespace Tracktum\Integrations;
use Tracktum\Integration;

class Tracktum_Integration_Facebook extends Integration {

	public function __construct() {
		$this->id       = 'facebook';
		$this->name     = __( 'Facebook', 'wc-tracktum');
		$this->supports = [];
	}

	public function get_settings() {
		$settings = [
			'id'      => array(
                'type'      => 'text',
                'name'      => 'pixel_id',
                'label'     => __( 'Pixel ID', 'wc-tracktum' ),
                'required'  => true,
                'value'     => '',
                'help'      => sprintf( __( 'Find the Pixel ID from <a href="%s" target="_blank">here</a>.', '' ), 'https://www.facebook.com/ads/manager/pixel/facebook_pixel' )
            ),
            'events'    => array(
                'type'    => 'multicheck',
                'name'    => 'events',
                'label'   => __( 'Events', 'wc-tracktum' ),
                'value'   => '',
                'options' => array(
					'AddToCart'        => __( 'Add to Cart', 'wc-tracktum' ),
					'InitiateCheckout' => __( 'Initiate Checkout', 'wc-tracktum' ),
					'Purchase'         => __( 'Purchase', 'wc-tracktum' ),
					'Registration'     => __( 'Complete Registration', 'wc-tracktum' ),
                    'ViewContent'      => __( 'ViewContent', 'wc-tracktum'),
                    'AddToWishlist'    => __( 'AddToWishlist', 'wc-tracktum'),
                    'ViewCategory'     => __( 'ViewCategory', 'wc-tracktum' ),
                    'Search'           => __( 'Search', 'wc-tracktum' )
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
        $facebook_pixel_id      = ! empty( $integration_settins[0]['pixel_id'] ) ? $integration_settins[0]['pixel_id'] : '';
	?>
		<script>
			  !function(f,b,e,v,n,t,s)
  {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
  n.callMethod.apply(n,arguments):n.queue.push(arguments)};
  if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
  n.queue=[];t=b.createElement(e);t.async=!0;
  t.src=v;s=b.getElementsByTagName(e)[0];
  s.parentNode.insertBefore(t,s)}(window, document,'script',
  'https://connect.facebook.net/en_US/fbevents.js');

        <?php
            if ( is_user_logged_in() ) {
                $user_email = wp_get_current_user()->user_email;

                echo $this->build_event( $facebook_pixel_id, array( 'em' => $user_email ), 'init' );// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
            } else {
                echo $this->build_event( $facebook_pixel_id, array(), 'init' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }

            echo $this->build_event( 'PageView', [] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>

		</script>
	<?php
        $this->print_event_script();
        $this->add_to_cart_ajax();
	}

	public function build_event( $event_name, $params = array(), $method = 'track' ) {
        return sprintf( "fbq('%s', '%s', %s);", $method, $event_name, json_encode( $params, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT ) );
    }


    public function product_view() {
        $product      = wc_get_product( get_the_ID() );
        $content_type = 'product';

        if ( ! $product ) {
            return;
        }

        if ( $product->get_type() === 'variable' ) {
            $content_type = 'product_group';
        }
        
        if ( ! $this->event_enabled( 'ViewContent' ) ) {
            return;
        }

    	$code = $this->build_event('ViewContent', array(
			'content_name'  =>  $product->get_title(),
            'content_ids'   =>  json_encode( array( $product->get_id() ) ),
            'content_type'  =>  $content_type,
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
			'content_name'      =>  $categories['name'],
            'content_category'  =>  $categories['categories'],
            'content_ids'       =>  json_encode( array_slice( $product_ids, 0, 10 ) ),
            'content_type'      =>  $content_type,
		));

		if ( $code ) {
            wc_enqueue_js($code);
        }
    }

    public function remove_from_cart() {


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
            'search_string' =>  get_search_query()
        ) );

        if ( $code ) {
            wc_enqueue_js( $code );
        }
    }

    public function add_to_cart() {

    	if ( ! $this->event_enabled( 'AddToCart' ) ) {
            return;
        }

        $product_ids = $this->get_content_ids_from_cart( WC()->cart->get_cart() );
		
		$code = $this->build_event('AddToCart', array(
			'content_ids'  => json_encode( $product_ids ),
            'content_type' => 'product',
            'value'        => WC()->cart->total ? WC()->cart->total : 0,
            'currency'     => get_woocommerce_currency()
		));


		wc_enqueue_js($code);

    }

    public function add_to_cart_ajax() {
        
        if ( ! $this->event_enabled( 'AddToCart' ) ) {
            return;
        }

        $integration_settins    = $this->get_integration_settings();
        $facebook_pixel_id      = ! empty( $integration_settins[0]['pixel_id'] ) ? $integration_settins[0]['pixel_id'] : '';
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                $(document).on('added_to_cart', function (event, fragments, dhash, button) {
                    var currencySymbol = $($(button.get()[0]).closest('.product')
                        .find('.woocommerce-Price-currencySymbol').get()[0]).text();

                    var price = $(button.get()[0]).closest('.product').find('.amount').text();
                    var originalPrice = price.split(currencySymbol).slice(-1).pop();

                    wcfbq('<?php echo esc_attr( $facebook_pixel_id ) ?>', 'AddToCart', {
                        content_ids: [ $(button).data('product_id') ],
                        content_type: 'product',
                        value: originalPrice,
                        currency: '<?php echo esc_attr( get_woocommerce_currency() ) ?>'
                    });
                });
            });
        </script>
        <?php
    }

    public function add_to_wishlist() {
        if ( ! $this->event_enabled( 'AddToWishlist' ) ) {
            return;
        }

        if ( ! defined( 'YITH_WCWL' ) && ! class_exists( 'WC_Wishlists_Plugin' ) ) {
            return;
        }

        global $product;

        $code = $this->build_event(
            'AddToWishlist',
            array(
                'content_ids'  => $product->get_id(),
                'content_type' => 'product',
            )
        );

        if ( $code ) {
            wc_enqueue_js( $code );
        }
    }

    public function initiate_checkout() {

    	if ( ! $this->event_enabled( 'InitiateCheckout' ) ) {
            return;
        }

		$product_ids = $this->get_content_ids_from_cart( WC()->cart->get_cart() );

        $code = $this->build_event( 'InitiateCheckout', array(
            'num_items'    => WC()->cart->get_cart_contents_count(),
            'content_ids'  => json_encode( $product_ids ),
            'content_type' => 'product',
            'value'        => WC()->cart->total ? WC()->cart->total : 0,
            'currency'     => get_woocommerce_currency()
        ) );

		wc_enqueue_js($code);
    }

	public function checkout( $order_id ) {

		if ( ! $this->event_enabled( 'Purchase' ) ) {
            return;
        }

        $order        = new \WC_Order( $order_id );
        $content_type = 'product';
        $product_ids  = array();

        foreach ( $order->get_items() as $item ) {
            $product = wc_get_product( $item['product_id'] );

            $product_ids[] = $product->get_id();

            if ( $product->get_type() === 'variable' ) {
                $content_type = 'product_group';
            }
        }

        $code = $this->build_event( 'Purchase', array(
            'content_ids'  => json_encode($product_ids),
            'content_type' => $content_type,
            'value'        => $order->get_total() ? $order->get_total() : 0,
            'currency'     => get_woocommerce_currency()
        ) );

        wc_enqueue_js( $code );
	}

	public function registration() {
		if ( ! $this->event_enabled( 'Registration' ) ) {
            return;
        }

        $code = $this->build_event( 'CompleteRegistration' );
        
        wc_enqueue_js( $code );
	}


    public function print_event_script() {
        ?>
        <script>
            (function (window, document) {
                if (window.wcfbq) return;
                window.wcfbq = (function () {
                    if (arguments.length > 0) {
                        var pixelId, trackType, contentObj;

                        if (typeof arguments[0] == 'string') pixelId = arguments[0];
                        if (typeof arguments[1] == 'string') trackType = arguments[1];
                        if (typeof arguments[2] == 'object') contentObj = arguments[2];

                        var params = [];
                        if (typeof pixelId === 'string' && pixelId.replace(/\s+/gi, '') != '' &&
                        typeof trackType === 'string' && trackType.replace(/\s+/gi, '')) {
                            params.push('id=' + encodeURIComponent(pixelId));
                            switch (trackType) {
                                case 'PageView':
                                case 'ViewContent':
                                case 'Search':
                                case 'AddToCart':
                                case 'InitiateCheckout':
                                case 'AddPaymentInfo':
                                case 'Lead':
                                case 'CompleteRegistration':
                                case 'Purchase':
                                case 'AddToWishlist':
                                    params.push('ev=' + encodeURIComponent(trackType));
                                    break;
                                default:
                                    return;
                            }

                            params.push('dl=' + encodeURIComponent(document.location.href));
                            if (document.referrer) params.push('rl=' + encodeURIComponent(document.referrer));
                            params.push('if=false');
                            params.push('ts=' + new Date().getTime());

                            if (typeof contentObj == 'object') {
                                for (var u in contentObj) {
                                    if (typeof contentObj[u] == 'object' && contentObj[u] instanceof Array) {
                                        if (contentObj[u].length > 0) {
                                            for (var y = 0; y < contentObj[u].length; y++) { contentObj[u][y] = (contentObj[u][y] + '').replace(/^\s+|\s+$/gi, '').replace(/\s+/gi, ' ').replace(/,/gi, '§'); }
                                            params.push('cd[' + u + ']=' + encodeURIComponent(contentObj[u].join(',').replace(/^/gi, '[\'').replace(/$/gi, '\']').replace(/,/gi, '\',\'').replace(/§/gi, '\,')));
                                        }
                                    }
                                    else if (typeof contentObj[u] == 'string')
                                        params.push('cd[' + u + ']=' + encodeURIComponent(contentObj[u]));
                                }
                            }

                            params.push('v=' + encodeURIComponent('2.7.19'));

                            var imgId = new Date().getTime();
                            var img = document.createElement('img');
                            img.id = 'fb_' + imgId, img.src = 'https://www.facebook.com/tr/?' + params.join('&'), img.width = 1, img.height = 1, img.style = 'display:none;';
                            document.body.appendChild(img);
                            window.setTimeout(function () { var t = document.getElementById('fb_' + imgId); t.parentElement.removeChild(t); }, 1000);
                        }
                    }
                });
            })(window, document);
        </script>
        <?php
    }
}

return new Tracktum_Integration_Facebook();