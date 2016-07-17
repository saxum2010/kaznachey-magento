<?
header("Content-Type: text/html; charset=utf-8");

require_once('app/Mage.php');
umask(0);
Mage::app();

$HTTP_RAW_POST_DATA = @$HTTP_RAW_POST_DATA ? $HTTP_RAW_POST_DATA : file_get_contents('php://input');
$hrpd = json_decode($HTTP_RAW_POST_DATA);
$callback_array = (array)$hrpd;

$c_user_id = $callback_array['MerchantInternalUserId'];
$c_order_id = $callback_array['MerchantInternalPaymentId'];
$c_signature = $callback_array['Signature'];
$c_sum = $callback_array['Sum']/97*100;
$sum = 0;

$read = Mage::getSingleton("core/resource")->getConnection("core_write");

$query = "SELECT * FROM  sales_flat_order WHERE increment_id='$c_order_id'";
$flat_order = $read->query($query)->fetch();

$paymentMethod = Mage::getModel('kaznachey/redirect');
$data = $paymentMethod->getRedirectFormFields();

$customer_id = ($flat_order['customer_id'])?$flat_order['customer_id']:1;

$c_sign=md5($callback_array['ErrorCode'].$c_order_id.$customer_id.number_format($callback_array['Sum'], 2, '.', '').$flat_order['customer_note'].$data[merch_secret_key]);

if ($c_sign == $c_signature AND $customer_id == $c_user_id)
{
	Mage::log("Sig is ok");
/* 	
	$order = Mage::getModel('sales/order')->loadByIncrementId($c_order_id);
	$payment = $order->getPayment();
	$payment->registerCaptureNotification($callback_array['Sum']);
	$order->save();
	$order->sendNewOrderEmail()->addStatusHistoryComment()->setIsCustomerNotified(true)->save();
	*/
	$query = "UPDATE sales_flat_order SET status='".$data['merch_order_status']."' WHERE entity_id='$c_order_id'";
	$flat_order_items = $read->query($query);
}
else
{
	Mage::log("Sig is failed");
}

/* function wtf($text)
{
	$file = 'wtf.txt';
	$current = file_get_contents($file);
	$current .= ' '.$text;
	file_put_contents($file, $current);
} */

die();
?>
