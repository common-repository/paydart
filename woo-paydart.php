<?php
/**
 * Plugin Name: PayDart
 * Plugin URI: https://www.paydart.co/paydart
 * Description: This plugin allow you to accept payments using PayDart. This plugin will add a PayDart Payment option on WooCommerce checkout page, when user choses PayDart as Payment Method, he will redirected to PayDart website to complete his transaction and on completion his payment, paydart will send that user back to your website along with transactions details. This plugin uses server-to-server verification to add additional security layer for validating transactions. Admin can also see payment status for orders by navigating to WooCommerce > Orders from menu in admin.
 * Version: 1.0.0
 * Author: MENA PayTech Systems Limited
 * Author URI: https://www.paydart.co/
 * Tags: PayDart, PayDart, PayWithPayDart, PayDart, PayDart Plugin
 * Requires at least: 4.0.1
 * Tested up to: 6.1.1
 * Requires PHP: 5.6
 * Text Domain: PayDart
 * WC requires at least: 2.0.0
 * WC tested up to: 7.2.0
 */



/**
 * Add the Gateway to WooCommerce
 **/
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once __DIR__.'/includes/PaydartHelper.php';

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'woocommerce_paydart_add_action_links');
function woocommerce_paydart_add_action_links( $links ) 
{
    $settting_url = array(
     '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=paydart')) . '"><b>Settings</b></a>',
     '<a href="' . esc_url(PaydartConstants::PLUGIN_DOC_URL) . '" target="_blank"><b>Docs</b></a>',
    );
     return array_merge($settting_url, $links);
}

/* Create table 'paydart_order_data' after install paydart plugin */
if (function_exists('register_activation_hook'))
register_activation_hook(__FILE__, 'install_paydart_plugin');
/* Drop table 'paydart_order_data' after uninstall paydart plugin */
if (function_exists('register_deactivation_hook') )
register_deactivation_hook(__FILE__, 'uninstall_paydart_plugin');


function install_paydart_plugin()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'paydart_order_data';
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`order_id` int(11) NOT NULL,
			`paydart_order_id` VARCHAR(255) NOT NULL,
			`transaction_id` VARCHAR(255) NOT NULL,
			`status` ENUM('0', '1')  DEFAULT '0' NOT NULL,
			`paydart_response` TEXT,
			`date_added` DATETIME NOT NULL,
			`date_modified` DATETIME NOT NULL,
			PRIMARY KEY (`id`)
		);";
    $wpdb->query($sql);
}

function uninstall_paydart_plugin()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'paydart_order_data';
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
    delete_option('woocommerce_paydart_settings');
}
function paydartWoopayment_enqueue_style() 
{
    wp_enqueue_style('paydartWoopayment', plugin_dir_url(__FILE__) . 'assets/'.PaydartConstants::PLUGIN_VERSION_FOLDER.'/css/paydart.css', array(), time(), '');
    wp_enqueue_script('paydart-script', plugin_dir_url(__FILE__) . 'assets/'.PaydartConstants::PLUGIN_VERSION_FOLDER.'/js/paydart.js', array('jquery'), time(), true);
}
add_action('wp_head', 'paydartWoopayment_enqueue_style');

