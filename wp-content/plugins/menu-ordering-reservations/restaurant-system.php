<?php
/*
  Plugin Name: Menu - Ordering - Reservations
  Plugin URI: https://www.gloriafood.com/wordpress-restaurant-plugin
  Description: This plugin is all you need to turn your restaurant website into an online business. Using a simple and friendly interface you get a restaurant menu, online food ordering and restaurant booking system. All free, no fees, no hidden costs, no commissions - for unlimited food orders and restaurant reservations.

  Version: 1.3.3
  Author: GloriaFood
  Author URI: https://www.gloriafood.com/
  License: GPLv2+
  Text Domain: menu-ordering-reservations

  @package  RestaurantSystem
  @category Core
  @author   GLOBALFOOD
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class GLF_Restaurant_System{
    var $version = '1.3.3',
        $base_url = 'https://www.gloriafood.com/',
        $api_token = null,
        $custom_css = null,
        $auth_domain = null,
        $auth_token = null,
        $restaurants = null,
        $user = null;

     // Constructor
     function __construct() {
         $this->api_url =  $this->base_url . 'api/';
         $this->load_user_data();
         $this->admin_language = substr(get_user_locale(), 0,2);
         require_once( dirname(__FILE__) . '/includes/class-glf-widget.php');
         add_action( 'widgets_init', array( $this, 'widget_init' ) );
         if(defined( 'DOING_AJAX' ) && DOING_AJAX) {
             add_action('wp_ajax_restaurant_system_insert_dialog', array($this, 'mce_insert_dialog'));
             add_action('wp_ajax_restaurant_system_customize_button', array($this, 'customize_button_dialog'));
         } else if( is_admin() ) {
            add_action('admin_menu', array($this, 'glf_mor_add_menu'));
            add_action('admin_bar_menu', array($this, 'glf_mor_add_admin_bar_menu'), 99);

            add_action('media_buttons', array($this, 'add_ordering_media_button'));
            add_action( 'admin_enqueue_scripts', array( $this, 'add_media_scripts') );

            } else {
                add_shortcode('restaurant-menu-and-ordering', array($this, 'add_ordering_shortcode'));
                add_shortcode('restaurant-reservations', array($this, 'add_reservations_shortcode'));
	            add_shortcode( 'restaurant-full-menu', array( $this, 'add_menu_shortcode' ) );
                add_action( 'wp_print_styles', array( $this, 'add_public_media_scripts') );
        }


         register_activation_hook( __FILE__, array( 'GLF_Restaurant_System', 'glf_mor_install' ) );
         register_uninstall_hook( __FILE__, array( 'GLF_Restaurant_System', 'glf_mor_uninstall' ) );
         add_action('wpmu_new_blog', array('GLF_Restaurant_System', 'glf_mor_new_blog'), 10, 6);
         if ( ! class_exists( 'Glf_Mor_Utils' ) )
             require_once( dirname(__FILE__) . '/includes/class-glf-utils.php' );
     }

     function generate_installation_id () {
         return wp_generate_uuid4();
     }

    function add_shortcode($type, $atts) {
        extract(shortcode_atts(array(
            'ruid' => 'ruid'
        ), $atts));


        if (!isset($atts['ruid'])) {
            $atts['ruid'] = '';
        }


        $label = '';
        $extraAttr = '';
        $extraCss = '';
        switch ($type) {
            case 'ordering':
                $label = 'See MENU & Order';
                break;
            case 'reservations':
                $label = 'Table Reservation';
                $extraAttr =  'data-glf-reservation="true"';
                $extraCss = 'reservation';
                break;
        }

        $customCss = '';
        if (isset($this->custom_css[$type])) {
            foreach ($this->custom_css[$type] as $key => $value) {
                if ($key == 'text') {
                    $label = $value;
                } else {
                    $customCss .= $key . ':' . $value . ($key == 'color' ? ' !important' : '') . ';';
                }
            }
        }

        if (isset($atts['class'])) { // basic || custom
            $html = '<a href="#"><span class="glf-button-default '. $atts['class'] . '" data-glf-cuid="" data-glf-ruid="'.$atts['ruid'].'" ' . $extraAttr . '>' . $label .'</span></a>';
        } else {
            $html = '<span class="glf-button-default glf-button ' . $extraCss . '" style=\'' . $customCss . '\'  data-glf-cuid="" data-glf-ruid="' . $atts['ruid'] . '" ' . $extraAttr . '>' . $label . '</span>';
        }
        $html .= '<script src="https://www.fbgcdn.com/embedder/js/ewm2.js" defer async ></script>';
        return $html;
    }

	function add_menu_shortcode( $atts ) {
		extract( shortcode_atts( array( 'ruid' => '' ), $atts ) );

		if ( empty( $atts['ruid'] ) ) {
			return '';
		}

		$restaurant_menu = $this->glf_mor_restaurant_menu( $atts['ruid'] );

		if ( ! $restaurant_menu ) {
			return '';
		}

		$html = '';
		if ( ! empty( $restaurant_menu->categories ) ) {
			foreach ( $restaurant_menu->categories as $cat_index => $category ) {
				if ( ! empty( $category->items ) ) {
					$html .= '<div class="glf-mor-restaurant-menu-category"><h3>' . $category->name . '</h3>';

					foreach ( $category->items as $item_index => $item ) {
						$html .= '<div class="glf-mor-restaurant-menu-item"><div class="glf-mor-restaurant-menu-item-inner">
                            <div class="glf-mor-restaurant-menu-item-header">
                                <h5 class="glf-mor-restaurant-menu-item-name">' . $item->name . '</h5>
                                <div class="glf-mor-restaurant-menu-item-price" data-price="' . $item->price . '" data-currency="' . $restaurant_menu->currency . '">' . $item->price . ' ' . $restaurant_menu->currency . '</div>
                            </div>' . ( empty( $item->description ) ? '' : '<div class="glf-mor-restaurant-menu-item-description">' . $item->description . '</div>' ) . '
                        </div></div>';
					}

					$html .= "</div>";
				}
			}
		}

		$locale = false;

		$restaurant_data_obj = get_option( 'glf_mor_restaurant_data' );
		if ( $restaurant_data_obj ) {
			foreach ( $restaurant_data_obj->restaurants as $restaurant ) {
				if ( $restaurant->uid === $atts['ruid'] ) {
					$locale = $restaurant->language_code . '-' . $restaurant->country_code;
				}
			}
		}


		if ( ! empty( $html ) ) {
			$html = '<div class="glf-mor-restaurant-menu-wrapper">' . $html . '</div>
            <script type="text/javascript">
                if (typeof jQuery != "undefined") {
                    jQuery(document).ready(function() {
                   jQuery(".glf-mor-restaurant-menu-item-price").each(function() {
                        const el=jQuery(this);
                        const price=parseFloat(el.data("price"));
                        const currency=el.data("currency");
                    
                        el.html(price.toLocaleString(' . ( $locale ? '\'' . $locale . '\'' : 'navigator.language' ) . ',{style:"currency",currency:currency}));
                    });
               });
                    }
            </script>';
		}

		return $html;
	}

     function load_user_data() {
         $restaurant_data_obj = get_option('glf_mor_restaurant_data');
         $pages = array('auth_domain', 'auth_token', 'restaurants', 'user', 'custom_css');
         foreach ($pages as $key) {
             $this->$key = $restaurant_data_obj && isset($restaurant_data_obj->$key) ? $restaurant_data_obj->$key : null;
         }
         $this->installation_id = get_option('glf_mor_installation_id');
     }

    function save_user_data($options) {
        $restaurant_data_obj = get_option('glf_mor_restaurant_data');

        if (!$restaurant_data_obj) $restaurant_data_obj = new stdClass();

        foreach ($options as $key => $value) {
            $this->$key = $value;
            $restaurant_data_obj->$key = $value;
        }
        update_option( 'glf_mor_restaurant_data', $restaurant_data_obj);
    }

    function remove_user_data () {
        delete_option( 'glf_mor_restaurant_data');
        $this->load_user_data();
    }

    function is_authenticated() {
        return $this->auth_token;
    }

    function get_glf_mor_token($target = 'admin') {
        if (!$this->is_authenticated())  return null;
        $remoteUrl = $this->auth_domain . $this->auth_token . '/'. $target;
        $response = wp_remote_post($remoteUrl, array(
                'method' => 'GET',
                'headers' => array()
            )
        );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            die("Something went wrong: $error_message");
        }
        $respone_body = json_decode($response['body']);
        if (isset($respone_body->errorDescription)) {
            die("Something went wrong: $respone_body->errorDescription");
        }

        $url = $response['body'];
        if ($target == 'admin') {
            $url .= '&language_code=' . $this->admin_language;
        }
        return $url;
    }

    function glf_mor_api_call($route, $method = 'GET', $body = '') {
        if (!$this->is_authenticated())  return null;

        if (!$this->api_token) {
            $this->api_token = $this->get_glf_mor_token('api');
        }
        $response = wp_remote_post($this->api_url . $route, array(
                'method' => 'GET',
                'headers' => array('Authorization' => $this->api_token,
                    'content-type' => 'application/json'),
                'body' => $body
            )
        );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            die("Something went wrong: $error_message");
        }

        $respone_body = json_decode($response['body']);
        if (isset($respone_body->errorDescription)) {
            die("Something went wrong: $respone_body->errorDescription");
        }

        return $respone_body;
    }


    function glf_mor_restaurant_menu($restaurantUid,$forceRefresh=false) {
	    if ( $forceRefresh ) {
		    $restaurant_menu=$this->glf_mor_restaurant_menu_get_and_cache($restaurantUid);
	    } else {
		    $restaurant_menu = get_transient( 'glf_mor_restaurant_menu' );

		    if ( false === $restaurant_menu ) {
			    $restaurant_menu=$this->glf_mor_restaurant_menu_get_and_cache($restaurantUid);
		    }
	    }

	    return $restaurant_menu;
    }

    function glf_mor_restaurant_menu_get_and_cache($restaurantUid, $cacheTime=86400) {
	    $restaurant_menu = $this->glf_mor_api_call( "/restaurant/$restaurantUid/menu?active=true" );

	    set_transient( 'glf_mor_restaurant_menu', $restaurant_menu, $cacheTime );

	    return $restaurant_menu;
    }

    function update_restaurants() {
        $restaurants = $this->glf_mor_api_call('user/restaurants');
        $this->save_user_data(array('restaurants' => $restaurants));
    }

     /*
       * Actions perform at loading of admin menu
     */
     function glf_mor_add_menu() {
         $title = 'Menu - Ordering - Reservations';
         if (current_user_can('manage_options')) {
             add_menu_page('Menu - Ordering - Reservations', $title, 'manage_options', 'glf-admin', array(
                 $this,
                 'glf_mor_page_file_path'
             ), plugins_url('images/logo.png', __FILE__), '2.2.9');

             add_submenu_page('glf-admin', $title . ' Dashboard', 'Dashboard', 'manage_options', 'glf-admin', array(
                 $this,
                 'glf_mor_page_file_path'
             ));

             $hook = add_submenu_page('glf-admin', $title . ' Publishing', 'Publishing', 'manage_options', 'glf-publishing', array(
                 $this,
                 'glf_mor_page_file_path'
             ));

             add_action( "load-$hook", array( $this, 'publishing_help' ) );

             add_submenu_page('glf-admin', $title . ' Extras', 'Extras', 'manage_options', 'glf-extras', array(
                 $this,
                 'glf_mor_page_file_path'
             ));

             add_submenu_page('glf-admin', $title . ' Partner Program', 'Partner Program', 'manage_options', 'glf-partner', array(
                 $this,
                 'glf_mor_page_file_path'
             ));

         add_options_page($title . ' Options', $title, 'manage_options', 'glf-options', array($this, 'glf_mor_page_file_path'));
         }
     }

    public function publishing_help() {
        $current_screen = get_current_screen();

        // Screen Content
        if ( current_user_can( 'manage_options' ) ) {
            $help_sections = array(
                    'customize' => __('Customize the buttons', 'menu-ordering-reservations'),
                    'pages' => __('Add buttons to pages', 'menu-ordering-reservations'),
                    'navigation' => __('Add buttons to the navigation', 'menu-ordering-reservations'),
                    'widget' => __('Add buttons to sidebar or footer', 'menu-ordering-reservations')
            );

            foreach ($help_sections as $key => $title) {
                ob_start();
                require(dirname(__FILE__) . '/includes/help/publishing/' . $key . '.php');
                $content = ob_get_contents();
                ob_end_clean();

                $current_screen->add_help_tab(
                    array(
                        'id' => $key,
                        'title' => $title,
                        'content' => '<div class="glf-help-section">' . $content . '</div>'
                    )
                );
            }

        }

        // Help Sidebar
        $current_screen->set_help_sidebar(
            '<p><strong>' . __( 'For more information:', 'menu-ordering-reservations') . '</strong></p>' .
            '<p><a href="https://www.gloriafood.com/restaurant-ideas/add-online-ordering-button-wordpress" target="_blank">'     . __( 'See the complete guide', 'menu-ordering-reservations') . '</a></p>'
        );
    }

    function glf_mor_add_admin_bar_menu() {
        global $wp_admin_bar, $pagenow;

        if ($pagenow != 'admin.php' || !$_GET['page'] || strpos($_GET['page'], 'glf-') != 0) return;

        if (! isset($this->restaurants[0])) return;
        $menus = array(
            array(
                'id' => 'glf-restaurant',
                'title' => '<img src="' . plugins_url('images/logo.png', __FILE__) . '"> Restaurant Admin',
                'href' => $this->get_glf_mor_token(),
                'meta' => array(
                    'target' => 'blank'
                )
            )
        );

        foreach ( apply_filters( 'render_webmaster_menu', $menus ) as $menu )
            $wp_admin_bar->add_menu( $menu );
    }

    function add_ordering_media_button() {
        ?>
        <a id="glf-ordering" class="button thickbox" onclick="glf_mor_showThickBox('restaurant_system_insert_dialog')">
            <img src="<?= plugins_url('images/logo.png', __FILE__)?>"> Menu - Ordering - Reservations
        </a>
        <?php
    }

     function add_ordering_shortcode($atts) {
        return $this->add_shortcode('ordering', $atts);
     }

    function add_reservations_shortcode($atts) {
        return $this->add_shortcode('reservations', $atts);
    }


     function iframe_src($section) {
         $params = array('parent_window' => 'wordpress');

         switch ($section) {
             case 'menu':
                 $params['r'] = 'app.admin.setup.menu_app.menu_editor';
                 $params['hide_top_menu'] = 'true';
                 $params['hide_left_menu'] = 'true';
                 $params['hide_left_navigation'] = 'true';
                 break;

             case 'setup':
                 $params['r'] = 'app.admin_ftu.setup';
                 $params['hide_top_menu'] = 'true';
                 $params['hide_left_menu'] = 'true';
                 break;


             default:
                 break;
         }

         $src = $this->get_glf_mor_token();
         $src .= strpos($src, '?') ? '&' : '?1';

         foreach ($params as $key=>$value) {
             $src.= "&$key=$value";
         }

         return $src;
     }

    function glf_mor_ends_with($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

     /*
      * Actions perform on loading of left menu or settings pages
      */
     function glf_mor_page_file_path() {
         $pages = array('admin', 'publishing', 'options', 'partner', 'extras');
         $screen = get_current_screen();

         foreach ($pages as $page) {
             if ( $this->glf_mor_ends_with( $screen->base, $page ) !== false ) {
                 if (in_array($page, array('admin', 'publishing'))) {
                     $this->update_restaurants();
                 }
                 require( dirname(__FILE__) . '/includes/'. $page .'.php' );
                 break;
             }
         }
     }

    function mce_insert_dialog() {
        include( dirname(__FILE__) . '/includes/media-button.php' );
    }


    function customize_button_dialog() {
        include( dirname(__FILE__) . '/includes/customize-button.php' );
    }


     /**
      * Styling & JS: loading stylesheets and js for the plugin.
      */
     public function add_media_scripts( $page ) {
        wp_enqueue_script( 'restaurant_system_media_btn_js', plugin_dir_url( __FILE__ ) . 'js/wp-editor-glf-media-button.js', array(), $this->version);
        wp_enqueue_script( 'restaurant_system_clipboard_js', plugin_dir_url( __FILE__ ) . 'js/clipboard.min.js', array(), '1.7.1');
        wp_enqueue_script( 'restaurant_system_customize_btn_js', plugin_dir_url( __FILE__ ) . 'js/admin-customize-button.js', array(), $this->version);
        wp_enqueue_script( 'restaurant_system_footer_js', plugin_dir_url( __FILE__ ) . 'js/footer.js', array(), $this->version,true);
        wp_enqueue_style( 'restaurant_system_style', plugins_url('css/style.css', __FILE__),false, $this->version);
        wp_enqueue_style( 'restaurant_system_public_style', plugins_url('css/public-style.css', __FILE__),false, $this->version);
        if ($this->glf_mor_ends_with($page, 'partner') || $this->glf_mor_ends_with($page, 'extras')) {
            wp_enqueue_style( 'restaurant_system_website_style', plugins_url('css/style-website.css', __FILE__),false, $this->version);
        }

        wp_enqueue_script('thickbox');
        wp_enqueue_style('thickbox');
     }

    public function add_public_media_scripts( $page ) {
        wp_enqueue_style( 'restaurant_system_public_style', plugins_url('css/public-style.css', __FILE__),false, $this->version);
    }

    public function widget_init() {
        register_widget( 'Glf_Mor_Widget' );
    }

    /*
     * Propagate action to the whole network
     */
    static function glf_mor_propagate_in_network($networkwide, $action) {
        global $wpdb;

        if (function_exists('is_multisite') && is_multisite()) {
            if ($networkwide) {
                $old_blog_id = $wpdb->blogid;

                $blogids = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
                foreach ($blogids as $blog_id) {
                    switch_to_blog($blog_id);

                    if ($action === 'install') {
                        self::_glf_mor_install();
                    } else if ($action === 'uninstall') {
                        self::_glf_mor_uninstall();
                    }
                }

                switch_to_blog($old_blog_id);
                return;
            }
        }

        if ($action === 'install') {
            self::_glf_mor_install();
        } else if ($action === 'uninstall') {
            self::_glf_mor_uninstall();
        }
    }

    /*
     * Actions performed on plugin activation
     */
    static function glf_mor_install($networkwide) {
        self::glf_mor_propagate_in_network($networkwide, 'install');
    }

    static function _glf_mor_install() {
        if (!get_option('glf_mor_installation_id')) {
            update_option('glf_mor_installation_id', wp_generate_uuid4());
        }
    }

    /*
     * Actions performed on plugin uninstall
     */
    static function glf_mor_uninstall() {
        self::glf_mor_propagate_in_network(true, 'uninstall');
    }

    static function _glf_mor_uninstall() {
        delete_option('glf_mor_installation_id');
        delete_option('glf_mor_restaurant_data');
    }

    /*
     * Actions performed when a new blog is added to the multisite
     */
    static function glf_mor_new_blog($blog_id, $user_id, $domain, $path, $site_id, $meta) {
        global $wpdb;

        if (is_plugin_active_for_network('menu-ordering-reservations/restaurant-system.php')) {
            $old_blog_id = $wpdb->blogid;

            switch_to_blog($blog_id);
            self::_glf_mor_install();

            switch_to_blog($old_blog_id);
        }
    }

}

 new GLF_Restaurant_System();
?>
