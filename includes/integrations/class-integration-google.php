<?php
namespace Tracktum\Integrations;
use Tracktum\Integration;

class Tracktum_Integration_Google extends Integration {

    public function __construct() {
      $this->id = 'google';
      $this->name = __( 'Google', 'wc-tracktum');
    }

    public function get_settings() {
      $settings = [
          'id'  => array(
              'type'        => 'text',
              'name'        => 'account_id',
              'label'       => __( 'Account ID', 'wc-tracktum' ),
              'value'       => '',
              'placeholder' => 'AW-123456789',
              'help'        =>  ''
          ),

          'events'    => array(
                'type'    => 'multicheck',
                'name'    => 'events',
                'label'   => __( 'Events', 'wc-tracktum' ),
                'value'   => '',
                'options' => array(
                  
                  'Registration' => array(
                        'event_label_box'   => true,
                        'label'             => __( 'CompleteRegistration', 'wc-tracktum' ),
                        'label_name'       => 'Registration-label',
                        'placeholder'      => 'Add Your Registration Label'
                  ),

                  'AddToCart'  => array(
                      'event_label_box'   => true,
                      'label'             => __( 'AddToCart', 'wc-tracktum' ),
                      'label_name'       => 'AddToCart-label',
                      'placeholder'      => 'Add Your AddToCart Label'
                  ),

                  'AddToWishlist'  => array(
                      'event_label_box'   => true,
                      'label'             => __( 'AddToWishlist', 'conversion' ),
                      'label_name'       => 'AddToWishlist-label',
                      'placeholder'      => 'Add Your AddToWishlist Label'
                  ),

                  'purchase' => array (
                        'event_label_box'   => true,
                        'label'             => __( 'purchase', 'wc-tracktum' ),
                        'label_name'       => 'purchase-label',
                        'placeholder'      => 'Add Your purchase Label'
                  ),

                  'ViewContent' => array (
                    'event_label_box'   => true,
                    'label'             => __( 'ViewContent', 'conversion' ),
                    'label_name'       => 'ViewContent-label',
                    'placeholder'      => 'Add Your ViewContent Label'
                  ),

                  'Search' => array (
                    'event_label_box'   => true,
                    'label'             => __( 'Search', 'conversion' ),
                    'label_name'       => 'Search-label',
                    'placeholder'      => 'Add Your Search Label'
                  ),
                )
            )
      ];

      return $settings;
    }

	  public function add_script() { 
        if ( ! $this->is_enabled() ) {
            return;
        }

        $settings   = $this->get_integration_settings();
        $account_id = ! empty( $settings[0]['account_id'] ) ? $settings[0]['account_id'] : '';

        if ( empty( $account_id ) ) {
            return;
        }

      ?>
      <!-- Global site tag (gtag.js) - Google Ads: 1007037091 -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $account_id ); ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments)};
            gtag('js', new Date());

            gtag('config', '<?php echo esc_attr( $account_id ); ?>');
        </script>

	 <?php }

	  public function build_event( $event_name, $params = array(), $method = 'event' ) {
      return sprintf( "gtag('%s', '%s', %s);", $method, $event_name, json_encode( $params, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES ) );
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

    public function remove_from_cart() {
      
      $code = $this->build_event('remove_from_cart', [

      ]);
    
    }

    public function add_to_cart() {

      if ( ! $this->event_enabled( 'AddToCart' ) ) {
          return;
      }

      $code = $this->build_event('add_to_cart', [
          'items' => [
              'value'        => 25,
              'currency'     => 'USD',
              'content_type' => 'product',
              'content_ids'  => '201'
          ]
      ]);

      wc_enqueue_js($code);
      
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

    public function begin_checkout() {

      $code = $this->build_event('begin_checkout', [

      ]);

      wc_enqueue_js($code);
    }

    public function checkout_progress() {
      $code = $this->build_event('checkout_progress',[

      ]);

      wc_enqueue_js($code);
    }

    public function initiate_checkout() {
      
      if ( ! $this->event_enabled( 'purchase' ) ) {
          return;
      }

      $code = $this->build_event('begin_checkout', [
        'currency' => 'USD',
        'value' => 2
      ]);

      wc_enqueue_js($code);
    
    }

	  public function checkout( $order_id ) {

      if ( ! $this->event_enabled( 'purchase' ) ) {
          return;
      }


      $settings   = $this->get_integration_settings();
      $account_id = isset( $settings[0]['account_id'] ) ? $settings[0]['account_id'] : '';
      $label      = isset( $settings[0]['events']['purchase-label'] ) ? $settings[0]['events']['purchase-label'] : '';

      if ( empty( $account_id ) || empty( $label ) ) {
          return;
      }

      $order = new WC_Order( $order_id );

      $code = $this->build_event( 'purchase', array(
          'send_to'        => sprintf( "%s/%s", $account_id, $label ),
          'transaction_id' => $order_id,
          'value'          => $order->get_total() ? $order->get_total() : 0,
          'currency'       => get_woocommerce_currency()
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

    }
}

return new Tracktum_Integration_Google();