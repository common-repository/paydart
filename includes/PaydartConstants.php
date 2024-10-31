<?php
class PaydartConstants{
    CONST PRODUCTION_HOST                       = "https://ulis.live:4008/";
    CONST STAGING_HOST                          = "https://ulis.live:4008/";

    CONST INITIATE_TRANSACTION_URL              = "api/v1/orders/create";
    CONST ORDER_STATUS_URL                      = "api/v1/orders/details";

    CONST SAVE_PAYDART_RESPONSE                 = true;
    CONST APPEND_TIMESTAMP                      = true;

    CONST ORDER_PREFIX= "";
    CONST X_REQUEST_ID= "PLUGIN_WOOCOMMERCE_";
    CONST PLUGIN_DOC_URL= "#";

    CONST MAX_RETRY_COUNT                       = 3;
    CONST CONNECT_TIMEOUT                       = 10;
    CONST TIMEOUT                               = 10;


    CONST LAST_UPDATED= "20221228";
    CONST PLUGIN_VERSION= "1.0.0";
    CONST PLUGIN_VERSION_FOLDER= "277";

    CONST CUSTOM_CALLBACK_URL= "";
    

    CONST METHOD_TITLE= "PayDart";
    CONST METHOD_DESCRIPTION= "The best payment gateway provider through credit card, debit card & UPI.";

    CONST TITLE= "PayDart";
    CONST DESCRIPTION= "The best payment gateway provider through credit card, debit card & UPI.";

    CONST FRONT_MESSAGE= "Thank you for your order, please click the button below to pay with paydart.";
    CONST NOT_FOUND_TXN_URL= "Something went wrong. Kindly contact with us.";
    CONST PAYDART_PAY_BUTTON= "Pay via PayDart";
    CONST CANCEL_ORDER_BUTTON= "Cancel order & Restore cart";
    CONST POPUP_LOADER_TEXT= "Thank you for your order. We are now redirecting you to paydart to make payment.";

    CONST TRANSACTION_ID= "<b>Transaction ID:</b> %s";
    CONST PAYDART_ORDER_ID= "<b>PayDart Order ID:</b> %s";

    CONST REASON= " Reason: %s";
    CONST FETCH_BUTTON= "Fetch Status";

    //Success
    CONST SUCCESS_ORDER_MESSAGE= "Thank you for your order. Your payment has been successfully received.";
    CONST RESPONSE_SUCCESS= "Updated <b>STATUS</b> has been fetched";
    CONST RESPONSE_STATUS_SUCCESS= " and Transaction Status has been updated <b>PENDING</b> to <b>%s</b>";
    CONST RESPONSE_ERROR= "Something went wrong. Please again'";

    //Error
    CONST PENDING_ORDER_MESSAGE= "Your payment has been pending!";
    CONST ERROR_ORDER_MESSAGE= "Your payment has been failed!";
    CONST ERROR_SERVER_COMMUNICATION= "It seems some issue in server to server communication. Kindly connect with us.";
    CONST ERROR_AMOUNT_MISMATCH= "Security Error. Amount Mismatched!";
    CONST ERROR_INVALID_ORDER= "No order found to process. Kindly contact with us.";
}

?>