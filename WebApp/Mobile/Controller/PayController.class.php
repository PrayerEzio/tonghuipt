<?php
/**
 * 支付宝支付
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Common\Lib\Alipay\Alipay;
use Think\Controller;
class PayController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->mod = D('Order');
		$this->mid = session('member_id');
	}
	public function predepositpay()
	{
		$order_sn = trim($_GET['order_sn']);
		$order_where['member_id'] = $this->mid;
		$order_where['order_sn'] = $order_sn;
		$order = D('Order')->where($order_where)->find();
		if (is_array($order) && !empty($order)) {
			if ($order['order_state'] != 10) {
				$this->error('该订单号无法进行支付,请联系客服.');
			}else {
				$predeposit = M('Member')->where(array('member_id'=>$this->mid,'member_status'=>1))->getField('predeposit');
				if ($predeposit < $order['order_amount'])
				{
					$this->error('静态不足,请充值');
				}else {
					$res = M('Member')->where(array('member_id'=>$this->mid))->setDec('predeposit',$order['order_amount']);
					if ($res)
					{
						$this->finishPay($order_sn);
						$this->success('支付成功',U('Order/index'));
					}else {
						$this->error('抱歉,支付失败.');
					}
				}
			}
		}else {
			$this->error('非法操作');
		}
	}

	public function point()
	{
		$order_sn = trim($_GET['order_sn']);
		$order_where['member_id'] = $this->mid;
		$order_where['order_sn'] = $order_sn;
		$order_where['order_amount'] = 0;
		$order = D('Order')->where($order_where)->find();
		if (is_array($order) && !empty($order)) {
			if ($order['order_state'] != 10) {
				$this->error('该订单号无法进行支付,请联系客服.');
			}else {
				$this->finishPay($order_sn);
				$this->success('支付成功',U('Order/index'));
			}
		}else {
			$this->error('非法操作');
		}
	}

	private function finishPay($order_sn)
	{
		$where['order_sn'] = $order_sn;
		$order = M('Order')->where($where)->find();
		if ($order['order_state'] == 10){
			switch ($order['order_type'])
			{
				case 1:
					$bill_log = '购买商品';
					$channel = 1;
					$res = $this->mod->where($where)->setField('order_state',20);
					break;
				case 2:
					//充值
					$bill_log = '充值静态';
					$channel = 2;
					$s = M('Member')->where(array('member_id'=>$order['member_id']))->setInc('predeposit',$order['goods_amount']);
					if ($s)
					{
						$res = $this->mod->where($where)->setField('order_state',50);
						$bill['member_id'] = $order['member_id'];
						$bill['bill_log'] = '静态充值成功';
						$bill['amount'] = $order['goods_amount'];
						$bill['balance'] = M('Member')->where(array('member_id'=>$order['member_id']))->getField('predeposit');
						$bill['addtime'] = NOW_TIME;
						$bill['bill_type'] = 1;
						$bill['channel'] = 2;
						M('MemberBill')->add($bill);
					}
					break;
				case 3:
					//TODO:购买vip
					$bill_log = '购买vip';
					$channel = 3;
					$res = $this->mod->where($where)->setField('order_state',50);
					break;
				case 4:
					//购买代理商
					$bill_log = '购买代理';
					$channel = 4;
					$res = $this->mod->where($where)->setField('order_state',50);
					$agent_info = M('AgentInfo')->where(array('agent_id'=>$order['order_param']))->find();
					if ($agent_info && $res)
					{
						$s = M('Member')->where(array('member_id'=>$order['member_id']))->setField('agent_id',$agent_info['agent_id']);
						$agent['member_id'] = $order['member_id'];
						$agent['create_time'] = NOW_TIME;
						$agent['status'] = 1;
						$agent['agent_id'] = $agent_info['agent_id'];
						$agent['agent_level'] = $agent_info['agent_level'];
						M('Agent')->add($agent);
						$this->giveDistributionRedPacket($order['order_id'],$agent_info['agent_level']);
						if ($agent_info['agent_level'] == 9)
						{
							//加入公排
							$board['member_id'] = $order['member_id'];
							$board['board_status'] = 0;
							$board['expect_num'] = MSC('board_expect_num');
							$board['differ_num'] = MSC('board_expect_num');
							$board['finish_num'] = 0;
							$board['create_time'] = NOW_TIME;
							M('Board')->add($board);
							//给公排收益
							$active_board_where['board_status'] = 0;
							$active_board_where['differ_num'] = array('gt',0);
							$active_board_where['finish_time'] = 0;
							$active_board = M('Board')->where($active_board_where)->order('create_time asc,board_id asc')->find();
							if ($active_board)
							{
								//更新公排数据
								$update_board['differ_num'] = $active_board['differ_num']-1;
								$update_board['finish_num'] = $active_board['finish_num']+1;
								if ($update_board['differ_num'] == 0)
								{
									$update_board['finish_time'] = NOW_TIME;
									$update_board['board_status'] = 1;
								}
								$update_board_result = M('Board')->where(array('board_id'=>$active_board['board_id']))->save($update_board);
								if ($update_board_result)
								{
									$board_reward_result = M('Member')->where(array('member_id'=>$active_board['member_id']))->setInc('predeposit',MSC('board_reward'));
									if ($board_reward_result)
									{
										$bill['member_id'] = $active_board['member_id'];
										$bill['bill_log'] = '来自全国代理公排收益';
										$bill['amount'] = MSC('board_reward');
										$bill['balance'] = M('Member')->where(array('member_id'=>$active_board['member_id']))->getField('predeposit');
										$bill['addtime'] = NOW_TIME;
										$bill['bill_type'] = 1;
										$bill['channel'] = 6;
										M('MemberBill')->add($bill);
									}
								}
							}
						}
					}
					break;
				case 5:
					//放贷(排单)
					$bill_log = '排单';
					$channel = -9;
					$loan_info_where['loan_id'] = $order['order_param'];
					$loan_info = M('Loan')->where($loan_info_where)->find();
					$data['member_id'] = $order['member_id'];
					$data['loan_id'] = $loan_info['loan_id'];
					$data['loan_level'] = $loan_info['loan_level'];
					$data['create_time'] = time();
					$data['start_time'] = strtotime('+1 day');
					$data['end_time'] = $data['start_time']+$loan_info['cycle']*24*60*60;
					$data['execution_times'] = 0;
					$data['status'] = 1;
					$data['active'] = 1;
					$data['order_sn'] = $order_sn;
 					$s = M('LoanRecord')->add($data);
					if ($s)
					{
						$res = $this->mod->where($where)->setField('order_state',50);
						$bill['member_id'] = $order['member_id'];
						$bill['bill_log'] = '购买排单成功';
						$bill['amount'] = $order['goods_amount'];
						$bill['balance'] = M('Member')->where(array('member_id'=>$order['member_id']))->getField('predeposit');
						$bill['addtime'] = NOW_TIME;
						$bill['bill_type'] = 1;
						$bill['channel'] = -9;
						M('MemberBill')->add($bill);
					}
					break;
				default :break;
			}
			//更改订单状态
			$this->mod->where($where)->setField('payment_time',time());
			//资金日志
			$bill['member_id'] = $order['member_id'];
			$bill['bill_log'] = $bill_log;
			$bill['amount'] = $order['order_amount'];
			$bill['balance'] = M('Member')->where(array('member_id'=>$order['member_id']))->getField('predeposit');
			$bill['addtime'] = NOW_TIME;
			$bill['bill_type'] = -1;
			$bill['channel'] = $channel;
			M('MemberBill')->add($bill);
			//订单日志
			$log_data['order_id'] = $order['order_id'];
			$log_data['order_state'] = get_order_state_name(20);
			$log_data['change_state'] = get_order_state_name(30);
			$log_data['state_info'] = '会员已支付订单';
			$log_data['log_time'] = NOW_TIME;
			$log_data['operator'] = '会员';
			M('OrderLog')->add($log_data);
			if ($order['order_type'] != 1)
			{
				//订单日志
				$log_data['order_id'] = $order['order_id'];
				$log_data['order_state'] = get_order_state_name(30);
				$log_data['change_state'] = get_order_state_name(50);
				$log_data['state_info'] = '系统已完成订单';
				$log_data['log_time'] = NOW_TIME;
				$log_data['operator'] = '系统';
				M('OrderLog')->add($log_data);
			}
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
					$alipay_data['subject'] = '泰鑫国际订单支付';//订单商品标题
					$alipay_data['body'] = '订单号:'.$order['order_sn'];//订单商品描述
					$alipay_data['show_url'] = 'http://'.$_SERVER['SERVER_NAME'].U('Member/order',array('sn'=>$order['order_sn']));//订单商品地址
					$alipay_data['notify_url'] = U('Mobile/Pay/alipayNotify', '', true, true);
					$alipay_data['return_url'] = U('Mobile/Pay/alipayReturn', '', true, true);
					$alipay = new Alipay();
					$alipay->toAlipay($alipay_data);
				}
			}else {
				$this->error('非法操作');
			}
		}else {
			$this->error('订单不存在.');
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

	/**
	 * 微信支付
	 */
	public function wxpay()
	{
		$order_sn = trim($_GET['order_sn']);
		if ($order_sn) {
			$member_id = $this->mid;
			$open_id = M('Member')->where(array('member_id'=>$member_id))->getField('openid');
			$where['order_sn'] = $order_sn;
			$where['member_id'] = $member_id;
			$order = $this->mod->where($where)->find();
			if (is_array($order) && !empty($order)) {
				if ($order['order_state'] != 10) {
					$this->error('该订单号无法进行支付,请联系客服.');
				}else {
					$wxpay_date['openid'] = $open_id;
					$wxpay_date['body'] = '订单号:'.$order['order_sn'];//订单商品描述
					$wxpay_date['total_fee'] = intval($order['order_amount']*100);//订单总金额
					$wxpay_date['out_trade_no'] = $order['order_sn'];//商户订单ID
					$wxpay_date['notify_url'] = U('Mobile/Pay/wxpayNotify', '', true, true);
					$jsApiParameters = jsapi_pay($wxpay_date);
					$this->assign('jsApiParameters',$jsApiParameters);
					switch ($order['order_type'])
					{
						case 1 : $this->url = U('Mobile/Order/detail', array('sn'=>$order_sn));break;
						case 2 : $this->url = U('Mobile/Member/index');break;
						case 3 : $this->url = U('Mobile/Member/index');break;
						case 4: $this->url = U('Mobile/Member/index');break;
						default: $this->url = U('Mobile/Order/detail', array('sn'=>$order_sn));break;
					}
					$this->display();
				}
			}else {
				$this->error('非法操作');
			}
		}else {
			$this->error('订单不存在.');
		}
	}

	/**
	 * 微信支付异步通知
	 */
	public function wxpayNotify()
	{
		Vendor('WxPayPubHelper.WxPayPubHelper');
		//使用通用通知接口
		$notify = new \Notify_pub();
		//存储微信的回调
		$xml = $GLOBALS['HTTP_RAW_POST_DATA'];
		$notify->saveData($xml);
		//验证签名，并回应微信。
		//对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
		//微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
		//尽可能提高通知的成功率，但微信不保证通知最终能成功。
		if($notify->checkSign() == FALSE){
			$notify->setReturnParameter("return_code","FAIL");//返回状态码
			$notify->setReturnParameter("return_msg","签名失败");//返回信息
			system_log('微信支付异步接口验证签名失败',$xml,2,'WechatPay');
		}else{
			$notify->setReturnParameter("return_code","SUCCESS");//设置返回码
			system_log('微信支付异步接验证签名成功',$xml,0,'WechatPay');
		}
		$returnXml = $notify->returnXml();
		//==商户根据实际情况设置相应的处理流程，此处仅作举例=======
		system_log('接受到微信notify通知',$xml,0,'WechatPay');

		if($notify->checkSign() == TRUE)
		{
			if ($notify->data["return_code"] == "FAIL") {
				//此处应该更新一下订单状态，商户自行增删操作
				system_log('微信notify通信出错',$xml,1,'WechatPay');
			}
			elseif($notify->data["result_code"] == "FAIL"){
				//此处应该更新一下订单状态，商户自行增删操作
				system_log('微信notify业务出错',$xml,1,'WechatPay');
			}
			else{
				//此处应该更新一下订单状态，商户自行增删操作
				system_log('微信notify支付成功',$xml,0,'WechatPay');
				$result_info = xmlToArray($xml);
				$order_sn = $result_info['out_trade_no'];
				$this->finishPay($order_sn);
				//推送支付完成信息
			}
			//商户自行增加处理流程,
			//例如：更新订单状态
			//例如：数据库操作
			//例如：推送支付完成信息
			//return $returnXml;
			echo 'SUCCESS';
		}
	}
}