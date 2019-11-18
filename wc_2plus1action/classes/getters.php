<?php
namespace WC_2plus1;

class Getters
{
	/*
		Gets the id or object of the cheapest product on cart

		@param $cart - an object of cart

		@args - an array of arguments passed to method

		@returns INTEGER | ARRAY | BOOLEAN
	*/
	public static function getCheapestProduct($cart,$args = array("return" => "id", "skip_free" => FALSE))
	{
		if(!isset($args["return"]))
		{
			return FALSE;
		}
		$skip_free = isset($args["skip_free"]) ? filter_var($args["skip_free"],FILTER_VALIDATE_BOOLEAN) : FALSE;
		/*
			Declare variables
		*/
		$cheapest_id = -1;
		$cheapest_object = null;
		$cheapest_price = PHP_INT_MAX;

		/*
			Loop through all cart items
		*/
		foreach($cart->cart_contents as $item)
		{
			/*
				Detect the the cheapest item on current iteration and assign variables
			*/
			if(floatval($item["data"]->price) < $cheapest_price)
			{
				if($skip_free && floatval($item["data"]->price) == 0) continue;

				$cheapest_price = floatval($item["data"]->price);
				$cheapest_id = $item["product_id"];
				$cheapest_object = $item;
			}
		}
		/*
			Return identifier(product_id) of the cheapest item on cart
		*/
		if(strtolower($args["return"]) === "id")
		{
			return $cheapest_id;
		}
		/*
			Return the cheapest item object(an array of fields) on cart
		*/
		if(strtolower($args["return"]) === "object")
		{
			return $cheapest_object;
		}
		return FALSE;
	}
	/*
		Gets the item object(an array of fields) on cart by provided ID

		@param $cart - an object of cart

		@param $product_id - an identifier(WP_POST one) of product to be accessed

		@returns NULL | ARRAY
	*/
	public static function getProduct($cart,$product_id)
	{
		foreach($cart->cart_contents as $item)
		{
			if($item["data"]->get_id() === $product_id)
			{
				return $item;
			}
		}
		return null;
	}
	/*
		Gets the overall quantity of product in cart, i.e it doesn't mean if product placed into cart with unique key or not

		@param $cart - an object of cart

		@oaram $product_id - an identifier(WP_POST one) of product to be checked

		@returns INTEGER
	*/
	public static function getQuantityOfProduct($cart,$product_id)
	{
		$quantity = 0;
		foreach($cart->cart_contents as $item)
		{
			if($item["product_id"] === $product_id)
			{
				$quantity+=intval($item["quantity"]);
			}
		}
		return $quantity;
	}
}