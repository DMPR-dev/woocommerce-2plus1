<?php

namespace WC_2plus1;

class Checkers
{
	/*
		Checks if there is any free item in cart

		@param $cart - an object of cart

		@oaram $product_id - an identifier(WP_POST one) of product to be checked

		@returns BOOLEAN
	*/
	public static function isFreeItemInCart($cart,$free_product_id)
	{
		foreach($cart->cart_contents as $item)
		{
			if($item["product_id"] === $free_product_id)
			{
				if(floatval($item["data"]->price) == 0 
				|| isset($item["wc_is_free_item"]) && filter_var($item["wc_is_free_item"],FILTER_VALIDATE_BOOLEAN) === TRUE)
				{
					return true;
				}
			}
		}
		return false;
	}
}