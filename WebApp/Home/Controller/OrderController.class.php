<?php
/**
 * 订单
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Home\Controller;
use Home\Controller\BaseController;
use Common\Lib\Cart\Cart;
class OrderController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->check_login();
	}
	/**
	 * 订单确认
	 */
	public function index(){
		if (IS_POST) {
			//处理获取的商品
			if (empty($_POST['goods_id'])) {
				$this->error('没有选中相关商品');
			}
			$goods_ids = $_POST['goods_id'];
			
			$goods_id_str = '';
			foreach ($goods_ids as $key => $goods_id){
				$goods_id_str .= $goods_id.',';
			}
			$goods_id_str = substr($goods_id_str, 0, -1);
			$list = D('Goods')->where(array('goods_id'=>array('IN',$goods_id_str)))->select();
			$Cart = new Cart();
			$cartList = $Cart->getList();
			$amount = 0;
			M('OrderGoods')->where(array('order_id'=>0,'member_id'=>$this->mid))->delete();
			foreach ($list as $key => $val){
				if (!empty($cartList[$val['goods_id']]['num'])) {
					if (get_distributor($this->mid)) {
						$cdisc = MSC('distributor_discount');
					}else {
						$cdisc = 1;
					}
					$list[$key]['num'] = $cartList[$val['goods_id']]['num'];
					$list[$key]['goods_price'] = $cdisc*$val['goods_price'];
					$amount += $cdisc*get_discount($cartList[$val['goods_id']]['num'])*$cartList[$val['goods_id']]['num']*$val['goods_price'];
					$order_goods['goods_id'] = $val['goods_id'];
					$order_goods['goods_name'] = $val['goods_name'];
					$order_goods['goods_price'] = $cdisc*$val['goods_price']*get_discount($cartList[$val['goods_id']]['num']);
					$order_goods['goods_mkprice'] = $val['goods_price'];
					$order_goods['goods_num'] = $cartList[$val['goods_id']]['num'];
					$order_goods['goods_image'] = $val['goods_pic'];
					$order_goods['member_id'] = $this->mid;
					M('OrderGoods')->add($order_goods);
				}else {
					unset($list[$key]);
				}
			}
			$this->list = $list;
			$this->amount = $amount;
			$point = M('Member')->where(array('member_id'=>$this->mid))->getField('point');
			$use_max_point = $amount*MSC('point_exchange_rate');
			if ($point >=$use_max_point) {
				$point = $use_max_point;
			}
			$this->point = $point;
			//收货地址
			$where['member_id'] = $this->mid;
			$address = M('MemberAddrs')->where($where)->select();
			$dwhere['upid'] = 0;
			$this->province = M('District')->where($dwhere)->order('d_sort')->select();
			$this->address = $address;
			$this->display();
		}else {
			$this->error('非法操作');
		}
	}
	/**
	 * 订单详情
	 */
	public function detail(){
		$order_sn = str_rp($_GET['sn'],1);
		$info = D('Order')->relation(true)->where(array('order_sn'=>$order_sn))->find();
		$this->assign('info',$info);
		$this->display();
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
	public function finish(){
		$sn = str_rp($_GET['sn']);
		$where['order_sn'] = $sn;
		$where['member_id'] = $this->mid;
		$where['order_state'] = 40;
		$count = M('Order')->where($where)->count();
		if ($count == 1) {
			$res = M('Order')->where($where)->setField('order_state',50);
			if ($res) {
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
		$addr_id = intval($_POST['addr_id']);
		$addr_info = M('MemberAddrs')->where(array('addr_id'=>$addr_id))->find();
		$goods_list = M('OrderGoods')->where(array('order_id'=>0,'member_id'=>$this->mid))->select();
		if (!empty($addr_info)) {
			$data['order_sn'] = order_sn();
			$data['member_id'] = $this->mid;
			$data['buyer_name'] = get_member_nickname($this->mid);
			switch (trim($_POST['pay_type'])){
				case 1:$data['payment_name'] = 'alipay';break;
				case 2:$data['payment_name'] = 'bdpay';break;
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
				$Cart->delItem($val['goods_id']);
				$goods_price = M('Goods')->where(array('goods_id'=>$val['goods_id']))->getField('goods_price');
				if (get_distributor($this->mid)) {
					$goods_price = $goods_price*MSC('distributor_discount');
				}
				$data['goods_amount'] += $goods_price*$val['goods_num'];
				$data['discount'] += $data['goods_amount']*(1-get_discount($val['goods_num']));
				//冻结库存
				M('Goods')->where(array('goods_id'=>$val['goods_id']))->setDec('goods_storage',$val['goods_num']);
				M('Goods')->where(array('goods_id'=>$val['goods_id']))->setInc('goods_freez',$val['goods_num']);
			}
			$data['order_amount'] = $data['goods_amount']-$data['discount'];
			$data['order_message'] = str_rp($_POST['order_message'],1);
			$member = M('Member')->where(array('member_id'=>$this->mid))->field('mobile,email')->find();
			$data['mobile'] = $member['mobile'];
			$data['email'] = $member['email'];
			$data['order_state'] = 10;
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
				//进行支付跳转
				switch (trim($_POST['pay_type'])){
					case 1:$this->success('订单生成成功',U('Pay/alipay',array('order_sn'=>$data['order_sn'])));break;
					case 2:$this->success('订单生成成功',U('Pay/bdpay',array('order_sn'=>$data['order_sn'])));break;
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
		$order_sn = trim($_POST['sn']);
		$where['order_sn'] = $order_sn;
		$where['member_id'] = $this->mid;
		$where['order_state'] = 10;
		$res = M('Order')->where($where)->setField('order_state',60);
		if ($res) {
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
				default:$url = U('Pay/alipay',array($field=>$sn));break;
			}
			redirect($url);
		}elseif (IS_GET){
			$type = $_GET['type'];
			$sn = str_rp($_GET['sn'],1);
			$where['member_id'] = $this->mid;
			if ($sn) {
				if ($type == 'repair') {
					$this->mod = M('Repair');
					$where['rp_sn'] = $sn;
					$where['rp_status'] = 3;
					$info['rp_sn'] = $sn;
					$info['title'] = '佐西卡维修支付';
				}else {
					$this->mod = M('Order');
					$where['order_sn'] = $sn;
					$where['order_state'] = 10;
					$info['order_sn'] = $sn;
					$info['title'] = '佐西卡购物支付';
				}
				$order = $this->mod->where($where)->find();
				if (!$order) {
					$this->error('没有找到相关订单信息.');
				}
				if ($type == 'repair') {
					$info['total_fee'] = $order['price'];
				}else {
					$info['total_fee'] = $order['order_amount'];
				}
				$info['body'] = '订单号:'.$sn;
				$this->info = $info;
				$this->display();
			}else {
				$this->error('没有找到相关订单信息.');
			}
		}
	}
	function get_serial(){
		if (IS_AJAX) {
			$goods_id = intval($_POST['goods_id']);
			$order_id = intval($_POST['order_id']);
			$where['goods_id'] = $goods_id;
			$where['order_id'] = $order_id;
			$list = M('Serial')->where($where)->select();
			json_return(200,'查询成功',$list);
		}
	}
}