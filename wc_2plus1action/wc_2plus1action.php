<?php
/*
Plugin Name: WooCommerce 2+1 Action
Description: Purchase 2, get 1 free! This plugin provides an ability to make an action "2+1", each 3rd item on WooCommerce cart produces one free item(the cheapest one on the cart).
Author: Dmytro Proskurin
Version: 0.0.1
Author URI: http://github.com/DMPR-dev
*/
namespace WC_2plus1;

/*
	Included additional classes
*/
require_once plugin_dir_path(__FILE__) . '/classes/checkers.php';
require_once plugin_dir_path(__FILE__) . '/classes/getters.php';
require_once plugin_dir_path(__FILE__) . '/classes/setters.php';
require_once plugin_dir_path(__FILE__) . '/functions.php';

/*
	This class provides an ability to make an action "2+1", each 3rd item on Woocommerce cart produces one free item(the cheapest one on the cart)
*/
class Action
{
	/*
		Constructor
	*/
	public function __construct()
	{
		$this->initHooks();
	}
	/*
		Inits needed hook(s)
	*/
	protected function initHooks()
	{
		add_action('woocommerce_before_calculate_totals',array($this,"setThirdItemAsFree"),10,1);
	}
	/*
		This method is attached to 'woocommerce_before_calculate_totals' hook

		@param $cart - an object of current cart that is being processed

		@returns VOID
	*/
	public function setThirdItemAsFree($cart)
	{
		/*
			Keep free items refreshed everytime cart gets updated
		*/
		$this->cleanCart($cart);
		if ($cart->cart_contents_count > 2)
		{
			/*
				Detect the cheapest product on cart
			*/
			$cheapest_product_id = Getters::getCheapestProduct($cart,array("return" => "ID"));

			if($cheapest_product_id === FALSE || intval($cheapest_product_id) < 0) return;
			/*
				Make one of the cheapest items free 

				@returns the original item quantity decreased on 1
			*/
			$this->addFreeItem($cart);
		}
	}
	/*
		Prevents recursion because adding a new item to cart will cause hook call

		@param $cart - an object of cart

		@returns VOID
	*/
	protected function addFreeItem($cart)
	{
		remove_action('woocommerce_before_calculate_totals',array($this,"setThirdItemAsFree"));
		Functions::addAsUniqueFreeProductToCart($cart,Getters::getCheapestProduct($cart,array("return" => "object")),"0.00");
		add_action('woocommerce_before_calculate_totals',array($this,"setThirdItemAsFree"),10,1);
	}
	/*
		Prevents recursion because removing an item from cart will cause hook call

		@param $cart - an object of cart

		@returns VOID
	*/
	protected function cleanCart($cart)
	{
		remove_action('woocommerce_before_calculate_totals',array($this,"setThirdItemAsFree"));
		Functions::removeFreeItemsFromCart($cart);
		add_action('woocommerce_before_calculate_totals',array($this,"setThirdItemAsFree"),10,1);
	}
}

new Action();

