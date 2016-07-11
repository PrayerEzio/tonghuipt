<?php
/**
 * 支付宝支付
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Home\Controller;
use Common\Lib\Alipay\Alipay;
use Think\Controller;
class PayController extends Controller{
	public function __construct(){
		parent::__construct();
		$this->mod = D('Order');
		$this->mid = session('member_id');
	}
	public function alipay(){
		$order_sn = trim($_GET['order_sn']);
		if ($order_sn) {
			$member_id = $this->mid;
			$where['order_sn'] = $order_sn;
			$where['member_id'] = $member_id;
			$order = $this->mod->where($where)->find();
			if (is_array($order) && !empty($order)) {
				if ($order['order_state'] != 10) {
					$this->error('该订单号无法进行支付,请联系客服.');
				}else {
					$alipay_data['total_fee'] = $order['order_amount'];//订单总金额
					$alipay_data['out_trade_no'] = $order['order_sn'];//商户订单ID
					$alipay_data['subject'] = '佐西卡购物支付';//订单商品标题
					$alipay_data['body'] = '订单号:'.$order['order_sn'];//订单商品描述
					$alipay_data['show_url'] = 'http://'.$_SERVER['SERVER_NAME'].U('Member/order',array('sn'=>$order['order_sn']));//订单商品地址
					$alipay_data['notify_url'] = U('Home/Pay/alipayNotify', '', true, true);
					$alipay_data['return_url'] = U('Home/Pay/alipayReturn', '', true, true);
					$alipay = new Alipay();
					$alipay->toAlipay($alipay_data);
				}
			}else {
				$this->error('非法操作');
			}
		}elseif (trim($_GET['rp_sn'])) {
			$rp_sn = trim($_GET['rp_sn']);
			$member_id = $this->mid;
			$where['rp_sn'] = $rp_sn;
			$where['member_id'] = $member_id;
			$order = M('Repair')->where($where)->find();
			if (is_array($order) && !empty($order)) {
				if ($order['rp_status'] != 3) {
					$this->error('该订单号无法进行支付,请联系客服.');
				}else {
					$alipay_data['total_fee'] = $order['price'];//订单总金额
					$alipay_data['out_trade_no'] = $order['rp_sn'];//商户订单ID
					$alipay_data['subject'] = '佐西卡维修支付';//订单商品标题
					$alipay_data['body'] = '订单号:'.$order['rp_sn'];//订单商品描述
					$alipay_data['show_url'] = 'http://'.$_SERVER['SERVER_NAME'].U('Member/progress',array('sn'=>$order['rp_sn']));//订单商品地址
					$alipay_data['notify_url'] = U('Home/Pay/alipayNotify', array('type'=>'repair'), true, true);
					$alipay_data['return_url'] = U('Home/Pay/alipayReturn', array('type'=>'repair'), true, true);
					$alipay = new Alipay();
					$alipay->toAlipay($alipay_data);
				}
			}else {
				$this->error('非法操作');
			}
		}
	}
	/**
	 * 支付宝同步通知
	 * @return [type] [description]
	 */
	public function alipayReturn()
	{
		if (empty($_GET) ) {
			$this->error('您查看的页面不存在');
		}
		$alipay = new Alipay();
		if (!$alipay->isAlipay($_GET)) {
			echo '验证失败请不要做违法行为！';die;
			$this->error('验证失败请不要做违法行为！');
		}
		$alipay_no = I('get.trade_no');
		$order_sn = I('get.out_trade_no');
		$status = I('get.trade_status');
		/**
		 * 这里需要修改。！！！
		 * --------------------------
		 * 写出自己的业务逻辑。
		 * 从数据库中获取订单信息，然后判断订单状态是否经过处理什么的！！
		 * ------------------
		*/
		if ( $status == 'TRADE_FINISHED' || $status == 'TRADE_SUCCESS') {
			if ($_GET['type'] == 'repair') {
				$this->mod = M('Repair');
				$where = array('rp_sn'=>$order_sn);
				$order = $this->mod->where($where)->find();
				if ($order['rp_status'] == 3){
					$res = $this->mod->where($where)->setField('rp_status',4);
					//订单日志
					$log_data['rp_id'] = $order['rp_id'];
					$log_data['log_content'] = '会员已支付维修订单.';
					$log_data['log_time'] = NOW_TIME;
					$log_data['is_view'] = 1;
					M('RepairLog')->add($log_data);
					$this->redirect('Member/progress',array('sn'=>$order['rp_sn']));
				}else {
					//订单日志
					$log_data['rp_id'] = $order['rp_id'];
					$log_data['log_content'] = '客户支付完成.但维修订单状态异常.';
					$log_data['log_time'] = NOW_TIME;
					$log_data['is_view'] = 1;
					M('RepairLog')->add($log_data);
					$this->redirect('Member/progress',array('sn'=>$order['rp_sn']));
				}
			}else {
				$where = array('order_sn'=>$order_sn);
				$order = $this->mod->where($where)->find();
				if ($order['order_state'] == 10){
					$res = $this->mod->where($where)->setField('order_state',20);
					//订单日志
					$log_data['order_id'] = $order['order_id'];
					$log_data['order_state'] = get_order_state_name(20);
					$log_data['change_state'] = get_order_state_name(30);
					$log_data['state_info'] = '会员已支付订单';
					$log_data['log_time'] = NOW_TIME;
					$log_data['operator'] = '会员';
					M('OrderLog')->add($log_data);
					$this->redirect('Order/detail',array('sn'=>$order_sn));
				}else {
					//订单日志
					$log_data['order_id'] = $order['order_id'];
					$log_data['order_state'] = get_order_state_name(0);
					$log_data['change_state'] = get_order_state_name(0);
					$log_data['state_info'] = '客户支付完成.但订单状态异常.异常状态为'.$order['order_state'].':'.get_order_state_name($order['order_state']);
					$log_data['log_time'] = NOW_TIME;
					$log_data['operator'] = '会员';
					M('OrderLog')->add($log_data);
					$this->redirect('Order/detail',array('sn'=>$order_sn));
				}
			}
		}else {
			echo '订单支付失败,请联系服务客服.';die;
			$this->error('订单支付失败,请联系服务客服.');
		}
	}
	/**
	 * 支付宝异步通知
	 * @return [type] [description]
	 */
	public function alipayNotify()
	{
		if (empty($_POST) ) {
			$this->error('您查看的页面不存在');
		}
		$alipay = new Alipay();
		if (!$alipay->isAlipay($_POST) ) {
			$this->error('请不要做违法行为！');
		}
		$alipay_no = I('post.trade_no');
		$order_sn = I('post.out_trade_no');
		$status = I('post.trade_status');
		if ( $status == 'TRADE_FINISHED' || $status == 'TRADE_SUCCESS') {
			if ($_POST['type'] == 'repair') {
				$this->mod = M('Repair');
				$where = array('rp_sn'=>$order_sn);
				$order = $this->mod->where($where)->find();
				if ($order['rp_status'] == 3){
					$res = $this->mod->where($where)->setField('rp_status',4);
					//订单日志
					$log_data['rp_id'] = $order['rp_id'];
					$log_data['log_content'] = '会员已支付维修订单.';
					$log_data['log_time'] = NOW_TIME;
					$log_data['is_view'] = 1;
					M('RepairLog')->add($log_data);
				}
				echo 'success';
			}else {
				$where = array('order_sn'=>$order_sn);
				$order = $this->mod->where($where)->find();
				if ($order['order_state'] == 10){
					$res = $this->mod->where($where)->setField('order_state',20);
					//订单日志
					$log_data['order_id'] = $order['order_id'];
					$log_data['order_state'] = get_order_state_name(20);
					$log_data['change_state'] = get_order_state_name(30);
					$log_data['state_info'] = '会员已支付订单';
					$log_data['log_time'] = NOW_TIME;
					$log_data['operator'] = '会员';
					M('OrderLog')->add($log_data);
				}
				echo 'success';
			}
		}
	}
	public function jdpay(){
		$order_sn = trim($_GET['order_sn']);
		$member_id = $this->mid;
		$where['order_sn'] = $order_sn;
		$where['member_id'] = $member_id;
		$order = $this->mod->where($where)->find();
		if (is_array($order) && !empty($order)) {
			if ($order['order_state'] != 10) {
				$this->error('该订单号无法进行支付,请联系客服.');
			}else {
				$alipay_data['total_fee'] = $order['order_amount'];//订单总金额
				$alipay_data['out_trade_no'] = $order['order_sn'];//商户订单ID
				$alipay_data['subject'] = '佐西卡购物支付';//订单商品标题
				$alipay_data['body'] = '订单号:'.$order['order_sn'];//订单商品描述
				$alipay_data['show_url'] = 'http://'.$_SERVER['SERVER_NAME'].U('Member/order',array('sn'=>$order['order_sn']));//订单商品地址
				$alipay_data['notify_url'] = U('Home/Pay/alipayNotify', '', true, true);
				$alipay_data['return_url'] = U('Home/Pay/alipayReturn', '', true, true);
				$alipay = new Alipay();
				$alipay->toAlipay($alipay_data);
			}
		}else {
			$this->error('非法操作');
		}
	}
	public function bdpay(){
		$order_sn = trim($_GET['order_sn']);
		if ($order_sn) {
			$member_id = $this->mid;
			$where['order_sn'] = $order_sn;
			$where['member_id'] = $member_id;
			$order = $this->mod->where($where)->find();
			if (is_array($order) && !empty($order)) {
				if ($order['order_state'] != 10) {
					$this->error('该订单号无法进行支付,请联系客服.');
				}else {
					$pay_data['total_amount'] = $order['order_amount'];//订单总金额
					$pay_data['order_no'] = $order['order_sn'];//商户订单ID
					$pay_data['goods_name'] = iconv("UTF-8", "GBK", urldecode('佐西卡购物支付'));//订单商品标题
					$pay_data['goods_desc'] = iconv("UTF-8", "GBK", urldecode('订单号:'.$order['order_sn']));//订单商品描述
					$pay_data['goods_url'] = 'http://'.$_SERVER['SERVER_NAME'].U('Member/order',array('sn'=>$order['order_sn']));//订单商品地址
					$bdpay = new Bdpay();
					$bdpay->toBdpay($pay_data);
				}
			}else {
				$this->error('非法操作');
			}
		}elseif (trim($_GET['rp_sn'])) {
			$rp_sn = trim($_GET['rp_sn']);
			$member_id = $this->mid;
			$where['rp_sn'] = $rp_sn;
			$where['member_id'] = $member_id;
			$order = M('Repair')->where($where)->find();
			if (is_array($order) && !empty($order)) {
				if ($order['rp_status'] != 3) {
					$this->error('该订单号无法进行支付,请联系客服.');
				}else {
					$pay_data['total_amount'] = $order['price'];//订单总金额
					$pay_data['order_no'] = $order['rp_sn'];//商户订单ID
					$pay_data['goods_name'] = iconv("UTF-8", "GBK", urldecode('佐西卡维修支付'));//订单商品标题
					$pay_data['goods_desc'] = iconv("UTF-8", "GBK", urldecode('订单号:'.$order['rp_sn']));//订单商品描述
					$pay_data['goods_url'] = 'http://'.$_SERVER['SERVER_NAME'].U('Member/progress',array('sn'=>$order['rp_sn']));//订单商品地址
					$pay_data['extra'] = 'repair';
					$bdpay = new Bdpay();
					$bdpay->toBdpay($pay_data);
				}
			}else {
				$this->error('非法操作');
			}
		}
	}
	/**
	 * 百度支付异步通知
	 */
	public function bdpayNotify(){
		$bdpay = new Bdpay();
		if ($bdpay->check_bfb_pay_result_notify()) {
			$order_sn = trim($_GET['order_no']);
			if ($_GET['type'] == 'repair') {
				$this->mod = M('Repair');
				$where = array('rp_sn'=>$order_sn);
				$order = $this->mod->where($where)->find();
				if ($order['rp_status'] == 3){
					$res = $this->mod->where($where)->setField('rp_status',4);
					//订单日志
					$log_data['rp_id'] = $order['rp_id'];
					$log_data['log_content'] = '会员已支付维修订单.';
					$log_data['log_time'] = NOW_TIME;
					$log_data['is_view'] = 1;
					M('RepairLog')->add($log_data);
				}else {
					//订单日志
					$log_data['rp_id'] = $order['rp_id'];
					$log_data['log_content'] = '客户支付完成.但维修订单状态异常.';
					$log_data['log_time'] = NOW_TIME;
					$log_data['is_view'] = 1;
					M('RepairLog')->add($log_data);
				}
			}else {
				$where = array('order_sn'=>$order_sn);
				$order = $this->mod->where($where)->find();
				if ($order['order_state'] == 10){
					$res = $this->mod->where($where)->setField('order_state',20);
					//订单日志
					$log_data['order_id'] = $order['order_id'];
					$log_data['order_state'] = get_order_state_name(20);
					$log_data['change_state'] = get_order_state_name(30);
					$log_data['state_info'] = '会员已支付订单';
					$log_data['log_time'] = NOW_TIME;
					$log_data['operator'] = '会员';
					M('OrderLog')->add($log_data);
				}else {
					//订单日志
					$log_data['order_id'] = $order['order_id'];
					$log_data['order_state'] = get_order_state_name(0);
					$log_data['change_state'] = get_order_state_name(0);
					$log_data['state_info'] = '客户支付完成.但订单状态异常.异常状态为'.$order['order_state'].':'.get_order_state_name($order['order_state']);
					$log_data['log_time'] = NOW_TIME;
					$log_data['operator'] = '会员';
					M('OrderLog')->add($log_data);
				}
			}
			if ($res) {
				$bdpay->notify_bfb();
			}
		}
	}
	/**
	 * 百度支付同步通知
	 */
	public function bdpayReturn(){
		if ($_GET['extra'] == 'repair') {
			redirect(U('Member/progress',array('sn'=>$_GET['order_no'])));
		}else {
			redirect(U('Order/detail',array('sn'=>$_GET['order_no'])));
		}
	}
}