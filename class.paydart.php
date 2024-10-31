<?php
/**
 * Gateway class
 */
class Paydart extends WC_Payment_Gateway
{

    protected $msg = array();
    /**
     * Contruction function
     */
    public function __construct() 
    {
        // Go wild in here
        $this->id                 = 'paydart';
        $this->method_title= PaydartConstants::METHOD_TITLE;
        $this->method_description= PaydartConstants::METHOD_DESCRIPTION;
        $getSetting = get_option('woocommerce_paydart_settings');
        $invertLogo = isset($getSetting['invertLogo'])?$getSetting['invertLogo']:"0";
        if ($invertLogo == 1) {
            $this->icon= esc_url(plugin_dir_url(__FILE__) ."assets/".PaydartConstants::PLUGIN_VERSION_FOLDER."/images/paydart_logo_invert.png");
        } else {
            $this->icon= esc_url(plugin_dir_url(__FILE__) ."assets/".PaydartConstants::PLUGIN_VERSION_FOLDER."/images/paydart_logo_paymodes.png");
        }
        $this->has_fields= false;

        $this->init_form_fields();
        $this->init_settings();

        $this->title= PaydartConstants::TITLE;
        $this->description= $this->getSetting('description');

        $this->msg = array('message' => '', 'class' => '');
        
        $this->initHooks();
    }

    /**
     * InitHooks function
    */
    private function initHooks()
    {
        add_action('init', array(&$this, 'check_paydart_response'));
        //update for woocommerce >2.0
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_paydart_response'));
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=') ) {
            add_action('woocommerce_update_options_payment_gateways_paydart', array( &$this, 'process_admin_options' ) );
        } else {
                add_action('woocommerce_update_options_payment_gateways', array( &$this, 'process_admin_options' ) );
        }
        add_action('woocommerce_receipt_paydart', array($this, 'receipt_page'));
        if (is_admin() ) {
            wp_enqueue_style('paydartadminWoopayment', plugin_dir_url(__FILE__) . 'assets/'.PaydartConstants::PLUGIN_VERSION_FOLDER.'/css/admin/paydart.css', array(), time(), '');
        }

