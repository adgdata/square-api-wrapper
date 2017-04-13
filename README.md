# Square API Wrapper
A Wrapper in PHP built to wrap both v1 and v2 of the Square API

## Usage

```
<?php
require 'Square.php';

$square = new Square();
echo $square->listLocations();
?>
```

### Calls
- listCategories(); 
- createCheckout( $order, $user_email );
- createCustomer( $customer );
- listCustomers();
- updateCustomer( $customer );
- getCustomer( $customer );
- deleteCustomer( $customer );
- addCardToCustomer( $customer );
- deleteCardFromCustomer( $customer, $card );
- listDiscounts(); 
- listFees(); 
- listInventory(); 
- updateInventory( $variation_id, $quantity, $type, $memo );
- listItems(); 
- getItem( $item_id );
- updateItem( $item_id );
- deleteItem( $item_id );
- updateItemImage( $item_id, $filepath );
- listLocations(); 
- listLocationsV1(); 
- getBusiness(); 
- listModifiers(); 
- getModifier( $modifier_list_id );
- createRefund( $transaction_id, $tender_id, $reason, $amount );
- listRefunds(); 
- listTransactions( $gegin, $end, $sort_order, $cursor ); 
- charge( $nonce, $amount, $customer, $note );
- captureTransaction( $transaction_id );
- voidTransaction( $transaction_id );
- getTransaction( $transaction_id ); 
