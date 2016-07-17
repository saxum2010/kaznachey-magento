<?php
class Kaznachey_Kzn_Block_Message extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $this->setTemplate('kzn/message.phtml');
        parent::_construct();
    }
	
	public function getForm()
	{
		$paymentMethod = Mage::getModel('kaznachey/redirect');
		
		$conf['merch_guid'] = $paymentMethod->getConfigData('merch_guid');
		$conf['merch_secret_key'] = $paymentMethod->getConfigData('merch_secret_key');

		return $conf;
	}
}