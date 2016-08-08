<?php
/**
 * 首页
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
class IndexController extends BaseController{
	public function __construct(){
		parent::__construct();
	}
	public function index(){
		redirect(U('Shop/index'));
	}

	//二维码
	public function myqrcode()
	{
		$phone = trim($_GET['phone']);
		if (empty($phone))
		{
			$where['member_id'] = $this->mid;
			$user = M('Member')->where($where)->field('mobile')->find();
			$phone = $user['mobile'];
			redirect(U('',array('phone'=>$phone)));
		}else {
			$where['mobile'] = $phone;
		}
		$user = M('Member')->where($where)->field('mobile')->find();
		$phone = $user['mobile'];
		$url = U('Login/register',array('invite_phone'=>$phone),true,true); //二维码内容
		$this->qrcode_img = qrcode($url,'');
		$this->display();
	}

	public function test(){
		$this->success('操作成功',U('Shop/index'));
		die;
		$id = intval($_GET['id']);
		if (!empty($id)) {
			$id = intval($_GET['id']);
			$count1 = M('Order')->where(array('buyer_id'=>$id,'order_type'=>4))->count();
			$count2 = M('Order')->where(array('buyer_id'=>$id,'source_id'=>$this->mid,'order_type'=>4))->count();
			if ($count1 <= 99 && $id != $this->mid && $count2<1) {
				$amount = rand(1, 3);
				$desc = '拉拢小伙伴奖励';
				$order_sn = g_order_sn();
				$data = array(
					'order_sn' => $order_sn,
					'buyer_id' => $id,
					'source_id' => $this->mid,
					'order_type' => 4,
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
				$openid = M('member')->where(array('member_id'=>$id))->getField('openid');
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
				if (!empty($res['partner_trade_no'])) {
					M('Member')->where(array('member_id'=>$this->mid))->setField('pid',$id);
					M('Order')->add($data);
				}
			}
		}
		$adv = array(
			'0' => array('title'=>'业务蜘蛛，带你装逼带你嗨','url'=>'http://mp.weixin.qq.com/s?__biz=MzA4MDg4MDI5MA==&mid=210320479&idx=1&sn=6e8fc3fa0ca0d66b9091f19ee4c6ab44&scene=18#rd'),
			'1' => array('title'=>'我、小婊砸、男神与一只蜘蛛的故事','url'=>'http://mp.weixin.qq.com/s?__biz=MzA4ODg4NDc4MA==&mid=208463983&idx=1&sn=bcb771774c5aa780ea3b35c90478d0a8#rd'),
			'2' => array('title'=>'业务员找包养神器','url'=>'http://mp.weixin.qq.com/s?__biz=MzAwMTU0Nzg1NQ==&mid=207728465&idx=1&sn=fcf4ab45a24a66f5b982872a0ab1443a&scene=18#rd'),
			'3' => array('title'=>'马云教你卖萌，看完笑翻了！','url'=>'http://mp.weixin.qq.com/s?__biz=MzA5MjgwMTY5Mw==&mid=214166252&idx=1&sn=fea359a0bbd6454f192a55fbd7b9aa09#rd'),
		);
		$key = array_rand($adv,1);
		$this->url = $adv[$key];
		$this->mid = $this->mid;
		//JS-SDK
		$signPackage = wx_js_sdk();
		$this->assign('signPackage',$signPackage);
		$type = intval($_GET['type']);
		if (!empty($type) && intval($_GET['type'])==2) {
			$this->display('index2');
		}else {
			$this->display();
		}
	}
}