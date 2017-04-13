<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

/**
 * Square v1 and v2 PHP wrapper
 * @author Ben Miller <bmiller@three29.com>
 *
 */

class Square
{
    /**
     * Square API url
     * @var string
     */
    protected $base_url;

    /**
     * timeout in seconds
     * @var float
     */
    protected $timeout;

    /**
     * Square API version (v1|v2)
     * @var string
     */
    protected $version;

    /**
     * Square v1 Access Token
     * @var string
     */
    protected $v1_token;

    /**
     * Square v2 Access Token
     * @var string
     */
    protected $v2_token;

    /**
     * Whether to location id to an endpoint
     * @var bool
     */
    protected $location_based = false;

    /**
     * v1 Location ID
     * @var string
     */
    protected $v1_location_id;

    /**
     * v2 Location ID
     * @var string
     */
    protected $v2_location_id;

    /**
     * HTTP Type (GET|POST|PUT|PATCH|DELETE|HEAD)
     * @var string
     */
    protected $request_type;

    /**
     * Square API endpoint
     * @var string
     */
    protected $endpoint;

    /**
     * Instance of GuzzleHttp\Client
     * @var object
     * @uses GuzzleHttp\Client
     */
    protected $client;

    /**
     * placeholder for body content
     * @var array
     */
    protected $body;

    /**
     * placeholder for multipart content
     * @var array
     */
    protected $multipart;

    /**
     * placeholder for query content
     * @var array
     */
    protected $query;


    public function __construct()
    {
        $this->base_url       = 'http://REPLACE_ME';
        $this->timeout        = 60;
        $this->v1_token       = 'V1_TOKEN_HERE';
        $this->v2_token       = 'V2_TOKEN_HERE';
        $this->v1_location_id = 'V1_LOCATION_ID';
        $this->v2_location_id = 'V2_LOCATION_ID';


        $this->client = new \GuzzleHttp\Client([
            // Base URI is used with relative requests
            'base_uri' => $this->base_url,
            // You can set any number of default request options.
            'timeout'  => $this->timeout,
        ]);
    }

    /**
     * This is the function that actually triggers the request to Square
     *
     * @return string JSON return from Square
     */
    private function _call()
    {
        // if required fields are empty cancel out
        if ( empty($this->request_type) || empty($this->version) || empty($this->endpoint))
        {
            return false;
        }

        // if the path requires a location base, add it to the endpoint
        if ( $this->location_based == true )
        {
            if ($this->version == 'v1')
            {
                $url = $this->version . '/' . $this->v1_location_id . '/' . $this->endpoint;
            } else if ($this->version == 'v2')
            {
                $url = $this->version . '/locations/' . $this->v2_location_id . '/' . $this->endpoint;
            }
        } else {
            $url = $this->version . '/' . $this->endpoint;
        }

        $options = array(
            'headers' => array(
                'Accept' => 'application/json'
            )
        );

        // set approprioate access_token per version
        if ($this->version == 'v1')
        {
            $options['headers']['Authorization'] = 'Bearer ' . $this->v1_token;
        } else if ($this->version == 'v2')
        {
            $options['headers']['Authorization'] = 'Bearer ' . $this->v2_token;
        }

        // if the call has body parameters, add it to the request
        if ( ! empty( $this->body ) )
        {
            $options['json'] = json_encode( $this->body );
        }

        // if the call has multipart parameters, add it to the request
        if ( ! empty( $this->multipart ) )
        {
            $options['multipart'] = $this->multipart ;
        }

        // if the call has query parameters, add it to the request
        if ( ! empty( $this->query ) )
        {
            $options['query'] = $this->query ;
        }

        try{
            // make the call
            $response = $this->client->request( strtoupper( $this->request_type ), $url, $options );

            // if call is successful echo out the body
            if ( $response->getStatusCode() == 200 ){
                return $response->getBody();
            } else {
                return $response;
            }

        } catch ( RequestException $e ) {
            // guzzle error
            echo Psr7\str( $e->getRequest() );
            if ( $e->hasResponse() ) {
                echo Psr7\str( $e->getResponse() );
            }
        } catch ( ClientException $e ) {
            // network error
            echo Psr7\str( $e->getRequest() );
            echo Psr7\str( $e->getResponse() );
        } catch ( Exception $e )
        {
            // general error
            echo $e->getMessage();
        }
    }


