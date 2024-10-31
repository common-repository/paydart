<?php
/** 
 * PaydartHelper Class 
 */
require_once __DIR__."/PaydartConstants.php";
if(!class_exists('PaydartHelper')) :
    class PaydartHelper 
    {
        /* 
         * Include timestap with order id 
         */
        public static function getPaydartOrderId($order_id)
        {
            if($order_id && PaydartConstants::APPEND_TIMESTAMP) {
                return PaydartConstants::ORDER_PREFIX.$order_id . '_' . date("YmdHis");
            } else {
                return PaydartConstants::ORDER_PREFIX.$order_id;
        }
    }
        /**
         * Exclude timestap with order id
         */
        public static function getOrderId($order_id)
        {
            $timestamp = PaydartConstants::APPEND_TIMESTAMP;
            if (($pos = strrpos($order_id, '_')) !== false && $timestamp) {
                $order_id = substr($order_id, 0, $pos);
            }
            $orderPrefix = PaydartConstants::ORDER_PREFIX;
            if (substr($order_id, 0, strlen($orderPrefix)) == $orderPrefix) {
                $order_id = substr($order_id, strlen(PaydartConstants::ORDER_PREFIX));
            } 
            return $order_id;
        }
        /**
         * Implements getPaydartURL() with params $url and $isProduction.
         */
        public static function getPaydartURL($url = false, $isProduction = 0)
        {
            if (!$url) return false; 
            if ($isProduction == 1) {
                return PaydartConstants::PRODUCTION_HOST . $url;
            } else {
                return PaydartConstants::STAGING_HOST . $url;
            }
        }
        

        public static function executeUrl($apiURL, $postData, $merchant_key, $merchant_secret) {

            $headers = ['merchant_key' => $merchant_key, 'merchant_secret' => $merchant_secret, "Content-Type"=> "application/json"];

            $jsonResponse = wp_remote_post(
                $apiURL, array(
                    'method' => 'POST',
                    'timeout'     => 60, // added
                    'redirection' => 5,  // added
                    'blocking'    => true, // added
                    'httpversion' => '1.0',
                    'sslverify' => false,
                    'headers'     => $headers,
                    'body'        => $postData,
                ) 
            );

            $response_code = wp_remote_retrieve_response_code( $jsonResponse );
            $response_body = wp_remote_retrieve_body($jsonResponse);
            $responseParamList = json_decode($response_body, true);
            $responseParamList['request'] = $postData;

            return $responseParamList;
        }

        public static function createJWTToken($key,$clientId,$environment)
        {
            // Create token header as a JSON string
            $header = json_encode(['alg' => 'HS512','typ' => 'JWT']);
            /* Create token payload as a JSON string */
            $time = time();
            $payload = json_encode(['client-id' => $clientId,'iat'=>$time]);

            // Encode Header to Base64Url String
            $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));

            // Encode Payload to Base64Url String
            $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

            // Create Signature Hash
            $signature = hash_hmac('SHA512', $base64UrlHeader . "." . $base64UrlPayload, $key, true);

            // Encode Signature to Base64Url String
            $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            // Create JWT
            $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

            return $jwt;
        }
    }
endif;
?>