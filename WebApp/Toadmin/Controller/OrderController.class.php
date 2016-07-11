<?php
/**
 * 交易
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
namespace Toadmin\Controller;
use Think\Page;
class OrderController extends GlobalController {
	public function _initialize() 
	{
        parent::_initialize();
		$this->model = D('Order');
	}	
	//管理
	public function order()
	{
		$map = array();
		$issuper = M('Admin')->where(array('admin_id'=>AID))->getField('admin_issuper');
		if (!$issuper) {
			$map['creat_admin_id'] = array('in','0,'.AID);
		}
		if(intval($_GET['order_state']))$map['order_state'] = array('eq',intval($_GET['order_state']));
		if(trim($_GET['field']) && trim($_GET['search_name']))$map[$_GET['field']] = array('like','%'.trim($_GET['search_name']).'%');
		$add_time_from = trim($_GET['add_time_from']) ? strtotime(trim($_GET['add_time_from'])) : 0;
		$add_time_to = trim($_GET['add_time_to']) ? strtotime(trim($_GET['add_time_to']))+86400 : NOW_TIME;
		$map['add_time'] = array('between',array($add_time_from,$add_time_to));
		$order_amount_from = trim($_GET['order_amount_from']) ? trim($_GET['order_amount_from']) : 0;
		$order_amount_to = trim($_GET['order_amount_to']) ? trim($_GET['order_amount_to']) : 0;
		if($order_amount_from && $order_amount_to)
		{
			$map['order_amount'] = array('between',$order_amount_from,$order_amount_to);	
		}elseif($order_amount_from && !$order_amount_to){
			$map['order_amount'] = array('egt',$order_amount_from);	
		}elseif(!$order_amount_from && $order_amount_to){
			$map['order_amount'] = array('elt',$order_amount_from);	
		}
		$totalRows = $this->model->where($map)->count();
		$page = new Page($totalRows,10);	
		$list = $this->model->where($map)->limit($page->firstRow.','.$page->listRows)->order('add_time desc')->select();				
		$this->assign('list',$list);
		$this->assign('page_show',$page->show());				
	    $this->assign('search', $_GET);							
		$this->display();
	}
	
	//查看订单	
	public function order_view()
	{
		$order_id = intval($_GET['order_id']);
		if($order_id)
		{
			$info = $this->model->where(array('order_id'=>$order_id))->relation(true)->find();
			if (is_array($info['OrderLog'])) {
				foreach ($info['OrderLog'] as $key => $vo){
					$where['order_id'] = $info['order_id'];
					$where['log_id'] = $vo['log_id'];
				}
			}
			$this->assign('info', $info);
			$this->display();
		}
	}
	//订单处理
	public function order_op()
	{
		$order_id = intval($_GET['order_id']);
		if($order_id)
		{
			$data = array();
			$data['shipping_name'] = str_rp(trim($_POST['shipping_name']));
			$data['shipping_code'] = str_rp(trim($_POST['shipping_code']));
			$data['shipping_time'] = NOW_TIME;
			$data['order_state'] = 30;
			
			$vo = $this->model->where('order_id='.$order_id)->find();
			if($vo['order_state'] == 20)
			{
				$this->model->where('order_id='.$order_id)->save($data);
				$this->success("操作成功",U('order'));  
				exit;	
			}
		
		}
	}
	//新增订单
	public function addOrder(){
		if (IS_POST) {
			$data['order_sn'] = order_sn();
			$data['out_sn'] = str_rp($_POST['out_sn'],1);
			$data['buyer_name'] = str_rp($_POST['true_name'],1);
			$data['order_type'] = 2;
			$data['payment_time'] = strtotime($_POST['payment_time']);
			$data['add_time'] = strtotime($_POST['add_time']);
			$data['discount'] = floatval($_POST['discount']);
			$data['order_amount'] = floatval($_POST['order_amount']);
			$data['order_state'] = 50;
			$data['email'] = str_rp($_POST['email'],1);
			if ($data['email']) {
				saveContact($data['email'], 'email', '订单');
				$member_id = M('Member')->where(array('email'=>$data['email']))->getField('member_id');
			}
			$data['mobile'] = str_rp($_POST['mob_phone'],1);
			if ($data['mobile']) {
				saveContact($data['mobile'], 'mobile', '订单');
				$member_id = M('Member')->where(array('mobile'=>$data['mobile']))->getField('member_id');
			}
			if ($member_id) {
				$data['member_id'] = $member_id;
			}else {
				$data['member_id'] = 0;
			}
			$data['source'] = str_rp($_POST['source'],1);
			$data['creat_admin_id'] = AID;
			$data['OrderAddress']['true_name'] = $data['buyer_name'];
			$data['OrderAddress']['prov_id'] = intval($_POST['province_id']);
			$data['OrderAddress']['city_id'] = intval($_POST['city_id']);
			$data['OrderAddress']['area_id'] = intval($_POST['area_id']);
			$data['OrderAddress']['address'] = str_rp($_POST['address'],1);
			$data['OrderAddress']['zip_code'] = intval($_POST['zip_code']);
			$data['OrderAddress']['mob_phone'] = $data['mobile'];
			$data['OrderAddress']['add_time'] = $data['add_time'];
			$data['OrderLog'][0]['order_state'] = '订单完成';
			$data['OrderLog'][0]['log_time'] = $data['add_time'];
			$data['OrderLog'][0]['state_info'] = '管理员录入订单';
			$data['OrderLog'][0]['operator'] = '管理员-'.get_admin_nickname(AID);
			foreach ($_POST['goods'] as $key => $val){
				$goods = M('Goods')->where(array('goods_id'=>$val['goods_id']))->find();
				if (!empty($goods)) {
					$data['OrderGoods'][$key]['goods_id'] = $goods['goods_id'];
					$data['OrderGoods'][$key]['goods_price'] = $val['goods_price'];
					$data['OrderGoods'][$key]['goods_mkprice'] = $val['goods_mktprice'];
					$data['OrderGoods'][$key]['goods_num'] = $val['goods_num'];
					$data['OrderGoods'][$key]['goods_name'] = $goods['goods_name'];
					$data['OrderGoods'][$key]['goods_image'] = $goods['goods_pic'];
				}
			}
			$res = $this->model->relation(true)->add($data);
			if ($res) {
				$this->success('录入订单成功',U('order'));
			}else {
				$this->error('录入订单失败');
			}
		}elseif (IS_GET){
			$province = M('District')->where(array('level'=>1,'status'=>1))->order('d_sort')->select();
			$this->assign('province',$province);
			$this->display();
		}
	}
	public function ajaxGetGoodsName(){
		$goods_name = trim($_POST['goods_name']);
		$where['goods_name'] = $goods_name;
		$info = M('Goods')->where($where)->find();
		if (!empty($info)) {
			$result['code'] = 200;
			$result['msg'] = '';
		}else {
			$result['code'] = 300;
			$result['msg'] = '没有找到相关记录.';
		}
		$result['data'] = $info;
		echo json_encode($result);
	}
	public function ajaxDeliver(){
		if (IS_AJAX){
			$order_id = intval($_POST['order_id']);
			$where['order_id'] = $order_id;
			$where['order_state'] = 20;
			$res = $this->model->where($where)->setField('order_state',30);
			if ($res) {
				$order_goods = M('OrderGoods')->where(array('order_id'=>$order_id))->select();
				foreach ($order_goods as $key => $val){
					M('Goods')->where(array('goods_id'=>$val['goods_id']))->setDec('goods_freez',$val['goods_num']);
				}
				$data['order_id'] = $order_id;
				$data['order_state'] = get_order_state_name(30);
				$data['change_state'] = get_order_state_name(40);
				$data['state_info'] = '平台已确认发货完成.等待客户收货.';
				$data['log_time'] = NOW_TIME;
				$data['operator'] = '管理员-'.AID;
				M('OrderLog')->add($data);
				$result['code'] = 200;
				$result['msg'] = '发货完成.';
				$result['data'] = array();
			}else {
				$result['code'] = 300;
				$result['msg'] = '发货失败.';
				$result['data'] = array();
			}
			echo json_encode($result);
		}
	}
	public function cancelOrder(){
		if (IS_AJAX) {
			$order_id = intval($_POST['order_id']);
			$where = array('order_id'=>$order_id);
			$order = $this->model->relation(true)->where($where)->find();
			if ($order['order_state'] == 60) {
				json_return(300,'该订单已取消,请勿重复操作.');
				die();
			}elseif ($order['order_state'] == 50) {
				json_return(300,'该订单已完成,无法进行取消订单操作.');
				die();
			}
			if ($order['order_state'] > 10) {
				M('Member')->where(array('member_id'=>$order['member_id']))->setInc('point',$order['order_amount']*MSC('point_exchange_rate'));
				$this->model->where($where)->setField('order_state',60);
			}elseif ($order['order_state'] == 10){
				$this->model->where($where)->setField('order_state',60);
			}
		}
	}
	public function deliver(){
		if (IS_POST) {
			$order_id = intval($_POST['order_id']);
			$order_data['shipping_express_id'] = intval($_POST['express']);
			$order_data['shipping_code'] = trim($_POST['express_sn']);
			M('Order')->where(array('order_id'=>$order_id))->save($order_data);
			M('Serial')->where(array('order_id'=>$order_id))->delete();
			foreach ($_POST['serial'] as $key => $val){
				$sk = explode('_', $key);
				if (!empty($val)) {
					$data['goods_id'] = $sk[1];
					$data['serial_number'] = $val;
					$data['status'] = 1;
					$data['order_id'] = $order_id;
					M('Serial')->add($data);
					unset($sk);
				}
			}
			$where['order_id'] = $order_id;
			$where['order_state'] = 20;
			$res = $this->model->where($where)->setField('order_state',30);
			if ($res) {
				$order_goods = M('OrderGoods')->where(array('order_id'=>$order_id))->select();
				foreach ($order_goods as $key => $val){
					M('Goods')->where(array('goods_id'=>$val['goods_id']))->setDec('goods_freez',$val['goods_num']);
				}
				$data['order_id'] = $order_id;
				$data['order_state'] = get_order_state_name(30);
				$data['change_state'] = get_order_state_name(40);
				$data['state_info'] = '平台已确认发货完成.等待客户收货.';
				$data['log_time'] = NOW_TIME;
				$data['operator'] = '管理员-'.AID;
				M('OrderLog')->add($data);
				$this->success('发货成功');
			}else {
				$this->success('修改序列号成功');
			}
		}elseif (IS_GET){
			$order_sn = trim($_GET['sn']);
			$where['order_sn'] = $order_sn;
			$info = D('Order')->relation(true)->where($where)->find();
			foreach ($info['OrderGoods'] as $key => $val){
				$goods_num = $val['goods_num'];
				$serial = M('Serial')->where(array('order_id'=>$info['order_id'],'goods_id'=>$val['goods_id']))->select();
				for ($goods_num;$goods_num>0;$goods_num--){
					$info['OrderGoods'][$key]['serial'][$goods_num] = $serial[$goods_num-1]['serial_number'];
				}
				unset($goods_num);
				ksort($info['OrderGoods'][$key]['serial']);
			}
			$this->express = M('Express')->where(array('e_state'=>1))->order('e_order')->select();
			$this->info = $info;
			$this->display();
		}
	}
	public function sendExpressSMS(){
		if (IS_AJAX) {
			$order_sn = $_POST['order_sn'];
			$express = intval($_POST['express']);
			$express_sn = $_POST['express_sn'];
			$express = M('Express')->where(array('id'=>$express))->getField('e_name');
			$member_id = $this->model->where(array('order_sn'=>$order_sn))->getField('member_id');
			$member = M('Member')->where(array('member_id'=>$member_id))->field('mobile,email')->find();
			$order_me = $this->model->where(array('order_sn'=>$order_sn))->field('mobile,email')->find();
			$content = '您好,订单号:'.$order_sn.'货品已寄出，'.$express.':'.$express_sn.'【佐西卡】';
			if ($member['mobile']) {
				$res = customSendSMS($member['mobile'], $content);
			}elseif ($member['email']){
				$res = sendEmail($member['email'], '您的佐西卡订单已发货', $content);
			}else {
				if ($order_me['mobile']) {
					$res = customSendSMS($order_me['mobile'], $content);
				}elseif ($order_me['email']){
					$res = sendEmail($order_me['email'], '您的佐西卡订单已发货', $content);
				}
			}
			if ($res) {
				json_return(200,'发货提醒成功.');
			}else {
				json_return(300,'发货提醒失败.');
			}
		}
	}
}