if (PaydartConstants::SAVE_PAYDART_RESPONSE) {
    // Add a paydart payments box only for shop_order post type (order edit pages)
    add_action('add_meta_boxes', 'add_paydart_block');
    function add_paydart_block()
    {
        global $wpdb;
        $settings = get_option("woocommerce_paydart_settings");
        $post_id1 = sanitize_text_field($_GET['post']);
        $post_id = preg_replace('/[^a-zA-Z0-9]/', '', $post_id1);
        if(! $post_id ) return; // Exit

        $results = getPaydartOrderData($post_id);
        // paydart enabled and order is exists with paym_order_data
        if ($settings['enabled'] == 'yes' && !empty($results)) {
            add_meta_box('_paydart_response_table', __('PayDart Payments'), '_paydart_response_table', 'shop_order', 'normal', 'default', array('results' => $results
                )
            );
        }  
    }

    function _paydart_response_table($post = array(),$data = array())
    {
        //Echoing HTML safely start
        global $allowedposttags;
        $allowed_atts = array(
            'align'      => array(),
            'class'      => array(),
            'type'       => array(),
            'id'         => array(),
            'dir'        => array(),
            'lang'       => array(),
            'style'      => array(),
            'xml:lang'   => array(),
            'src'        => array(),
            'alt'        => array(),
            'href'       => array(),
            'rel'        => array(),
            'rev'        => array(),
            'target'     => array(),
            'novalidate' => array(),
            'type'       => array(),
            'value'      => array(),
            'name'       => array(),
            'tabindex'   => array(),
            'action'     => array(),
            'method'     => array(),
            'for'        => array(),
            'width'      => array(),
            'height'     => array(),
            'data'       => array(),
            'title'      => array(),
        );
        $allowedposttags['form']     = $allowed_atts;
        $allowedposttags['label']    = $allowed_atts;
        $allowedposttags['input']    = $allowed_atts;
        $allowedposttags['textarea'] = $allowed_atts;
        $allowedposttags['iframe']   = $allowed_atts;
        $allowedposttags['script']   = $allowed_atts;
        $allowedposttags['style']    = $allowed_atts;
        $allowedposttags['strong']   = $allowed_atts;
        $allowedposttags['small']    = $allowed_atts;
        $allowedposttags['table']    = $allowed_atts;
        $allowedposttags['span']     = $allowed_atts;
        $allowedposttags['abbr']     = $allowed_atts;
        $allowedposttags['code']     = $allowed_atts;
        $allowedposttags['pre']      = $allowed_atts;
        $allowedposttags['div']      = $allowed_atts;
        $allowedposttags['img']      = $allowed_atts;
        $allowedposttags['h1']       = $allowed_atts;
        $allowedposttags['h2']       = $allowed_atts;
        $allowedposttags['h3']       = $allowed_atts;
        $allowedposttags['h4']       = $allowed_atts;
        $allowedposttags['h5']       = $allowed_atts;
        $allowedposttags['h6']       = $allowed_atts;
        $allowedposttags['ol']       = $allowed_atts;
        $allowedposttags['ul']       = $allowed_atts;
        $allowedposttags['li']       = $allowed_atts;
        $allowedposttags['em']       = $allowed_atts;
        $allowedposttags['hr']       = $allowed_atts;
        $allowedposttags['br']       = $allowed_atts;
        $allowedposttags['tr']       = $allowed_atts;
        $allowedposttags['td']       = $allowed_atts;
        $allowedposttags['p']        = $allowed_atts;
        $allowedposttags['a']        = $allowed_atts;
        $allowedposttags['b']        = $allowed_atts;
        $allowedposttags['i']        = $allowed_atts;
        //Echoing HTML safely end

        $table_html = '<div class="" id="paydart_area"><div class="message"></div>';
        $results = $data['args']['results'];
        //$table_html .= '<div class="btn-area"><img class="paydart-img-loader" src="'.admin_url('images/loading.gif').'"><button type="button" id="button-paydart-fetch-status" class="button-paydart-fetch-status button">'.__(PaydartConstants::FETCH_BUTTON).'</button></div>';
        $paydart_data = array();
        if (!empty($results)) {
            $paydart_data = json_decode($results['paydart_response'], true);
            if (!empty($paydart_data)) {
                $table_html .= '<table class="paydart_block" id="paydart_table">';
                foreach ($paydart_data as $key => $value) {
                    if ($key!=='request') {
                        $table_html .= '<tr><td>'.ucfirst(str_replace("_", " ", $key)).'</td><td>' .$value.'</td></tr>';
                    }
                }
                $table_html .= '</table>';
                $table_html .= '<input type="hidden" id="paydart_order_id" name="paydart_order_id" value="'.$results['paydart_order_id'].'"><input type="hidden" id="order_data_id" name="order_data_id" value="'.$results['id'].'"><input type="hidden" id="paydart_woo_nonce" name="paydart_woo_nonce" value="'.wp_create_nonce('paydart_woo_nonce').'">';
            }
        }
        $table_html .= '</div>';

        echo wp_kses($table_html, $allowedposttags);
    }
    function getPaydartOrderData($order_id)
    {
        global $wpdb;
        $sql = "SELECT * FROM `".$wpdb->prefix ."paydart_order_data` WHERE ". $wpdb->prepare(" `order_id` = %d", $order_id)." ORDER BY `id` DESC LIMIT 1";
        return $wpdb->get_row($sql, "ARRAY_A");
    }

    add_action('admin_head', 'woocommerce_paydart_add_css_js');

    function woocommerce_paydart_add_css_js() 
    {
        ?>
    <style>
            #paydart_area .message{float:left;} 
            #paydart_area .btn-area{ float: right;}
            #paydart_area .btn-area .paydart-img-loader{ margin: 6px;float: left; display:none;}
            .paydart_response{padding: 7px 15px;margin-bottom: 20px;border: 1px solid transparent;border-radius: 4px;text-align: center;}
            .paydart_response.error-box{color: #a94442;background-color: #f2dede;border-color: #ebccd1;}
            .paydart_response.success-box{color: #155724;background-color: #d4edda;border-color: #c3e6cb;}
            .paydart_block{table-layout: fixed;width: 100%;}
            .paydart_block td{word-wrap: break-word;}.paydart_highlight{ font-weight: bold;}
            .redColor{color:#f00;}
            .wp-core-ui .button.button-paydart-fetch-status{float: left; line-height: normal; background: #2b9c2b; color: #fff; border-color: #2b9c2b;}
            .wp-core-ui .button.button-paydart-fetch-status:hover{background:#32bd32}
    </style>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            jQuery("#button-paydart-fetch-status").click(function(){
                var paydart_order_id = jQuery("#paydart_order_id").val();
                var order_data_id = jQuery("#order_data_id").val();
                var paydart_woo_nonce = jQuery("#paydart_woo_nonce").val();
                $('.paydart-img-loader').show();

                jQuery.ajax({
                  type:"POST",
                  dataType: 'json',
                  data:{action:"savetxnstatus", paydart_order_id:paydart_order_id, order_data_id:order_data_id,paydart_woo_nonce:paydart_woo_nonce},
                    url: "<?php echo admin_url("admin-ajax.php");?>",
                    success: function(data) {
                        $('.paydart-img-loader').hide();
                        if (data.success == true) {
                            var html = '';
                            $.each(data.response, function (index, value) {
                                html += "<tr>";
                                html += "<td>" + index + "</td>";
                                html += "<td>" + value + "</td>";
                                html += "</tr>";
                            });
                            jQuery('#paydart_table').html(html);
                            jQuery('#paydart_area div.message').html('<div class="paydart_response success-box">' + data.message + '</div>');
                        } else {
                            jQuery('#paydart_area div.message').html('<div class="paydart_response error-box">' + data.message + '</div>');
                        }
                    }
                });
            });
        });
      </script>
    <?php }

    add_action('wp_ajax_savetxnstatus', 'savetxnstatus');

    function savetxnstatus()
    {

        if (!wp_verify_nonce($_POST['paydart_woo_nonce'], 'paydart_woo_nonce')) die('You are not authorised!');

        $settings = get_option("woocommerce_paydart_settings");
        $json = array("success" => false, "response" => '', 'message' => __(PaydartConstants::RESPONSE_ERROR));

        if (!empty($_POST['paydart_order_id']) && PaydartConstants::SAVE_PAYDART_RESPONSE) {
            $reqParams = array(
                "MID"        => $settings['merchant_id'],
                "ORDERID"    => sanitize_text_field($_POST['paydart_order_id'])
            );

            $retry = 1;

            $response   =   1;

            if ($response) {
                $message = __(PaydartConstants::RESPONSE_SUCCESS);
                $json = array("success" => true, "response" => $response, 'message' => $message);
            }
        }
        echo json_encode($json);die;
    }

    /**
     * Save response in db
    */
    function saveTxnResponse($data = array(), $order_id, $id = false){
        global $wpdb;

        if(empty($data['status'])) return false;

        $status             = (!empty($data['status']) && $data['status'] =='Completed') ? 1 : 0;
        $paydart_order_id  = (!empty($data['order_id'])? $data['order_id']:'');
        $transaction_id     = (!empty($data['transaction_id'])? $data['transaction_id']:'');

        if ($id !== false) {
            $wpdb->query(
                $wpdb->prepare("UPDATE " . $wpdb->prefix . "paydart_order_data SET order_id =  %d, paydart_order_id =  %s, transaction_id =  %s, status =  %s, paydart_response =  %s, date_modified = NOW() WHERE id = %d AND paydart_order_id = %s", $order_id, $paydart_order_id, $transaction_id, $status, json_encode($data), $id, $paydart_order_id)
            );
            return $id;
        } else {
            $wpdb->query(
                $wpdb->prepare("INSERT INTO " . $wpdb->prefix . "paydart_order_data SET order_id =  %d, paydart_order_id =  %s, transaction_id =  %s, status =  %s, paydart_response =  %s, date_added = NOW(), date_modified = NOW()", $order_id, $paydart_order_id, $transaction_id, $status, json_encode($data))
            );

            return $wpdb->insert_id;
        }
    }
}
    add_action('plugins_loaded', 'woocommerce_paydart_init', 0);

    function woocommerce_paydart_init() {
        // If the WooCommerce payment gateway class is not available nothing will return
       if (!class_exists('WC_Payment_Gateway') ) return;

        // WooCommerce payment gateway class to hook Payment gateway
        require_once(plugin_basename('class.paydart.php'));

        add_filter('woocommerce_payment_gateways', 'woocommerce_add_paydart_gateway' );
        function woocommerce_add_paydart_gateway($methods) 
        {
            $methods[] = 'paydart';
            return $methods;
        }

       /**
         * Localisation
         */
        load_plugin_textdomain('wc-paydart', false, dirname(plugin_basename(__FILE__)) . '/languages');

        if(isset($_GET['paydart_response']) && sanitize_text_field($_GET['paydart_response'])) {
           add_action('the_content', 'paydartResponseMessage');
        }

        add_action('wp_head', 'woocommerce_paydart_front_add_css');

        function woocommerce_paydart_front_add_css() 
        { 
        ?>
        <style>
            .paydart_response{padding:15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; text-align: center;}
            .paydart_response.error-box{color: #a94442; background-color: #f2dede; border-color: #ebccd1;}
            .paydart_response.success-box{color: #155724; background-color: #d4edda; border-color: #c3e6cb;}
        </style>
        <?php } 

        function paydartResponseMessage($content)
        {
            return '<div class="paydart_response box '.htmlentities(sanitize_text_field($_GET['type'])).'-box">'.htmlentities(urldecode(sanitize_text_field($_GET['paydart_response']))).'</div>'.$content;
        }
    }