        if (!is_admin() ) {
            wp_enqueue_script('paydart-script', plugin_dir_url(__FILE__) . 'assets/'.PaydartConstants::PLUGIN_VERSION_FOLDER.'/js/paydart.js', array('jquery'), time(), true);
        }
    }


    private function getSetting($key)
    {
        return $this->settings[$key];
    }

    private function getPaydartCallbackUrl($order_id)
    {
        $checkout_page_id = get_option('woocommerce_checkout_page_id');
        $checkout_page_id = (int) $checkout_page_id > 0 ? $checkout_page_id : 7;
        return get_site_url() . '/?page_id='.$checkout_page_id.'&order_id='.$order_id.'&&wc-api=paydart';
    }

    public function init_form_fields() 
    {

        /* Code to Handle Website Name Data Start */
        $isWebsiteAdded= get_option('isWebsiteAdded');
        $getSetting = get_option('woocommerce_paydart_settings');
        $website = isset($getSetting['website'])?$getSetting['website']:"";
        $websiteOption=array('WEBSTAGING'=>'WEBSTAGING','DEFAULT'=>'DEFAULT');

        if ($isWebsiteAdded=="") {
            // Old plugin Data, Need to handle previous Website Name
            add_option("isWebsiteAdded", "yes");
            if (!in_array($website, $websiteOption) and $website!="") {
                $websiteOption[$website]=$website; 
            }
            $websiteOption['OTHERS'] = 'OTHERS' ;
            add_option('websiteOption', json_encode($websiteOption));
        }
        $websiteOptionFromDB = json_decode(get_option('websiteOption'), true);
        /* else
        {
        // New Plugin added Nothing to handle
        } */
        /* Code to Handle Website Name Data Start */

        $checkout_page_id = get_option('woocommerce_checkout_page_id');
        $checkout_page_id = (int) $checkout_page_id > 0 ? $checkout_page_id : 7;
        $webhookUrl = esc_url(get_site_url() . '/?wc-api=paydart&webhook=yes');
        $paydartDashboardLink = esc_url("https://dashboard.paydart.com/next/apikeys");
        $paydartPaymentStatusLink = esc_url("https://developer.paydart.com/docs/payment-status/");
        $paydartContactLink = esc_url("https://business.paydart.com/contact-us#developer");
        $this->form_fields = array(
            /*'title' => array(
                'title'         => __('Title', 'paydart'),
                'type'          => 'text',
                'description'   => __('This controls the title which the user sees during checkout.', 'paydart'),
                'default'       => __(PaydartConstants::TITLE, 'paydart'),
            ),*/
            'description' => array(
                'title'         => __('Description', 'paydart'),
                'type'          => 'textarea',
                'description'   => __('This controls the description which the user sees during checkout.', 'paydart'),
                'default'       => __(PaydartConstants::DESCRIPTION, 'paydart')
            ),
            'environment' => array(
                'title'         => __('Environment'), 'paydart',
                'type'          => 'select',
                'custom_attributes' => array( 'required' => 'required' ),
                'options'       => array("0" => "Test/Staging", "1" => "Production"),
                'description'   => __('Select "Test/Staging" to setup test transactions & "Production" once you are ready to go live', 'paydart'),
                'default'       => '0'
            ),
            'merchant_id'=> array(
                'title'         => __('Test/Production Key'),
                'type'          => 'text',
                'custom_attributes' => array( 'required' => 'required' ),
                'description'   => __('Based on the selected Environment Mode, copy the relevant Merchant Key for test or production environment available on <a href="'.$paydartDashboardLink.'" target="_blank">PayDart dashboard</a>.', 'paydart'),
            ),
            'merchant_key' => array(
                'title'         => __('Test/Production Secret'),
                'type'          => 'text',
                'custom_attributes' => array( 'required' => 'required' ),
                'description'   => __('Based on the selected Environment Mode, copy the Merchant Secret for test or production environment available on <a href="'.$paydartDashboardLink.'" target="_blank">PayDart dashboard</a>.', 'paydart'),
            ),
            'invertLogo' => array(
                'title'         => __('Enable Invert Logo'), 'paydart',
                'type'          => 'select',
                'options'       => array("0" => "No", "1" => "Yes"),
                'default'       => '0',
                'description' => 'PayDart PG logo colour can be changed on your WooCommerce Checkout Page.'
            ),
            'enabled'           => array(
                'title'             => __('Enable/Disable', 'paydart'),
                'type'          => 'checkbox',
                'label'         => __('Enable PayDart Payments.', 'paydart'),
                'default'       => 'yes'
            ),
            
        );
    }


    /**
     * Admin Panel Options
     * - Options for bits like 'title'
     **/
    public function admin_options()
    {
        //Echoing HTML safely start
        $default_attribs = array(
            'id' => array(),
            'class' => array(),
            'title' => array(),
            'style' => array(),
            'data' => array(),
            'data-mce-id' => array(),
            'data-mce-style' => array(),
            'data-mce-bogus' => array(),
        );
        $allowed_tags = array(
            'div'           => $default_attribs,
            'span'          => $default_attribs,
            'p'             => $default_attribs,
            'a'             => array_merge(
                $default_attribs, array(
                'href' => array(),
                'target' => array('_blank', '_top'),)
            ),
            'u'             =>  $default_attribs,
            'i'             =>  $default_attribs,
            'q'             =>  $default_attribs,
            'b'             =>  $default_attribs,
            'ul'            => $default_attribs,
            'ol'            => $default_attribs,
            'li'            => $default_attribs,
            'br'            => $default_attribs,
            'hr'            => $default_attribs,
            'strong'        => $default_attribs,
            'blockquote'    => $default_attribs,
            'del'           => $default_attribs,
            'strike'        => $default_attribs,
            'em'            => $default_attribs,
            'code'          => $default_attribs,
            'h1'            => $default_attribs,
            'h2'            => $default_attribs,
            'h3'            => $default_attribs,
            'h4'            => $default_attribs,
            'h5'            => $default_attribs,
            'h6'            => $default_attribs,
            'table'         => $default_attribs      
        );
        //Echoing HTML safely end

        echo wp_kses('<h3>'.__('PayDart', 'paydart').'</h3>', $allowed_tags);
        echo wp_kses('<p>'.__('Online payment solutions for all your transactions by PayDart', 'paydart').'</p>', $allowed_tags);

        echo wp_kses('<table class="form-table">', $allowed_tags);
            $this->generate_settings_html();
        echo wp_kses('</table>', $allowed_tags);
    
        $last_updated = date("d F Y", strtotime(PaydartConstants::LAST_UPDATED)) .' - '.PaydartConstants::PLUGIN_VERSION;

        $footer_text = '<div style="text-align: center;"><hr/>';
        $footer_text .= '<strong>'.__('PHP Version').'</strong> '. PHP_VERSION . ' | ';
        $footer_text .= '<strong>'.__('Wordpress Version').'</strong> '. get_bloginfo('version') . ' | ';
        $footer_text .= '<strong>'.__('WooCommerce Version').'</strong> '. WOOCOMMERCE_VERSION . ' | ';
        $footer_text .= '<strong>'.__('Last Updated').'</strong> '. $last_updated. ' | ';
        $footer_text .= '<a href="'.esc_url(PaydartConstants::PLUGIN_DOC_URL).'" target="_blank">Developer Docs</a>';

        $footer_text .= '</div>';

        echo wp_kses($footer_text, $allowed_tags);
        
    }

    /**
     *  There are no payment fields for paydart, but we want to show the description if set.
    **/
    public function payment_fields()
    {
        if($this->description) echo wpautop(wptexturize($this->description));
    }


    /**
     * Receipt Page
    **/
    public function receipt_page($order) 
    {
        echo $this->generate_paydart_form($order);
    }
    public function getOrderInfo($order)
    {
        if (version_compare(WOOCOMMERCE_VERSION, '2.7.2', '>=')) {
            $data = array(
                'name'=> $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'email'=> $order->get_billing_email(),
                'contact'=> $order->get_billing_phone(),
                'amount'=> $order->get_total(),
            );
        } else {
            $data = array(
                'name'=> $order->billing_first_name . ' ' . $order->billing_last_name,
                'email'=> $order->billing_email,
                'contact'=> $order->billing_phone,
                'amount'=> $order->order_total,
            );
        }

        return $data;
    }
    /* 
     * Get the transaction token
    */
    public function blinkCheckoutSend($order_id, $paramData = array())
    {
        $data=array();
        if (!empty($paramData['amount']) && (int)$paramData['amount'] > 0) {
            $paydartParams = array();

            $paydartParams["data"] = array(
                "customer_details"  => $paramData['customer_details'],
                "billing_details"   => $paramData['billing_details'],
                "shipping_details"  => $paramData['shipping_details'],
                "order_details"     => array(
                    "amount"     => "".$paramData['amount'],
                    "currency"   => "INR",
                    'return_url' => $this->getPaydartCallbackUrl($order_id)
                )
            );

            
            /* prepare JSON string for request */
            $post_data = json_encode($paydartParams, JSON_UNESCAPED_SLASHES);
            $url = PaydartHelper::getPaydartURL(PaydartConstants::INITIATE_TRANSACTION_URL, $this->getSetting('environment'));

            $res= PaydartHelper::executeUrl($url, $post_data, $this->getSetting('merchant_id'), $this->getSetting('merchant_key'));

            //echo $url.",";
            //echo $post_data.",";
            //echo $this->getSetting('merchant_id').",";
            //echo $this->getSetting('merchant_key').",";
            //print_r($res); exit;
            
            $data['response'] = $res;

            if(isset($res['status']) && ($res['status'] == "Created")){
                $data['status'] = $res['status'];
                $data['token'] = $res['token'];
                $data['paydart_orderid'] = $res['order_id'];
                $data['payment_link'] = $res['payment_link'];
                $data['message'] = 'Token generated';
            }else{
                $data['status'] = '';
                $data['token'] = '';
                $data['paydart_orderid'] = '';
                $data['payment_link'] = '';
                $data['message'] = isset($response['message']) ? $response['message'] : 'Something went wrong';
            }
            /* $txntoken = json_encode($res); */
        }

        return $data;
    }
    /**
     * Generate paydart button link
    **/
    public function generate_paydart_form($order_id) 
    {
        global $woocommerce;
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=') ) {
            $order = new WC_Order($order_id);
        } else {
            $order = new woocommerce_order($order_id);
        }

        $order_id = PaydartHelper::getPaydartOrderId($order_id);

        $getOrderInfo = $this->getOrderInfo($order);

        $cust_id = $name = $email = $mobile_no = "";

        if (!empty($getOrderInfo['name'])) {
            $name = $getOrderInfo['name'];
        } else {
            $name = "";
        }

        if (!empty($getOrderInfo['email'])) {
            $cust_id = $email = $getOrderInfo['email'];
        } else {
            $cust_id = "CUST_".$order_id;
        }
        //get mobile no if there for DC_EMI
        if (isset($getOrderInfo['contact']) && !empty($getOrderInfo['contact'])) {
            $mobile_no = $cust_mob_no = $getOrderInfo['contact'];
        } else {
            $mobile_no = $cust_mob_no = "";
        }
        $settings = get_option("woocommerce_paydart_settings");

        $paramData = array(
            'customer_details' => array('name' => $name, 'email' => $email, 'mobile' => $mobile_no),
            'billing_details' => array('address_line1' => $order->get_billing_address_1(), 'address_line2' => $order->get_billing_address_2(), 'country' => (string) $order->get_billing_country(), 'city' => $order->get_billing_city(), 'pin' => $order->get_billing_postcode(), 'province' => $order->get_billing_state()),
            'shipping_details' => array('address_line1' => $order->get_shipping_address_1(), 'address_line2' => $order->get_shipping_address_2(), 'country' => $order->get_shipping_country(), 'city' => $order->get_shipping_city(), 'pin' => $order->get_shipping_postcode(), 'province' => $order->get_shipping_state()),
            'amount' => $getOrderInfo['amount'], 'order_id' => $order_id, 'cust_id' => $cust_id, 'email' => $email,'cust_mob_no' => $cust_mob_no, 'mobile_no' => $mobile_no
        );

        $data = $this->blinkCheckoutSend($order_id, $paramData);

        if(isset($data['status']) && ($data['status'] == "Created")){
            $wait_msg='<div id="paydart-pg-spinner" class="paydart-woopg-loader"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div><div class="bounce4"></div><div class="bounce5"></div><p class="loading-paydart">Loading PayDart...</p></div><div class="paydart-overlay paydart-woopg-loader"></div><div class="paydart-action-btn"><a href="'.$data['payment_link'].'" class="refresh-payment re-invoke">Pay Now</a><a href="'.wc_get_checkout_url().'" class="refresh-payment">Cancel</a></div>';

            $result = $wait_msg;

            $result.= '<script type="text/javascript">
                function invokeBlinkCheckoutPopup(){
                    console.log("method called");

                    jQuery(".loading-paydart").hide();
                    jQuery("#paydart-pg-spinner").hide();
                    jQuery(".paydart-overlay").hide();
                    jQuery(".refresh-payment").show();
                }

                //jQuery(document).ready(function(){ jQuery(".re-invoke").on("click",function(){ invokeBlinkCheckoutPopup(); return false; }); });

                setTimeout(function() {
                    invokeBlinkCheckoutPopup();
                }, 2000);
            </script>';
        }
        else
        {
            $result = '<span style="color:red;">Something went wrong</span>';
        }

        return $result;

    }


    /**
     * Process the payment and return the result
    **/
    public function process_payment($order_id)
    {
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=') ) {
            $order = new WC_Order($order_id);
        } else {
            $order = new woocommerce_order($order_id);
        }

        if (version_compare(WOOCOMMERCE_VERSION, '3.0.0', '>=')) {
            $order_key = $order->get_order_key();
        } else {
            $order_key = $order->order_key;
        }

        if (version_compare(WOOCOMMERCE_VERSION, '2.1', '>=')) {
            return array(
                'result' => 'success',
                'redirect' => add_query_arg('key', $order_key, $order->get_checkout_payment_url(true))
            );
        } else if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            return array(
                'result' => 'success',
                'redirect' => add_query_arg(
                    'order', $order->get_id(), add_query_arg('key', $order_key, $order->get_checkout_payment_url(true))
                )
            );
        } else {
            return array(
                'result' => 'success',
                'redirect' => add_query_arg(
                    'order', $order->get_id(), add_query_arg('key', $order_key, get_permalink(get_option('woocommerce_pay_page_id')))
                )
            );
        }
    }

    /**
     * Check for valid paydart server callback // response processing //
    **/
    public function check_paydart_response()
    {
        global $woocommerce;

        if (!empty(sanitize_text_field($_POST['status']))) {

            $order = array();
			$order_id = sanitize_text_field($_GET['order_id']);

            $order_id = !empty($order_id)? PaydartHelper::getOrderId(sanitize_text_field($order_id)) : 0;

			$responseDescription = (!empty($_POST['status'])) ? sanitize_text_field($_POST['status']) :"";

			if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=') ) {
				$order = new WC_Order($order_id);
			} else {
				$order = new woocommerce_order($order_id);
			}

			if (isset($_GET['webhook']) && $_GET['webhook'] =='yes') {
				$through = "webhook_".time();
			} else {
				$through = "callback_".time();
			}
			if (!empty($order)) {

                $order_data_id = sanitize_text_field($_POST['order_id']);

                $status = sanitize_text_field($_POST['status']);

                $resParams = ['status' => $status, 'order_id' => sanitize_text_field($_POST['order_id']), 'transaction_id' => sanitize_text_field($_POST['transaction_id'])];

                $data['status'] = $status;

				/* save paydart response in db */
                //print_r($resParams);
                //print_r($order_id);
                //print_r($order_data_id);
				saveTxnResponse($resParams, $order_id);
				/* save paydart response in db */

			    // if failed to fetch response
                if($status == "Completed")
                {
					$this->msg['message']= __(PaydartConstants::SUCCESS_ORDER_MESSAGE);
					$this->msg['class']= 'success';

					$order->payment_complete($resParams['transaction_id']);
					$order->reduce_order_stock();

					$message = "<br/>".sprintf(__(PaydartConstants::TRANSACTION_ID), $resParams['transaction_id'])."<br/>".sprintf(__(PaydartConstants::PAYDART_ORDER_ID), $resParams['order_id']);
					$message .= '<br/><span class="msg-by-paydart">By: PayDart '.$through.'</span>';
					$order->add_order_note($this->msg['message'] . $message);
                    $this->setStatusMessage($order, $message, 'completed');
                    $woocommerce->cart->empty_cart();
				} else {
					$message = __(PaydartConstants::ERROR_ORDER_MESSAGE);
					if (!empty($responseDescription)) {
						$message .= sprintf(__(PaydartConstants::REASON), $responseDescription);
					}
					$message .= '<br/><span class="msg-by-paydart">By: PayDart '.$through.'</span>';
					$this->setStatusMessage($order, $message);
				}
			} else {
				$this->setStatusMessage($order, __(PaydartConstants::ERROR_INVALID_ORDER));
			}

            $redirect_url = $this->redirectUrl($order);

            $this->setMessages($this->msg['message'], $this->msg['class']);

            if (isset($_GET['webhook']) && $_GET['webhook'] =='yes') {
                echo "Webhook Received";
            } else {
                wp_redirect($redirect_url);
            }

            exit;
        }
    }
    /**
     * Show template while response 
    */
    private function setStatusMessage($order, $msg = '', $status = 'failed')
    {

        $this->msg['class'] = 'error';
        $this->msg['message'] = $msg;
        if (!empty($order)) {
            $order->update_status($status);
            $order->add_order_note($this->msg['message']);
        }
    }

    /* private function setMessages(){
		global $woocommerce;
		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice( $msg['message'], $msg['class'] );
		} else {
			if( 'success' == $msg['class'] ) {
				$woocommerce->add_message( $msg['message']);
			}else{
				$woocommerce->add_error( $msg['message'] );

			}
			$woocommerce->set_messages();
		}	
	} */

    private function setMessages($message='',$class='')
    {
            global $woocommerce;
        if (function_exists('wc_add_notice') ) {
            wc_add_notice($message, $class);
        } else {
            if ('success' == $class ) {
                $woocommerce->add_message($message);
            } else {
                $woocommerce->add_error($message);
            }
            $woocommerce->set_messages();
        }
    }

    private function redirectUrl($order)
    {
        global $woocommerce;
        // Redirection after paydart payments response.
        if (!empty($order)) {
            if ('success' == $this->msg['class']) {
                $redirect_url = $order->get_checkout_order_received_url();
            } else {
                //$redirect_url = wc_get_checkout_url();
                $redirect_url = $order->get_view_order_url();
            }
        } else {
            $redirect_url = $woocommerce->cart->get_checkout_url();
        }
        return $redirect_url;
    }


    /*
     * End paydart Essential Functions
    **/
}

function paydart_enqueue_script() 
{   
        wp_enqueue_script('paydart-script', plugin_dir_url(__FILE__) . 'assets/'.PaydartConstants::PLUGIN_VERSION_FOLDER.'/js/admin/paydart.js', array('jquery'), time(), true);
}
add_action('admin_enqueue_scripts', 'paydart_enqueue_script');