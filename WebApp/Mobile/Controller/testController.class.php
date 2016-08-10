<?php
/**
 * 测试
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
class TestController extends BaseController{
	public function __construct(){
		parent::__construct();
		/*$this->getWechatInfo();
		if ($this->mid != 36 && $this->mid != 37)
		{
			redirect(U('Member/index'));
		}*/
	}

	public function test()
	{
		system_log('来自Linux的curl访问','来自Linux的curl访问,时间:'.date('Y-m-d H:i:s',NOW_TIME).',Ip:'.get_client_ip());
		echo '123';
	}

	public function company_pay(){
		$amount = 2.88;
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

	public function sendredpack()
	{
		$amount = 2.88;
		//加载支付类库
		Vendor('WxPayPubHelper.WxPayPubHelper');
		$wxPay = new \Common_util_pub();
		$openid = M('member')->where(array('member_id'=>$this->mid))->getField('openid');
		$info = array(
			'mch_billno' => Wx_C('wx_mch_id').date('Ymd',time()).substr(time(),1,11),
			'wxappid' => Wx_C('wx_appid'),
			'mch_id' => Wx_C('wx_mch_id'),
			'nonce_str' => $wxPay->createNoncestr(32),
			'send_name' => '通汇大商圈',
			're_openid' => $openid,
			'total_amount' => intval($amount*100),
			'total_num' => 1,
			'wishing' => '红包祝福语',
			'client_ip' => get_client_ip(),
			'act_name' => '通汇大商圈提现测试',
			'remark' => '提现备注测试',
		);
		$info['sign'] = $wxPay->getSign($info);
		$arr = $info;
		$xml = $wxPay->arrayToXml($arr);
		$url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
		$result = $wxPay->postXmlSSLCurl($xml, $url);
		$res = $wxPay->xmlToArray($result);
		p($res);
		if ($res['return_code'] === 'SUCCESS' && $res['result_code'] === 'SUCCESS')
		{
			echo 'success';
		}
	}
}