    /***************************************************************************
    This section contains all of the Categories related queries.
    ***************************************************************************/

    /**
     * Lists all of a location's item categories.
     *
     * @return string returns JSON string of 0 or more Square Category objects
     */
    public function listCategories()
    {
        $this->version = 'v1';
        $this->endpoint = 'categories';
        $this->request_type = 'GET';
        $this->location_based = true;

        return $this->_call();
    }



    /***************************************************************************
    This section contains all of the Checkout related queries.
    ***************************************************************************/

    /**
     * Creates a Checkout response that links a checkoutId and checkout_page_url
     * that customers can be directed to in order to provide their payment
     * information using a payment processing workflow hosted on
     * connect.squareup.com.
     *
     * @param array $order Order array containing line items
     * @param string $user_email The email of the user checking out
     * @return string JSON string containing Sqaure Checkout object
     */
    public function createCheckout( $order, $user_email )
    {
        $this->version = 'v2';
        $this->endpoint = 'checkouts';
        $this->request_type = 'POST';
        $this->location_based = true;
        $this->body = array(
            'idempotency_key' => uniqid(),
            'order' => $order,
            'pre_populate_buyer_email' => $user_email,
            'redirect_url' => 'https://example.com/order-confirm_REPLACE_ME'
        );

        return $this->_call();
    }


    /***************************************************************************
    This section contains all of the Customer related queries.
    ***************************************************************************/

    /**
     * Creates a new customer for a business,
     * which can have associated cards on file.
     *
     * @param object $customer A customer object containing first, last, and email
     * @return string JSON string containing Square Customer object
     */
    public function createCustomer( $customer )
    {
        $this->version = 'v2';
        $this->endpoint = 'customers';
        $this->request_type = 'POST';
        $this->location_based = false;
        $this->body = array(
            'given_name' => $customer->first, // first name
            'family_name' => $customer->last, // last name
            'email_address' => $customer->email
        );

        return $this->_call();
    }


    /**
     * Lists a business's customers.
     *
     * @param string $cursor optional used in pagination of results
     * @return string JSON string containing 0 or more Square Customer objects
     */
    public function listCustomers( $cursor = NULL )
    {
        $this->version = 'v2';
        $this->endpoint = 'customers';
        $this->request_type = 'GET';
        $this->location_based = false;
        $this->body = array(
            'cursor' => $cursor
        );

        return $this->_call();
    }


    /**
     * Updates the details of an existing customer.
     *
     * @param object Customer object containing id, first, last, and email
     * @return string JSON string containing Square Customer object
     */
    public function updateCustomer( $customer )
    {
        $this->version = 'v2';
        $this->endpoint = 'customers/' . $customer->id;
        $this->request_type = 'PUT';
        $this->location_based = false;
        $this->body = array(
            'given_name' => $customer->first, // first name
            'family_name' => $customer->last, // last name
            'email_address' => $customer->email
        );

        return $this->_call();
    }


    /**
     * Returns details for a single customer.
     *
     * @param object $customer A customer object
     * @return string JSON string containing Square Customer object
     */
    public function getCustomer( $customer )
    {
        $this->version = 'v2';
        $this->endpoint = 'customers/' . $customer->id;
        $this->request_type = 'GET';
        $this->location_based = false;

        return $this->_call();
    }


    /**
     * Returns details for a single customer.
     *
     * @param object $customer A customer object
     * @return string Empty JSON string if success
     */
    public function deleteCustomer( $customer )
    {
        $this->version = 'v2';
        $this->endpoint = 'customers/' . $customer->id;
        $this->request_type = 'GET';
        $this->location_based = false;

        return $this->_call();
    }


    /**
     * Adds a card on file to an existing customer.
     *
     * @param object $customer A customer object
     * @return string JSON string containing Sqaure Card object
     */
    public function addCardToCustomer( $customer )
    {
        $this->version = 'v2';
        $this->endpoint = 'customers/' . $customer->id . '/cards';
        $this->request_type = 'POST';
        $this->location_based = false;
        $this->body = array(
            'card_nonce' => $nonce,
            'billing_address' => array(
                'postal_code'=> $customer->zip,
            ),
            'cardholder_name' => $customer->first . ' ' . $customer->last
        );

        return $this->_call();
    }


