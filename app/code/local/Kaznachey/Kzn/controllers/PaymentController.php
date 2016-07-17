<?php

class Kaznachey_Kzn_PaymentController extends Mage_Core_Controller_Front_Action
{
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
		$session->setKaznacheyQuoteId($session->getQuoteId());
        $this->getResponse()->setBody($this->getLayout()->createBlock('kaznachey/redirect')->toHtml());
        $session->unsQuoteId();
        $session->unsRedirectUrl();
    }

    public function failAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getKaznacheyQuoteId());
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();
            }

        }

        $quote = Mage::getModel('sales/quote')->load($session->getKaznacheyQuoteId());
        if ($quote->getId()) {
            $quote->setActive(true);
            $quote->save();
        }
        $session->addError(Mage::helper('kaznachey')->__('Payment failed. Pleas try again later.'));
        $this->_redirect('checkout/cart');
    }

    public function returnAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getKaznacheyQuoteId());
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
        $this->_redirect('checkout/onepage/success', array('_secure'=>true));
    }

    public function notifyAction()
    {
        $request = Mage::app()->getRequest()->getPost();

        $paymentMethod = Mage::getModel('kaznachey/redirect');
        if (!$paymentMethod->validateRequest($request)) {
            return;
        }

        $order = Mage::getModel('sales/order')->loadByIncrementId($request['InvId']);
        $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
        $order->setStatus('processing');
        $order->setIsNotified(false);
        $order->save();
        echo 'OK' . $request['InvId'];
    }
}