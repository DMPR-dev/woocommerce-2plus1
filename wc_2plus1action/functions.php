<?php
namespace WC_2plus1;

class Functions
{
	/*
		Counts the existing free items in a cart, used in recursive adding of free items

		@param $cart - an object of cart to look in

		@returns INTEGER
	*/
	public static function countFreeItemsInCart($cart)
	{
		$counter = 0;
		foreach($cart->cart_contents as $item)
		{
			if(floatval($item["data"]->price) == 0 
			|| isset($item["wc_is_free_item"]) && filter_var($item["wc_is_free_item"],FILTER_VALIDATE_BOOLEAN) === TRUE)
			{
				$counter+=$item["quantity"];
			}
		}
		return $counter;
	}
	/*
		Removes all free items from cart (executed outside of needed pages)

		@param $cart - an object of cart to remove items from

		@returns VOID
	*/
	public static function removeFreeItemsFromCart($cart)
	{
		/*
			Loop through all items in a cart
		*/
		foreach($cart->cart_contents as $cart_key => $item)
		{
			if(floatval($item["data"]->price) == 0 
			|| isset($item["wc_is_free_item"]) && filter_var($item["wc_is_free_item"],FILTER_VALIDATE_BOOLEAN) === TRUE)
			{
				/*
					Remove free item from cart if it's duplicated
				*/
				$cart->remove_cart_item($cart_key);
				/*
					Search for other products with the same product id and increase their quantity
				*/
				$cart_id = $cart->generate_cart_id($item["data"]->get_id());
				$in_cart = $cart->find_product_in_cart( $cart_id );
				/*
					Check if product is still in cart
				*/
				if($in_cart)
				{
					$existing_object = Getters::getProduct($cart,$item["data"]->get_id());
					$cart->set_quantity($cart_id,
						intval($item["quantity"]) 
						+ intval($existing_object["quantity"]));
				}
			}
		}
	}
	/*
		Adds a unique copy of product to cart (if we have some items free and some not of the same product_id)

		@param $cart - an object of cart to add free product to

		@param $original_product - an array of fields related to the original product(we make a duplicate from it)

		@param $price - optional - a price value for new unique product(usually set to 0.00)

		@returns VOID
	*/
	public static function addAsUniqueFreeProductToCart($cart,$original_product,$price = "0.00")
	{
		/*
			Check if valid product is passed
		*/
		if(!isset($original_product["data"]))
		{
			return; 
		}
		/*
			Simplify variable name
		*/
		$op = $original_product;
		/*
			Get product_id
		*/
		$unique_product_id = $op["data"]->get_id();
		/*
			Generate unique ID for new item
		*/
		$filter = function($cart_item_data,$product_id) use ($unique_product_id)
		{
			$unique_cart_item_key = sha1( microtime() . rand() );

			if($product_id !== $unique_product_id) return $cart_item_data;

			$cart_item_data['unique_key'] = $unique_cart_item_key;

			return $cart_item_data;
		};
		/*
			Add a filter to generate unique ID for prodivded product_id
		*/
		add_filter('woocommerce_add_cart_item_data',$filter,10,2);
		/*
			Check if free item is already in a cart (anti-duplicate)
		*/
		if(!Checkers::isFreeItemInCart($cart,$unique_product_id))
		{
			$free_items_in_cart = self::countFreeItemsInCart($cart);
			/*
				Calculate quantity, we add a free product on each 3rd item in a cart
			*/
			$needed_quantity_of_free_items = floor($cart->cart_contents_count / 3) - $free_items_in_cart;

			/*	
				Decrease the original quantity of product
			*/
			remove_action("woocommerce_before_calculate_totals",array("WC_2plus1\Action","setThirdItemAsFree"),10);
			/*
				Duplicate needed quantity of item and make its duplicate free if "free items" quantity is lower than original item's quantity
			*/
			if($needed_quantity_of_free_items < $op["quantity"])
			{
				$cart->set_quantity($op["key"],intval($op["quantity"])-$needed_quantity_of_free_items);
				/*
					Add a product to cart & use variations from original product
				*/
				$cart->add_to_cart($unique_product_id,$needed_quantity_of_free_items,$op["variation_id"], $op["variation"],array(
					"wc_custom_price" => $price,
					"wc_is_free_item" => TRUE,
				));
			}
			/*
				Otherwise just set the item price to 0 and execute check one more time
			*/
			else if($needed_quantity_of_free_items > 0)
			{
				$op["data"]->set_price("0.00");
				/*
					Call it recursively until there will not be a free item on each 3rd product in a cart
				*/
				remove_filter('woocommerce_add_cart_item_data',$filter,10);
				self::addAsUniqueFreeProductToCart(
					$cart,Getters::getCheapestProduct($cart,array("return" => "object","skip_free" => TRUE)),"0.00"
				);
				add_filter('woocommerce_add_cart_item_data',$filter,10,2);
			}
			add_action("woocommerce_before_calculate_totals",array("WC_2plus1\Action","setThirdItemAsFree"),10,1);
		}
		/*
			Remove filter
		*/
		remove_filter('woocommerce_add_cart_item_data',$filter,10);
		/*
			Set newly added product price to 0
		*/
		Setters::setProductPrice($cart,$unique_product_id,"0.00",TRUE);
	}
}