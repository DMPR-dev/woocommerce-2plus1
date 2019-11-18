<?php

namespace WC_2plus1;

class Setters
{
	/*
		Sets the product price in a cart, usually used for setting price to 0.00

		@param $cart - an object of cart

		@param $product_id - an idenfier of product that is being updated

		@param $price - optional - new price for prodvided product in a cart

		@prarm $custom_price_check - optional - a flag to determine if we need to set price from "wc_custom_price" field

		@returns VOID
	*/
	public static function setProductPrice($cart,$product_id,$price = "0.00", $custom_price_check = FALSE)
	{
		/*
			Loop through all items in cart
		*/
		foreach($cart->cart_contents as $item)
		{
			/*
				Check if wc_custom_price and flag $custom_price_check are set and set new item price based 
				on the wc_custom_price field
			*/
			if(isset($item["wc_custom_price"]) && $custom_price_check === TRUE) 
			{
				$price = $item["wc_custom_price"];
				if($item["product_id"] === $product_id)
				{
					$item["data"]->set_price($price);
				}
			}
			/*
				Otherwise, if flag and wc_custom_price field are not set, then set price from variable @price, 
			*/
			else if(!isset($item["wc_custom_price"]) && $custom_price_check === FALSE)
			{
				if($item["product_id"] === $product_id)
				{
					$item["data"]->set_price($price);
				}
			}
		}
	}
}