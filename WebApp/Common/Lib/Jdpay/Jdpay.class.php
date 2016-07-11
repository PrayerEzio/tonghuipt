<?php
namespace Common\Lib\Jdpay;
use Org\Net\Http;
/**
* 京东支付接口集成
*/
class Jdpay
{
	private $jdpay_gateway = 'https://m.jdpay.com/wepay/web/pay';
	private $version = '2.0';
	public function __construct(){
		$this->Jdpay_config = unserialize(M('Payment')->where(array('payment_code'=>'Jdpay'))->getField('payment_config'));
		$this->successCallbackUrl_suffix = '?token=xxxx&tradeNum=xxxx';
	}
	/**
	 * 到京东付款
	 * @param  [array] $para [商品请求参数]
	 * @return []       []
	 */
	public function toJdpay($para)
	{
		$para['version'] = $this->version;
		$para['merchantNum'] = $this->alipay_config['alipay_account'];
		$para['tradeTime'] = NOW_TIME;
		$para['tradeAmount'] = $para['tradeAmount']*100;
		$para['currency'] = 'CNY';
		if (empty($para['token'])) {
			$para['token'] = '';
		}
		if (empty($para['successCallbackUrl'])) {
			$para['successCallbackUrl'] = U('Home/Pay/alipayNotify', '', true, true).$this->successCallbackUrl_suffix;
		}
		if (empty($para['failCallbackUrl'])) {
			$para['failCallbackUrl'] = U('Home/Pay/alipayReturn', '', true, true);
		}
		if (empty($para['notifyUrl'])) {
			$para['notifyUrl'] = U('Home/Pay/alipayReturn', '', true, true);
		}
		$Sign = new SignUtil();
		$para['merchantSign'] = $Sign->sign($para);
		return $para;
	}
	/**
	 * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
	 * @param $para 需要拼接的数组
	 * return 拼接完成以后的字符串
	 */
	private function createLinkstring($para) {
		$arg  = "";
		foreach ($para as $key => $val) {
			$arg .= $key."=".$val."&";
		}
		//去掉最后一个&字符
		$arg = substr($arg,0,count($arg)-2);
		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
		return $arg;
	}
}