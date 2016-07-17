<?php

class Kaznachey_Kzn_Block_Redirect extends Mage_Core_Block_Template {

	protected function _construct()
	{
		$this->setTemplate('kzn/redirect.phtml');
		parent::_construct();
	}
  
	public function getRedirectForm() 
	{
		$paymentMethod = Mage::getModel('kaznachey/redirect');
		$data = $paymentMethod->getRedirectFormFields();
		$read = Mage::getSingleton("core/resource")->getConnection("core_write");
		
		$sum = 0;
		$count = 0;

		$query = "SELECT * FROM  sales_flat_order WHERE increment_id='".$data['order_id']."'";
		$flat_order = $read->query($query)->fetch();	
		$currency = $flat_order['order_currency_code'];
		
		$query = "SELECT * FROM  sales_flat_order_address WHERE entity_id='".$flat_order['billing_address_id']."'";
		$flat_order_address = $read->query($query)->fetch();
		
		$order_id = $flat_order['entity_id'];
		
		$customer_id = ($flat_order['customer_id'])?$flat_order['customer_id']:1;
		
		$paymentDetails = Array(
		"MerchantInternalPaymentId"=>$order_id,// Номер платежа в системе мерчанта
		"MerchantInternalUserId"=>$customer_id, //Номер пользователя в системе мерчанта
		"CustomMerchantInfo"=>$flat_order['customer_note'],// Любая информация
		"Currency"=>$currency,//валюта

		"PhoneNumber"=>$flat_order_address['telephone'],//телефон
		"EMail"=>$flat_order['customer_email'],
		"BuyerFirstname"=>$flat_order['customer_firstname'],//Имя,
		"BuyerLastname"=>$flat_order['customer_lastname'],//Фамилия
		"BuyerStreet"=>$flat_order_address['street'],// Адрес
		"BuyerZone"=>$flat_order_address['region'],//   Область
		"BuyerZip"=>$flat_order_address['postcode'],//  Индекс
		"BuyerCity"=>$flat_order_address['city'],//   Город,
		"BuyerCountry"=>$flat_order_address['country_id'],//Страна

		//информация о доставке
		"DeliveryFirstname"=>$flat_order['customer_firstname'],// 
		"DeliveryLastname"=>$flat_order['customer_lastname'],//
		"DeliveryZip"=>$flat_order_address['postcode'],//     
		"DeliveryCountry"=>$flat_order_address['country_id'],//   
		"DeliveryStreet"=>$flat_order_address['street'],//   
		"DeliveryCity"=>$flat_order_address['city'],//      
		"DeliveryZone"=>$flat_order_address['region'],//

		"StatusUrl"=>str_replace('index.php','kaznachey.processing.php','http://'.$_SERVER[HTTP_HOST].$_SERVER[SCRIPT_NAME]),// url состояния
		"ReturnUrl"=>str_replace('index.php','kaznachey.success.php','http://'.$_SERVER[HTTP_HOST].$_SERVER[SCRIPT_NAME]) //url возврата
		);

		$item_order_id = abs ($data['order_id']-100000000);
		$query = "SELECT * FROM  sales_flat_order_item WHERE order_id='$order_id'";
		$flat_order_items = $read->query($query);
		
		while ($res_items = $flat_order_items->fetch())
		{
			$count+=$res_items['qty_ordered'];
			$this_product['ProductItemsNum']=number_format($res_items['qty_ordered'],2,'.','');
			$this_product['ProductName']=$res_items['name'];
			$this_product['ProductId']=$res_items['product_id'];
			$this_product['ProductPrice']=number_format($res_items['row_total'],2,'.','');

			$sum+=number_format($res_items['row_total'] * $res_items['qty_ordered'],2,'.','');
			
			$query = "SELECT * FROM  catalog_product_entity_media_gallery WHERE entity_id='".$res_items['product_id']."' ORDER BY value_id ASC LIMIT 1";
			$flat_order_items_images = $read->query($query)->fetch();
			$this_product['ImageUrl']=str_replace('index.php','media/catalog/product','http://'.$_SERVER[HTTP_HOST].$_SERVER[SCRIPT_NAME]).$flat_order_items_images['value'];
			
			$products[]=$this_product;
		}
		//добавление цены доставки если есть
		if ($flat_order['base_shipping_amount']>0)
		{
			$this_product['ProductItemsNum']='1.00';
			$this_product['ProductName']='Доставка';
			$this_product['ProductId']='0';
			$this_product['ProductPrice']=number_format($flat_order['base_shipping_amount'],2,'.','');
			$this_product['ImageUrl']='';
			$products[]=$this_product;
			$sum+=number_format($flat_order['base_shipping_amount'],2,'.','');
			$count++;
		}
		
		$signature=md5($data[merch_guid].number_format($sum,2,'.','').number_format($count,2,'.','').$customer_id.$order_id.$_COOKIE['SelectedPaySystemId'].$data[merch_secret_key]);
		
		$requestMerchantInfo = array(
		"SelectedPaySystemId"=>$_COOKIE['SelectedPaySystemId'],
		"Products"=>$products,
		"PaymentDetails"=>$paymentDetails,
		"Signature"=>$signature,
		"MerchantGuid"=>$data[merch_guid]
		);

		$resMerchantPayment = json_decode($this->sendRequestKaznachey('http://payment.kaznachey.net/api/PaymentInterface/CreatePayment', json_encode($requestMerchantInfo)),true);

		//clear cart
		$cartHelper = Mage::helper('checkout/cart');
		$items = $cartHelper->getCart()->getItems();
		foreach ($items as $item) {
			$itemId = $item->getItemId();
			$cartHelper->getCart()->removeItem($itemId)->save();
		}
		
		echo base64_decode($resMerchantPayment[ExternalForm]);
		
		
	}
	
	function sendRequestKaznachey($url,$data)
	{
		$curl =curl_init();
		if (!$curl) return false;
		curl_setopt($curl, CURLOPT_URL,$url );
		curl_setopt($curl, CURLOPT_POST,true);
		curl_setopt($curl, CURLOPT_HTTPHEADER,array("Expect: ","Content-Type: application/json; charset=UTF-8",'Content-Length: '. strlen($data)));
		curl_setopt($curl, CURLOPT_POSTFIELDS,$data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,True);
		$res =  curl_exec($curl);
		curl_close($curl);
		return $res;
	}
}
