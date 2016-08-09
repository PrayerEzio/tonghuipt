<?php
/**
 * 测试
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
class testController extends BaseController{
	public function __construct(){
		parent::__construct();
		if ($this->mid == 36 || $this->mid == 37)
		{
			$amount = 101;
		}else {
			$this->error('请不要非法操作');die;
		}
	}

	public function company_pay(){
		if ($this->mid == 36 || $this->mid == 37)
		{
			$amount = 101;
		}else {
			$this->error('请不要非法操作');die;
		}
		$desc = '企业付款测试';
		$order_sn = order_sn('Test');
		$data = array(
			'order_sn' => $order_sn,
			'buyer_id' => $this->mid,
			'source_id' => $this->mid,
			'order_type' => -2,
			'payment_name' => '微信企业付款',
			'order_amount' => $amount/100,
			'goods_amount' => $amount/100,
			'order_state' => 60,
			'shipping_time' => NOW_TIME,
			'payment_time' => NOW_TIME,
			'add_time' => NOW_TIME,
		);
		//加载支付类库
		Vendor('WxPayPubHelper.WxPayPubHelper');
		$wxPay = new \Common_util_pub();
		$openid = M('member')->where(array('member_id'=>$this->mid))->getField('openid');
		$info = array(
			'mch_appid' => Wx_C('wx_appid'),
			'mchid' => Wx_C('wx_mch_id'),
			'nonce_str' => $wxPay->createNoncestr(32),
			'partner_trade_no' => $order_sn,
			'openid' => $openid,
			'check_name' => 'NO_CHECK',
			'amount' => $amount,
			'desc' => $desc,
			'spbill_create_ip' => get_client_ip(),
		);
		$info['sign'] = $wxPay->getSign($info);
		$arr = $info;
		$xml = $wxPay->arrayToXml($arr);
		$url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
		$result = $wxPay->postXmlSSLCurl($xml, $url);
		$res = $wxPay->xmlToArray($result);
		p($res);
		if (!empty($res['partner_trade_no'])) {
			M('Order')->add($data);
		}
	}
}