    /**
     * Adds a card on file to an existing customer.
     *
     * @param object $customer A customer object
     * @param object $card A card object
     * @return string Empty JSON string if success
     */
    public function deleteCardFromCustomer( $customer, $card )
    {
        $this->version = 'v2';
        $this->endpoint = 'customers/' . $customer->id . '/cards/' . $card->id;
        $this->request_type = 'DELETE';
        $this->location_based = false;

        return $this->_call();
    }


    /***************************************************************************
    This section contains all of the Discount related queries.
    ***************************************************************************/

    /**
     * Lists all of a location's discounts.
     *
     * @return string JSON string of 0 or more Square Discount objects
     */
    public function listDiscounts()
    {
        $this->version = 'v1';
        $this->endpoint = 'discounts';
        $this->request_type = 'GET';
        $this->location_based = true;


        return $this->_call();
    }


    /***************************************************************************
    This section contains all of the Fee related queries.
    ***************************************************************************/

    /**
     * Lists all of a location's fees (taxes).
     *
     * @return string JSON array containing zero or more Fee objects.
     */
    public function listFees()
    {
        $this->version = 'v1';
        $this->endpoint = 'fees';
        $this->request_type = 'GET';
        $this->location_based = true;

        return $this->_call();
    }

    /***************************************************************************
    This section contains all of the Inventory related queries.
    ***************************************************************************/

    /**
     * Provides inventory information for all of a merchant's inventory-enabled
     * item variations.
     *
     * @param int $limit A limit on results
     * @return string JSON array of zero or more InventoryEntry objects.
     */
    public function listInventory( $limit = NULL )
    {
        $this->version = 'v1';
        $this->endpoint = 'inventory';
        $this->request_type = 'GET';
        $this->location_based = true;
        $this->query = array(
            'limit' => $limit // int 250
        );

        return $this->_call();
    }

    /**
     * Provides inventory information for all of a merchant's inventory-enabled
     * item variations.
     *
     * @param string $variation_id The ID of the variation to adjust inventory
     * information for.
     * @param number $quantity The number to adjust the variation's quantity by.
     * ( -1 || +50 )
     * @param string $type The reason for the inventory adjustment.
     * ( SALE || RECEIVE_STOCK || MANUAL_ADJUST )
     * @param string $memo optional A note about the inventory adjustment.
     */
    public function updateInventory( $variation_id, $quantity, $type, $memo )
    {
        $this->version = 'v1';
        $this->endpoint = 'inventory/' . $variation_id;
        $this->request_type = 'POST';
        $this->location_based = true;
        $this->body = array(
            'quantity_delta' => $quantity,
            'adjustment_type' => $type,
            'memo' => $memo
        );

        return $this->_call();
    }



    /***************************************************************************
    This section contains all of the Item related queries.
    ***************************************************************************/

    /**
     * Provides summary information for all of a location's items.
     *
     * @return string An array of zero or more Item objects.
     */
    public function listItems()
    {
        $this->version = 'v1';
        $this->endpoint = 'items';
        $this->request_type = 'GET';
        $this->location_based = true;

        return $this->_call();
    }

    /**
     * Provides the details for a single item, including associated modifier
     * lists and fees.
     *
     * @param string $item_id The item's ID.
     */
    public function getItem( $item_id )
    {
        $this->version = 'v1';
        $this->endpoint = 'items/' . $item_id;
        $this->request_type = 'GET';
        $this->location_based = true;

        return $this->_call();
    }

    /**
     * Modifies the core details of an existing item.
     *
     * @param string $item_id The item's ID.
     * @return string An Item object that represents the updated item.
     */
    public function updateItem( $item_id )
    {
        $this->version = 'v1';
        $this->endpoint = 'items/' . $item_id;
        $this->request_type = 'PUT';
        $this->location_based = true;
        $this->body = array(
            'name' => '',
            'description' => '',
            'category_id' => '',
            'color' => '',
            'abbreviation' => '',
            'visibility' => '',
            'available_online' => '',
            'available_for_pickup' => ''
        );

        return $this->_call();
    }

