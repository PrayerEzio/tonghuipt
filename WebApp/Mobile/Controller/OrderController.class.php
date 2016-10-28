<?php
/**
 * 订单
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Mobile\Controller\BaseController;
use Common\Lib\Cart\Cart;
use Think\Page;

class OrderController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->check_login();
		$this->m_info = M('Member')->where('member_id='.$this->mid)->find();
		if (empty($this->m_info['openid']))
		{
			$this->getWechatInfo();
		}
	}
	/**
	 * 订单列表
	 */
	public function index()
	{
		$search = $_GET;
		$order_type = intval($_GET['order_type']);
		$order_type ? $order_type = $order_type : $order_type = 1;
		if ($order_type)
		{
			//类型-2提现1-普通2-充值3-vip4-购买代理商
			$where['order_type'] = $order_type;
		}
		$search['order_type'] = $order_type;
		$where['visible'] = 1;
		$where['member_id'] = $this->mid;
		//$where['order_type'] = 1;
		$count = D('Order')->where($where)->count();
		$page = new Page($count,10);
		$page->rollPage = 3;
		$page->setConfig('prev','上一页');
		$page->setConfig('next','下一页');
		$page->setConfig('theme','%UP_PAGE% %LINK_PAGE% %DOWN_PAGE%');
		$list = D('Order')->relation(true)->where($where)->limit($page->firstRow.','.$page->listRows)->order('add_time desc')->select();
		switch ($order_type)
		{
			case 1:
				$temp = 'orderlist';
				break;
			case 2:
				$temp = 'orderlist-cz';
				break;
			case 4:
				foreach ($list as $key => $item)
				{
					$list[$key]['desc'] = M('AgentInfo')->where(array('agent_id'=>$item['order_param']))->getField('agent_name');
				}
				$temp = 'orderlist-dl';
				break;
			case -2:
				$temp = 'orderlist-tx';
				break;
			default:
				$temp = 'orderlist';
				break;
		}
		$this->list = $list;
		$this->page = $page->show();
		$this->search = $search;
		$this->display($temp);
	}

	/**
	 * 订单详情
	 */
	public function detail(){
		$order_sn = str_rp($_GET['sn'],1);
		$where['order_sn'] = $order_sn;
		$where['member_id'] = $this->mid;
		$info = D('Order')->relation(true)->where($where)->find();
		$this->assign('info',$info);
		$this->display();
	}

	/**
	 * 订单确认
	 */
	public function confirm(){
		if (IS_POST) {
			//处理获取的商品
			if (empty($_POST['goods_id'])) {
				$this->error('没有选中相关商品');
			}
			$goods_ids = $_POST['goods_id'];

			foreach ($goods_ids as $key => $goods_str)
			{
				$a = explode('-',$goods_str);
				$cart_array[$key]['goods_id'] = $a[0];
				$cart_array[$key]['spec_id'] = $a[1];
			}
			$Cart = new Cart();
			$cartList = $Cart->getList();
			$amount = 0;
			M('OrderGoods')->where(array('order_id'=>0,'member_id'=>$this->mid))->delete();
			foreach ($cart_array as $key => $val){
				$where['goods_id'] = $val['goods_id'];
				$where['goods_status'] = 1;
				$goods = M('Goods')->where($where)->find();
				if (!empty($cartList[$val['goods_id']][$val['spec_id']]['num']) && !empty($goods)) {
					$cart_array[$key]['Goods'] = $goods;
					if ($val['spec_id'])
					{
						$spec_where['spec_id'] = $val['spec_id'];
						$spec_where['goods_id'] = $val['goods_id'];
						$goods_spec = M('Goods')->where($spec_where)->find();
						$cart_array[$key]['GoodsSpec'] = $goods_spec;
					}else {
						$goods_spec = '';
					}
					$price = $goods['goods_price'];
					$num = $cartList[$val['goods_id']][$val['spec_id']]['num'];
					$discount = get_discount($num);
					$amount += $discount*$num*$price;
					$cart_array[$key]['num'] = $num;
					$cart_array[$key]['price'] = $price*$discount;
					$order_goods['goods_id'] = $goods['goods_id'];
					if ($val['spec_id'] && $goods_spec['spec_id'])
					{
						$order_goods['spec_id'] = $goods_spec['spec_id'];
					}
					$order_goods['goods_point'] = $goods['goods_point'];
					$order_goods['cost_point'] = $goods['cost_point'];
					$order_goods['goods_name'] = $goods['goods_name'];
					$order_goods['goods_price'] = $price*get_discount($num);
					$order_goods['goods_mkprice'] = $goods['goods_mktprice'];
					$order_goods['goods_cost'] = $goods['goods_cost'];
					$order_goods['freight'] = $goods['freight'];
					$order_goods['goods_num'] = $num;
					$order_goods['goods_image'] = $goods['goods_pic'];
					$order_goods['member_id'] = $this->mid;
					M('OrderGoods')->add($order_goods);
					unset($order_goods);
				}else {
					unset($cart_array[$key]);
				}
			}
			$this->list = $cart_array;
			$this->amount = $amount;
			//收货地址
			$where['member_id'] = $this->mid;
			$address = M('MemberAddrs')->where($where)->select();
			$dwhere['upid'] = 0;
			$this->province = M('District')->where($dwhere)->order('d_sort')->select();
			$this->address = $address;
			$this->display();
		}else {
			$this->error('非法操作',U('Index/index'));
		}
	}

	/**
	 * 确认收货
	 */
	public function receipt(){
		$sn = str_rp($_GET['sn']);
		$where['order_sn'] = $sn;
		$where['member_id'] = $this->mid;
		$where['order_state'] = 30;
		$count = M('Order')->where($where)->count();
		if ($count == 1) {
			$res = M('Order')->where($where)->setField('order_state',40);
			if ($res) {
				//$this->success('确认收货成功',$_SERVER['HTTP_REFERER']);
				$this->success('确认收货成功',U('Order/detail',array('sn'=>$sn)));
			}else {
				$this->error('确认收货失败');
			}
		}else {
			$this->error('没有找到相关订单,或者订单信息错误.');
		}
	}
	/**
	 * 完成订单
	 */
	public function finishOrder(){
		$sn = str_rp($_GET['sn']);
		$where['order_sn'] = $sn;
		$where['member_id'] = $this->mid;
		$where['order_state'] = 40;
		$count = M('Order')->where($where)->count();
		if ($count == 1) {
			$order = D('Order')->relation('OrderGoods')->where($where)->find();
			$res = M('Order')->where($where)->setField('order_state',50);
			if ($res) {
				//赠送商品积分
				$points_res = M('Member')->where(array('member_id'=>$order['member_id']))->setInc('point',$order['order_points']);
				//扣除所需积分需要在支付时扣除
				//M('Member')->where(array('member_id'=>$order['member_id']))->setDec('point',$order['cost_points']);
				//TODO:积分日志
				//订单日志
				$log_data['order_id'] = $order['order_id'];
				$log_data['order_state'] = get_order_state_name(40);
				$log_data['change_state'] = get_order_state_name(50);
				$log_data['state_info'] = '会员完成订单';
				$log_data['log_time'] = NOW_TIME;
				$log_data['operator'] = '会员';
				M('OrderLog')->add($log_data);
				//进行三级分润
				orderShareProfit($order['order_id']);
				//消息推送
				$open_id = M('Member')->where(array('member_id'=>$order['member_id']))->getField('openid');
				if ($open_id)
				{
					if ($points_res)
					{
						$data['touser'] = $open_id;
						$data['template_id'] = trim('zEB34NUf7Q1rgT1vjZeP0bQdGqHqRQqyItmQCVD_cmA');
						$data['url'] = C('SiteUrl').U('Member/index');
						$data['data']['first']['value'] = '亲，您的动态已到账！';
						$data['data']['first']['color'] = '#173177';
						$data['data']['time']['value'] = date('Y年m月d日 H:i',time());
						$data['data']['time']['color'] = '#173177';
						$data['data']['org']['value'] = '泰鑫国际';
						$data['data']['org']['color'] = '#173177';
						$data['data']['type']['value'] = '个人消费';
						$data['data']['type']['color'] = '#173177';
						$data['data']['money']['value'] = price_format($order['order_amount']).'元';
						$data['data']['money']['color'] = '#173177';
						$data['data']['point']['value'] = $order['order_points'].'动态';
						$data['data']['point']['color'] = '#173177';
						$data['data']['remark']['value'] = '如有疑问，请联系客服894916947。';
						$data['data']['remark']['color'] = '#173177';
						sendTemplateMsg($data);
					}
					$data['touser'] = $open_id;
					$data['template_id'] = trim('YpV6rl7TZz-dULxA2QgBlTZwXjF_FY4UztGoNMbd4rU');
					$data['url'] = C('SiteUrl').U('Order/index');
					$data['data']['first']['value'] = '您的订单:'.$order['order_sn'].'已完成.';
					$data['data']['first']['color'] = '#173177';
					$data['data']['orderno']['value'] = $order['order_sn'];
					$data['data']['orderno']['color'] = '#173177';
					$data['data']['refundno']['value'] = 1;
					$data['data']['refundno']['color'] = '#173177';
					$data['data']['refundproduct']['value'] = price_format($order['order_amount']).'元';
					$data['data']['refundproduct']['color'] = '#173177';
					$data['data']['remark']['value'] = '如有疑问，请联系客服894916947。';
					$data['data']['remark']['color'] = '#173177';
					sendTemplateMsg($data);
				}
				$this->success('完成订单成功',U('Order/detail',array('sn'=>$sn)));
			}else {
				$this->error('完成订单失败');
			}
		}else {
			$this->error('没有找到相关订单,或者订单信息错误.');
		}
	}
	/**
	 * 生成订单
	 */
	public function creatOrder(){
		/*$agent_id = M('Member')->where(array('member_id'=>$this->mid))->getField('agent_id');
		if (!$agent_id)
		{
			$this->error('您还不是本网站的经销商,没有购买权限.');
		}*/
		//$addr_id = intval($_POST['addr_id']);
		//$addr_info = M('MemberAddrs')->where(array('addr_id'=>$addr_id))->find();
		$addr_info['name'] = trim($_POST['name']);
		$addr_info['province_id'] = 19;//intval($_POST['province']);
		$addr_info['city_id'] = 291;//intval($_POST['city']);
		$addr_info['area_id'] = 3572;//intval($_POST['area']);
		$addr_info['mobile'] = trim($_POST['mobile']);
		$addr_info['addr'] = trim($_POST['address']);
		foreach ($addr_info as $check_info)
		{
			if (empty($check_info))
			{
				$this->error('请完整填写收货信息.');die;
			}
		}
		$goods_list = M('OrderGoods')->where(array('order_id'=>0,'member_id'=>$this->mid))->select();
		if (!empty($addr_info)) {
			$data['order_sn'] = order_sn();
			$data['member_id'] = $this->mid;
			$data['buyer_name'] = get_member_nickname($this->mid);
			switch (trim($_POST['pay_type'])){
				case 1:$data['payment_name'] = 'alipay';break;
				case 2:$data['payment_name'] = 'bdpay';break;
				case 3:$data['payment_name'] = 'wxpay';break;
				default : $data['payment_name'] = 'undefine';break;
			}
			$data['shipping_fee'] = 0;
			$data['goods_amount'] = 0;
			$data['discount'] = 0;
			$data['order_amount'] = 0;
			if (empty($goods_list)) {
				$this->error('您还没有选择好商品哦.',U('Cart/index'));
			}
			foreach ($goods_list as $key => $val){
				//计算价格同时清除购物车里的商品
				$Cart = new Cart();
				$Cart->delItem($val['goods_id'],$val['spec_id']);
				$goods = M('Goods')->where(array('goods_id'=>$val['goods_id']))->find();
				$goods_price = $goods['goods_price'];
				if (get_distributor($this->mid)) {
					$goods_price = $goods_price*MSC('distributor_discount');
				}
				$data['goods_amount'] += $goods_price*$val['goods_num'];
				$data['shipping_fee'] += $val['freight'];
				$data['discount'] += $data['goods_amount']*(1-get_discount($val['goods_num']));
				if ($goods['goods_storage'] < $val['goods_num'])
				{
					$this->error('抱歉,订单商品库存已不足,无法生成订单.');
				}
			}
			$order_points = 0;
			$cost_points = 0;
			foreach ($goods_list as $key => $val){
				$order_points += $val['goods_point']*$val['goods_num'];
				$cost_points += $val['cost_point']*$val['goods_num'];
				M('Goods')->where(array('goods_id'=>$val['goods_id']))->setDec('goods_storage',$val['goods_num']);
				M('Goods')->where(array('goods_id'=>$val['goods_id']))->setInc('goods_freez',$val['goods_num']);
			}
			if ($cost_points)
			{
				$member_point = M('Member')->where(array('member_id'=>$this->mid,'member_status'=>1))->getField('point');
				if ($member_point < $cost_points)
				{
					$this->error('抱歉,您剩余的动态无法完成支付,订单生成失败.');
				}else {
					$res = M('Member')->where(array('member_id'=>$this->mid))->setDec('point',$cost_points);
					if (!$res)
					{
						$this->error('因动态支付失败,订单生成失败.');
					}else {
						$member = M('Member')->where(array('member_id'=>$this->mid))->find();
						if ($member['openid'])
						{
							$data['touser'] = $member['openid'];
							$data['template_id'] = trim('C-ODq44vKBM88QaKAoXdeTF_bJ3dkqkrFqprjVTiDK0');
							$data['url'] = C('SiteUrl').U('Order/index');
							$data['data']['first']['value'] = get_member_nickname($this->mid).',您好:\n您的账号动态最新交易信息';
							$data['data']['first']['color'] = '#173177';
							$data['data']['time']['value'] = date('Y年m月d日 H:i',time());
							$data['data']['time']['color'] = '#173177';
							$data['data']['type']['value'] = '减少';
							$data['data']['type']['color'] = '#173177';
							$data['data']['Point']['value'] = $cost_points;
							$data['data']['Point']['color'] = '#173177';
							$data['data']['From']['value'] = '泰鑫国际积分商城';
							$data['data']['From']['color'] = '#173177';
							$data['data']['remark']['value'] = '截止'.date('Y年m月d日 H:i',time()).'，您的可用动态为'.$member['point'].'积分。如有疑问请咨询微信TH09241121';
							$data['data']['remark']['color'] = '#173177';
							sendTemplateMsg($data);
						}
					}
				}
			}
			$data['order_points'] = $order_points;
			$data['cost_points'] = $cost_points;
			$data['order_amount'] = $data['goods_amount']-$data['discount']+$data['shipping_fee'];
			$data['order_message'] = str_rp($_POST['order_message'],1);
			$member = M('Member')->where(array('member_id'=>$this->mid))->field('mobile,email')->find();
			$data['mobile'] = $member['mobile'];
			$data['email'] = $member['email'];
			$data['order_state'] = 10;
			$data['order_type'] = 1;
			$data['payment_id'] = 4;
			$data['payment_name'] = '微信支付';
			$data['add_time'] = NOW_TIME;
			$order_id = M('Order')->add($data);
			if ($order_id) {
				//认领订单商品 已从购物车页面写入
				M('OrderGoods')->where(array('order_id'=>0,'member_id'=>$this->mid))->setField('order_id',$order_id);
				//生成物流地址
				$address_data['order_id'] = $order_id;
				$address_data['buyer_id'] = $this->mid;
				$address_data['true_name'] = $addr_info['name'];
				$address_data['prov_id'] = $addr_info['province_id'];
				$address_data['city_id'] = $addr_info['city_id'];
				$address_data['area_id'] = $addr_info['area_id'];
				$address_data['address'] = $addr_info['addr'];
				$address_data['zip_code'] = $addr_info['zip'];
				$address_data['mob_phone'] = $addr_info['mobile'];
				$address_data['add_time'] = NOW_TIME;
				M('OrderAddress')->add($address_data);
				//订单日志
				$log_data['order_id'] = $order_id;
				$log_data['order_state'] = get_order_state_name(10);
				$log_data['change_state'] = get_order_state_name(20);
				$log_data['state_info'] = '会员确认订单';
				$log_data['log_time'] = NOW_TIME;
				$log_data['operator'] = '会员';
				M('OrderLog')->add($log_data);
				if ($data['order_amount'] > 0)
				{
					//进行支付跳转
					switch (trim($_POST['pay_type'])){
						case 0:$this->success('订单生成成功',U('Pay/predepositpay',array('order_sn'=>$data['order_sn'])));break;
						case 1:$this->success('订单生成成功',U('Pay/alipay',array('order_sn'=>$data['order_sn'])));break;
						case 2:$this->success('订单生成成功',U('Pay/bdpay',array('order_sn'=>$data['order_sn'])));break;
						case 3:$this->success('订单生成成功',U('Pay/wxpay',array('order_sn'=>$data['order_sn'])));break;
						//case 10:$this->success('订单生成成功',U('Pay/point',array('order_sn'=>$data['order_sn'])));break;
						default :$this->success('订单生成成功',U('Pay/wxpay',array('order_sn'=>$data['order_sn'])));break;
					}
				}else {
					$this->success('订单生成成功',U('Pay/point',array('order_sn'=>$data['order_sn'])));
				}
			}
		}else {
			$this->error('请选择收货地址');
		}
	}
	/**
	 * 取消订单
	 */
	public function cancelOrder(){
		$order_sn = trim($_GET['sn']);
		$where['order_sn'] = $order_sn;
		$where['member_id'] = $this->mid;
		$where['order_state'] = 10;
		$res = M('Order')->where($where)->setField('order_state',60);
		if ($res) {
			//返还预先扣除的积分
			$order = M('Order')->where($where)->find();
			if ($order['cost_points'])
			{
				$point_res = M('Member')->where(array('member_id'=>$order['member_id']))->setInc('point',$order['cost_points']);
				if (!$point_res)
				{
					$this->error('动态返还失败,未能成功取消订单.');
				}
			}
			//解冻库存
			$order_id = M('Order')->where(array('order_sn'=>$order_sn))->getField('order_id');
			$order_goods = M('OrderGoods')->where(array('order_id'=>$order_id))->select();
			foreach ($order_goods as $key => $val){
				M('Goods')->where(array('goods_id'=>$val['goods_id']))->setInc('goods_storage',$val['goods_num']);
				M('Goods')->where(array('goods_id'=>$val['goods_id']))->setDec('goods_freez',$val['goods_num']);
			}
			//订单日志
			$log_data['order_id'] = $order_id;
			$log_data['order_state'] = get_order_state_name(60);
			$log_data['change_state'] = '无';
			$log_data['state_info'] = '会员取消订单';
			$log_data['log_time'] = NOW_TIME;
			$log_data['operator'] = '会员';
			M('OrderLog')->add($log_data);
			$this->success('取消订单成功');
		}else {
			$this->error('取消订单失败');
		}
	}
	/**
	 * 选择支付方式
	 */
	public function pay(){
		if (IS_POST) {
			$pay_type = intval($_POST['pay_type']);
			$order_sn = trim($_POST['order_sn']);
			$rp_sn = trim($_POST['rp_sn']);
			if ($order_sn && empty($rp_sn)) {
				$field = 'order_sn';
				$sn = $order_sn;
			}elseif (empty($order_sn) && $rp_sn){
				$field = 'rp_sn';
				$sn = $rp_sn;
			}
			switch ($pay_type){
				case 1:$url = U('Pay/alipay',array($field=>$sn));break;
				case 2:$url = U('Pay/bdpay',array($field=>$sn));break;
				case 3:$url = U('Pay/wxpay',array($field=>$sn));break;
				default:$url = U('Pay/alipay',array($field=>$sn));break;
			}
			redirect($url);
		}elseif (IS_GET){
			$sn = str_rp($_GET['sn'],1);
			$where['member_id'] = $this->mid;
			if ($sn) {
				$this->mod = M('Order');
				$where['order_sn'] = $sn;
				$where['order_state'] = 10;
				$info['order_sn'] = $sn;
				$info['title'] = '泰鑫国际平台支付';
				$order = $this->mod->where($where)->find();
				if (!$order) {
					$this->error('没有找到相关订单信息.');
				}else {
					$this->success('正在跳转微信支付页面.',U('Pay/wxpay',array('order_sn'=>$order['order_sn'])));
				}
				/*if ($type == 'repair') {
					$info['total_fee'] = $order['price'];
				}else {
					$info['total_fee'] = $order['order_amount'];
				}
				$info['body'] = '订单号:'.$sn;
				$this->info = $info;
				$this->success();*/
			}else {
				$this->error('没有找到相关订单信息.');
			}
		}
	}

	public function deleteOrder()
	{
		//非完全删除订单 而是订单设置为会员不可见
		$order_sn = trim($_GET['sn']);
		$where['order_sn'] = $order_sn;
		$where['member_id'] = $this->mid;
		$res = M('Order')->where($where)->setField('visible',0);
		if ($res) {
			//解冻库存
			$order_id = M('Order')->where($where)->getField('order_id');
			//订单日志
			$log_data['order_id'] = $order_id;
			$log_data['order_state'] = get_order_state_name(-10);
			$log_data['change_state'] = '无';
			$log_data['state_info'] = '会员删除订单[设置不可见]';
			$log_data['log_time'] = NOW_TIME;
			$log_data['operator'] = '会员';
			M('OrderLog')->add($log_data);
			$this->success('删除订单成功');
		}else {
			$this->error('删除订单失败');
		}
	}
}