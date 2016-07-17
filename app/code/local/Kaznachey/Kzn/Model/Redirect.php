<?php

class Kaznachey_Kzn_Model_Redirect extends Mage_Payment_Model_Method_Abstract {

	protected $_code = 'kaznachey_redirect';
	protected $_formBlockType = 'kaznachey/message';
	  
	protected $_canUseForMultishipping = false;
	protected $_canUseInternal = false;
	protected $_isInitializeNeeded = true;
  
	public function initialize($paymentAction, $stateObject)
	{
		$stateObject->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT);
		$stateObject->setStatus('pending_payment');
		$stateObject->setIsNotified(false);
		$stateObject->save();
	}
  
	public function get_shop_config() {
		$result['merch_guid'] = $this->getConfigData('merch_guid');
		$result['merch_secret_key'] = $this->getConfigData('merch_secret_key');
		return $result;
	}
  
	public function getOrderPlaceRedirectUrl()
	{
		return Mage::getUrl('kaznachey/payment/redirect', array('_secure' => true));
	}
  
	public function getRedirectFormFields() 
	{
		$result = array();
		$session = Mage::getSingleton('checkout/session');
		$order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
	
		$order_id = $order->getRealOrderId();
		$result['order_id'] = $order_id;
		
		$result['merch_guid'] = $this->getConfigData('merch_guid');
		$result['merch_secret_key'] = $this->getConfigData('merch_secret_key');
		$result['merch_redirect_page'] = $this->getConfigData('merch_redirect_page');
		$result['merch_order_status'] = $this->getConfigData('merch_order_status');
		$result['pay_system'] = '1';
		
		return $result;
	}
}