    /**
     * Deletes an existing item and all item variations associated with it.
     *
     * @param string $item_id The ID of the item to delete.
     * @return string If the request succeeds, the endpoint returns a 200
     * status code and an empty JSON object, {}.
     */
    public function deleteItem( $item_id )
    {
        $this->version = 'v1';
        $this->endpoint = 'items/' . $item_id;
        $this->request_type = 'DELETE';
        $this->location_based = true;

        return $this->_call();
    }

    /**
     * Deletes an existing item and all item variations associated with it.
     *
     * @param string $item_id The ID of the item to associate the image with.
     * @param string $filepath The location of the image to upload
     * @return string An ItemImage object that represents the uploaded image.
     */
    public function updateItemImage( $item_id, $filepath )
    {
        $this->version = 'v1';
        $this->endpoint = 'items/' . $item_id . '/image';
        $this->request_type = 'POST';
        $this->location_based = true;
        $this->multipart = array(
            array(
                'name' => 'file_name',
                'contents' => fopen( $filepath, 'r' )
            )
        );

        return $this->_call();
    }




    /***************************************************************************
    This section contains all of the Location related queries.
    ***************************************************************************/

    /**
     * Provides the details for all of a business's locations.
     *
     * @return string An array of zero or more Location objects.
     */
    public function listLocations()
    {
        $this->version = 'v2';
        $this->endpoint = 'locations';
        $this->request_type = 'GET';

        return $this->_call();
    }

    /**
     * Provides the details for all of a business's locations.
     *
     * @return string A Merchant object describing the business.
     */
    public function getBusiness()
    {
        $this->version = 'v1';
        $this->endpoint = 'me';
        $this->request_type = 'GET';

        return $this->_call();
    }

    /**
     * Provides the details for all of a business's locations.
     *
     * @return string An array of Merchant objects containing profile
     * information for the business' locations.
     */
    public function listLocationsV1()
    {
        $this->version = 'v1';
        $this->endpoint = 'me/locations';
        $this->request_type = 'GET';

        return $this->_call();
    }


    /***************************************************************************
    This section contains all of the Modifier related queries.
    ***************************************************************************/

    /**
     * Lists all of a location's modifier lists.
     *
     * @return string An array of zero or more ModifierList objects.
     */
    public function listModifiers()
    {
        $this->version = 'v1';
        $this->endpoint = 'modifier-lists';
        $this->request_type = 'GET';
        $this->location_based = true;

        return $this->_call();
    }

    /**
     * Lists all of a location's modifier lists.
     *
     * @param string $modifier_list_id The modifier list's ID.
     * @return string A ModifierList object that describes the requested
     * modifier list.
     */
    public function getModifier( $modifier_list_id )
    {
        $this->version = 'v1';
        $this->endpoint = 'modifier-lists/' . $modifier_list_id;
        $this->request_type = 'GET';
        $this->location_based = true;

        return $this->_call();
    }

    /***************************************************************************
    This section contains all of the Refund related queries.
    ***************************************************************************/

    /**
     * Initiates a refund for a previously charged tender.
     *
     * @param string $transaction_id The ID of the original transaction that
     * includes the tender to refund.
     * @param string $tender_id The ID of the tender to refund.
     * @param string $reason A description of the reason for the refund.
     * @param array $amount The amount of money to refund. Sqaure Money Object
     * @return string Square Refund Object
     */
    public function createRefund( $transaction_id, $tender_id, $reason, $amount )
    {
        $this->version = 'v2';
        $this->endpoint = 'transactions/' . $transaction_id . '/refund';
        $this->request_type = 'POST';
        $this->location_based = true;
        $this->body = array(
            'idempotency_key' => uniqid(),
            'tender_id' => $tender_id,
            'reason' => $reason,
            'amount_money' => array(
                'amount' => $amount,
                'currency' => 'USD'
            )
        );

        return $this->_call();
    }


    /**
     * Lists refunds for one of a business's locations.
     *
     * @param string $begin The beginning of the requested reporting period,
     * in RFC 3339 format.
     *
     * @param string $end The end of the requested reporting period,
     * in RFC 3339 format.
     *
     * @param string $sort_order The order in which results are listed in the
     * response (ASC for oldest first, DESC for newest first).
     *
     * @param string $cursor A pagination cursor returned by a previous call to
     * this endpoint. Provide this to retrieve the next set of results for your
     * original query.
     *
     * @return string Square Refund Object and possible cursor
     */
    public function listRefunds( $begin = NULL, $end = NULL, $sort_order = 'DESC', $cursor = NULL )
    {
        $this->version = 'v2';
        $this->endpoint = 'refunds';
        $this->request_type = 'GET';
        $this->location_based = true;
        $this->body = array(
            'begin_time' => $begin,
            'end_time' => $end,
            'sort_order' => $sort_order,
            'cursor' => $cursor
        );

        return $this->_call();
    }

    /***************************************************************************
    This section contains all of the Transaction related queries.
    ***************************************************************************/

    /**
     * Lists transactions for a particular location.
     *
     * @param string $begin The beginning of the requested reporting period,
     * in RFC 3339 format.
     *
     * @param string $end The end of the requested reporting period,
     * in RFC 3339 format.
     *
     * @param string $sort_order The order in which results are listed in the
     * response (ASC for oldest first, DESC for newest first).
     *
     * @param string $cursor A pagination cursor returned by a previous call to
     * this endpoint. Provide this to retrieve the next set of results for your
     * original query.
     *
     * @return string Square Transaction Object and possible cursor
     *
     */
    public function listTransactions( $begin = NULL, $end = NULL, $sort_order = 'DESC', $cursor = NULL )
    {
        $this->version = 'v2';
        $this->endpoint = 'transactions';
        $this->request_type = 'GET';
        $this->location_based = true;
        $this->query = array(
            'begin_time' => $begin,
            'end_time' => $end,
            'sort_order' => $sort_order,
            'cursor' => $cursor
        );

        return $this->_call();
    }

    /**
     * Charges a card represented by a card nonce or a customer's card on file.
     *
     * @param string $nonce A nonce generated from the SqPaymentForm that
     * represents the card to charge.
     * @param array $amount The amount of money to charge. Square Money Object
     * @param object $customer Customer object containing at least id, email,
     * zip. Card optional.
     * @param string $note An optional note to associate with the transaction.
     * Max chars: 60
     *
     * @return string A Square Transaction object
     */
    public function charge( $nonce, $amount, $customer, $note )
    {
        $this->version = 'v2';
        $this->endpoint = 'transactions';
        $this->request_type = 'POST';
        $this->location_based = true;
        $this->body = array(
            'idempotency_key' => uniqid(),
            'billing_address' => array(
                'postal_code' => $customer->zip
            ),
            'amount_money' => array(
                'amount' => $amount,
                'currency' => 'USD'
            ),
            'buyer_email_address' => $customer->email,
            'card_nonce' => $nonce,
            // OR
            'customer_id' => $customer->id,
            'customer_card_id' => $customer->card->id


        );

        return $this->_call();
    }

    /**
     * Captures a transaction that was created with the Charge endpoint with a
     * delay_capture value of true.
     *
     * @param string $transaction_id The id of the transaction.
     * @return Empty JSON string on success
     */
    public function captureTransaction( $transaction_id )
    {
        $this->version = 'v2';
        $this->endpoint = 'transactions/' . $transaction_id . '/capture';
        $this->request_type = 'POST';
        $this->location_based = true;

        return $this->_call();
    }


    /**
     * Cancels a transaction that was created with the Charge endpoint with a
     * delay_capture value of true.
     *
     * @param string $transaction_id The id of the transaction.
     * @return Empty JSON string on success
     */
    public function voidTransaction( $transaction_id )
    {
        $this->version = 'v2';
        $this->endpoint = 'transactions/' . $transaction_id . '/void';
        $this->request_type = 'POST';
        $this->location_based = true;

        return $this->_call();
    }


    /**
     * Retrieves details for a single transaction.
     *
     * @param string $transaction_id The ID of the transaction to retrieve.
     * @return string Square Transaction Object
     */
    public function getTransaction( $transaction_id )
    {
        $this->version = 'v2';
        $this->endpoint = 'transactions/' . $transaction_id;
        $this->request_type = 'GET';
        $this->location_based = true;

        return $this->_call();
    }


}
?>
