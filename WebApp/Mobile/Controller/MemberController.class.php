<?php
/**
 * 会员中心
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Muxiangdao\DesUtils;
use Think\Page;
class MemberController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->check_login();
        //检查登录
        //session('wechat_openid',null);
        $this->m_info = M('Member')->where('member_id='.$this->mid)->find();
		if (empty($this->m_info['openid']))
		{
			$this->getWechatInfo();
		}
        $this->autoFinishOrder();
	}
	/**
	 * 会员中心.
	 */
	public function index()
	{
		$where['member_id'] = $this->mid;
		$user_info = D('Member')->relation(true)->where($where)->find();
		$this->user_info = $user_info;
		$this->display();
	}
	//登出
	public function logout()
	{
		session('member_id',null);
		session('wechat_openid',null);
		session('wechat',null);
		redirect(U('Index/index'));
	}
	//个人资料
	public function info()
	{
		if (IS_POST)
		{
			$data['nickname'] = trim($_POST['nickname']);
			$data['member_name'] = trim($_POST['member_name']);
			$data['gender'] = intval($_POST['gender']);
			$data['province'] = trim($_POST['province']);
			$data['city'] = trim($_POST['city']);
			$data['area'] = trim($_POST['area']);
			if(!empty($_FILES['avatar']['size'])){
				$arc_img = 'mid_avatar_'.$this->mid;
				$param = array('savePath'=>'member/','subName'=>'','files'=>$_FILES['avatar'],'saveName'=>$arc_img,'saveExt'=>'');
				$up_return = upload_one($param);
				if($up_return == 'error'){
					$this->error('图片上传失败');
					exit;
				}else{
					$data['avatar'] = C('SiteUrl').'/Uploads/'.$up_return;
				}
			}
			$data['wechat'] = trim($_POST['wechat']);
			$data['alipay'] = trim($_POST['alipay']);
			$res = M('Member')->where(array('member_id'=>$this->mid))->save($data);
			$this->success('修改资料成功');
		}elseif (IS_GET) {
			$user_info = M('Member')->where(array('member_id'=>$this->mid))->find();
			$province = M('District')->where(array('level'=>1,'status'=>1))->order('d_sort')->select();
			$this->province = $province;
			$this->user_info = $user_info;
			$this->display();
		}
	}
	//我的团队
	public function branch()
	{
		if (IS_POST)
		{
		}elseif (IS_GET){
			$where['parent_member_id'] = $this->mid;
			$list = M('Member')->where($where)->order('register_time')->select();
			$field = 'member_id,parent_member_id,agent_id';
			$loop = 9;
			foreach ($list as $key => $item)
			{
				$childs_member = $this->getChildsMember($item['member_id'],$field,$loop);
				$list[$key]['branch_num'] = count($childs_member);//count(getChildsId($all_list,$item['member_id'],'member_id','parent_member_id',$loop));//
				$agent_member_num = 0;
				foreach ($childs_member as $child)
				{
					if ($child['agent_id'])
					{
						$agent_member_num++;
					}
				}
				$list[$key]['agent_member_num'] = $agent_member_num;
			}
			$this->branch_num = count($this->getChildsMember($this->mid,$field,$loop));//count(getChildsId($all_list,$this->mid,'member_id','parent_member_id',$loop));//
			$parent_member_id = M('Member')->where(array('member_id'=>$this->mid))->getField('parent_member_id');
			$this->parent_member = M('Member')->where(array('member_id'=>$parent_member_id))->find();
			$this->list = $list;
			$this->display();
		}
	}
	//获取子级id
	private function getChildsMember($parent_member_id,$field = '*',$loop = 9999)
	{
		$array = array();
		if (!$loop)
		{
			return $array;
		}
		$loop--;
		$array = M('Member')->where(array('parent_member_id'=>$parent_member_id))->field($field)->select();
		if (!empty($array))
		{
			$child_array = array();
			foreach ($array as $v){
				$child = $this->getChildsMember($v['member_id'],$field,$loop);
				if ($child)
				{
					$child_array = array_merge($child_array,$child);
				}
			}
			$array = array_merge($array, $child_array);
			return $array;
		}else {
			return $array;
		}
	}
	//账单
	public function bill()
	{
		$bill_type = intval($_GET['bill_type']);
		$where['bill_type'] = $bill_type;
		$where['member_id'] = $this->mid;
		$count = M('MemberBill')->where($where)->count();
		$page = new Page($count,10);
		$page->rollPage = 3;
		$page->setConfig('prev','上一页');
		$page->setConfig('next','下一页');
		$page->setConfig('theme','%UP_PAGE% %LINK_PAGE% %DOWN_PAGE%');
		$list = M('MemberBill')->where($where)->limit($page->firstRow.','.$page->listRows)->order('addtime desc')->select();
		$this->list = $list;
        $this->page = $page->show();
		$this->display();
	}
	public function submitAddress()
	{
		if (IS_POST)
		{
			$where['order_sn'] = trim($_POST['sn']);
			$where['member_id'] = $this->mid;
			$where['order_state'] = 10;
			$where['order_type'] = 4;
			$order = M('Order')->where($where)->find();
			if ($order)
			{
				$orderAddress['order_id'] = $order['order_id'];
				$orderAddress['buyer_id'] = $order['member_id'];
				$order_address = M('OrderAddress')->where($orderAddress)->field('id')->find();
				$orderAddress['true_name'] = str_rp($_POST['name'],1);
				$orderAddress['prov_id'] = 19;
				$orderAddress['city_id'] = 291;
				$orderAddress['area_id'] = 3572;
				$orderAddress['address'] = str_rp($_POST['address'],1);
				$orderAddress['mob_phone'] = str_rp($_POST['mobile'],1);
				$orderAddress['add_time'] = NOW_TIME;
				if ($order_address['id'])
				{
					$res = M('OrderAddress')->where(array('id'=>$order_address['id']))->save($orderAddress);
				}else {
					$res = M('OrderAddress')->add($orderAddress);
				}
				if ($res)
				{
					//进行支付跳转
					switch (intval($_POST['pay_type'])){
						case 1:$this->success('地址填写成功,正在跳转支付.',U('Pay/alipay',array('order_sn'=>$order['order_sn'])));break;
						case 2:$this->success('地址填写成功,正在跳转支付.',U('Pay/bdpay',array('order_sn'=>$order['order_sn'])));break;
						case 3:$this->success('地址填写成功,正在跳转支付.',U('Pay/wxpay',array('order_sn'=>$order['order_sn'])));break;
						case 4:$this->success('地址填写成功,正在跳转支付.',U('Pay/predepositpay',array('order_sn'=>$order['order_sn'])));break;
					}
				}
			}else {
				$this->error('没有找到相关订单.');
			}
		}elseif(IS_GET)
		{
			$where['order_sn'] = trim($_GET['sn']);
			$where['member_id'] = $this->mid;
			$where['order_state'] = 10;
			$where['order_type'] = 4;
			$order = D('Order')->relation(true)->where($where)->find();
			if (empty($order))
			{
				$this->error('没有找到相关订单.');
			}
			$this->order = $order;
			$this->display('address');
		}
	}
	//站内信
	public function letter()
	{
		$where['member_id'] = $this->mid;
		$count = M('MemberLetter')->where($where)->count();
		$page = new Page($count,10);
		$list = M('MemberLetter')->where($where)->limit($page->firstRow.','.$page->listRows)->order('addtime desc')->select();
		$this->list = $list;
		$this->display();
	}
	public function agent()
	{
		$this->error('该功能已关闭');
		if (IS_POST)
		{
			$where['agent_id'] = intval($_POST['radio1']);
			if ($this->mid != 36 && $this->mid != 37 && $this->mid != 89)
			{
				$where['agent_status'] = 1;
			}
			$agent_info = M('AgentInfo')->where($where)->find();
			$max_level = M('AgentInfo')->Max('agent_level');
			//加入判断
			if ($agent_info['agent_level'] == $max_level)
			{
				$count = M('Board')->where(array('board_status'=>0,'member_id'=>$this->mid))->count();
				if ($count > 3)
				{
					$this->error('您的公排系统还在结算,请勿重复购买.');
				}
			}else {
				if ($agent_info['agent_level'] != 2)
				{
					$my_max_level_where['member_id'] = $this->mid;
					$my_max_level = M('Agent')->where($my_max_level_where)->Max('agent_level');
					if ($my_max_level != $max_level && $my_max_level >= $agent_info['agent_level'])
					{
						$this->error('您不能购买更低级的代理级别.');
					}
					if ($my_max_level == $max_level && $my_max_level > $agent_info['agent_level'])
					{
						$this->error('您不能购买更低级的代理级别.');
					}
				}
			}
			if ($agent_info)
			{
				//生成订单并跳转
				$order['order_sn'] = order_sn();
				$order['member_id'] = $this->mid;
				$order['order_type'] = 4;
				$order['order_param'] = $agent_info['agent_id'];
				$order['payment_id'] = 4;
				switch (intval($_POST['pay_type'])){
					case 1:$order['payment_name'] = 'alipay';break;
					case 2:$order['payment_name'] = 'bdpay';break;
					case 3:$order['payment_name'] = 'wxpay';break;
					default : $order['payment_name'] = 'undefine';break;
				}
				$order['order_points'] = $agent_info['get_points'];
				$order['cost_points'] = $agent_info['cost_points'];
				$order['goods_amount'] = $agent_info['price'];
				$order['discount'] = 0;
				$order['order_amount'] = $agent_info['price'];
				$order['order_state'] = 10;
				$order['add_time'] = NOW_TIME;
				if ($agent_info['gift_id_str'])
				{
					$goods_id_array = explode(',',$agent_info['gift_id_str']);
					foreach ($goods_id_array as $key => $val){
						$goods = M('Goods')->where(array('goods_id'=>$val))->find();
						if (!empty($goods)) {
							$data['OrderGoods'][$key]['goods_id'] = $goods['goods_id'];
							$data['OrderGoods'][$key]['goods_price'] = 0;
							$data['OrderGoods'][$key]['goods_mkprice'] = $goods['goods_mktprice'];
							$data['OrderGoods'][$key]['goods_num'] = 1;
							$data['OrderGoods'][$key]['goods_name'] = $goods['goods_name'];
							$data['OrderGoods'][$key]['goods_image'] = $goods['goods_pic'];
						}
					}
				}else {
					$goods_id_array = array();
				}
				$res = D('Order')->relation(true)->add($order);
				if ($res)
				{
					if (!empty($goods_id_array))
					{
						$this->success('订单生成成功',U('Member/submitAddress',array('sn'=>$order['order_sn'],'pay_type'=>intval($_POST['pay_type']))));
					}else {
						//进行支付跳转
						switch (intval($_POST['pay_type'])){
                            case 1:$this->success('订单生成成功',U('Pay/alipay',array('order_sn'=>$order['order_sn'])));break;
                            case 2:$this->success('订单生成成功',U('Pay/bdpay',array('order_sn'=>$order['order_sn'])));break;
                            case 3:$this->success('订单生成成功',U('Pay/wxpay',array('order_sn'=>$order['order_sn'])));break;
                            case 4:$this->success('订单生成成功',U('Pay/predepositpay',array('order_sn'=>$order['order_sn'])));break;
                        }
					}
				}else {
				}
			}else {
				//报错
			}
		}elseif (IS_GET)
		{
			if ($this->mid != 36 && $this->mid != 37 && $this->mid != 89)
			{
				$where['agent_status'] = 1;
			}
			$this->list = M('AgentInfo')->where($where)->order('agent_sort desc,agent_level desc')->select();
			$where['member_id'] = $this->mid;
			$user_info = D('Member')->relation(true)->where($where)->find();
			$this->user_info = $user_info;
			$this->display();
		}
	}
	public function loan()
	{
		if (IS_POST)
		{
			$where['loan_id'] = intval($_POST['radio1']);
			if ($this->mid != 36 && $this->mid != 37 && $this->mid != 89)
			{
				$where['status'] = 1;
			}
			$loan_info = M('Loan')->where($where)->find();
			$sub_member_count_where['parent_member_id'] = $this->mid;
			$sub_member_count = M('Member')->where($sub_member_count_where)->count();
			$loan_status = M('Member')->where(array('member_id'=>$this->mid))->getField('loan_status');
			if (!$loan_status)
			{
				$this->error('您还没有排单权限,请联系管理员开通.');
			}
			if ($sub_member_count < $loan_info['need_sub_member'])
			{
				$this->error('您的下级会员数量不够哦.');
			}
			$more_level_count_where['member_id'] = $this->mid;
			$more_level_count_where['loan_level'] = array('gt',$loan_info['loan_level']);
			$more_level_count_where['active'] = 1;
			$more_level_count = M('LoanRecord')->where($more_level_count_where)->count();
			if ($more_level_count)
			{
				$this->error('您还有更大的排单正在活跃状态,不能排小单.');
			}
			$today_count_where['member_id'] = $this->mid;
			$today_count_where['create_time'] = array('egt',strtotime(date('Y-m-d',time())));
			$today_count_where['status'] = 1;
			$today_count = M('LoanRecord')->where($today_count_where)->count();
			if ($today_count)
			{
				$this->error('您今天已经排过单了,请明天再来.');
			}
			if ($loan_info)
			{
				//生成订单并跳转
				$order['order_sn'] = order_sn();
				$order['member_id'] = $this->mid;
				$order['order_type'] = 5;
				$order['order_param'] = $loan_info['loan_id'];
				$order['payment_id'] = 4;
				switch (intval($_POST['pay_type'])){
					case 1:$order['payment_name'] = 'alipay';break;
					case 2:$order['payment_name'] = 'bdpay';break;
					case 3:$order['payment_name'] = 'wxpay';break;
					default : $order['payment_name'] = 'undefine';break;
				}
				$order['goods_amount'] = $loan_info['price'];
				$order['discount'] = 0;
				$order['order_amount'] = $loan_info['price'];
				$order['order_state'] = 10;
				$order['add_time'] = NOW_TIME;
				$res = D('Order')->relation(true)->add($order);
				if ($res)
				{
					//进行支付跳转
					switch (intval($_POST['pay_type'])){
						case 1:$this->success('订单生成成功',U('Pay/alipay',array('order_sn'=>$order['order_sn'])));break;
						case 2:$this->success('订单生成成功',U('Pay/bdpay',array('order_sn'=>$order['order_sn'])));break;
						case 3:$this->success('订单生成成功',U('Pay/wxpay',array('order_sn'=>$order['order_sn'])));break;
						case 4:$this->success('订单生成成功',U('Pay/predepositpay',array('order_sn'=>$order['order_sn'])));break;
					}
				}else {
				}
			}else {
				//报错
			}
		}elseif (IS_GET)
		{
			if ($this->mid != 36 && $this->mid != 37 && $this->mid != 89)
			{
				$where['status'] = 1;
			}
			$this->list = M('Loan')->where($where)->order('loan_sort desc,loan_level desc')->select();
			$where['member_id'] = $this->mid;
			$user_info = D('Member')->relation(true)->where($where)->find();
			$this->user_info = $user_info;
			$this->display();
		}
	}
	//订单自动完成
	private function autoFinishOrder()
	{
		$where['auto_finish_time'] = array('between',array(1,time()));
		$where['order_state'] = array('between',array(31,49));
		$order_list = M('Order')->where($where)->select();
		foreach ($order_list as $key => $order)
		{
			$where['order_id'] = $order['order_id'];
			$order = D('Order')->relation('OrderGoods')->where($where)->find();
			//变更交易状态
			$res = M('Order')->where($where)->setField('order_state',50);
			if ($res)
			{
				//赠送商品积分
				$points_res = M('Member')->where(array('member_id'=>$order['member_id']))->setInc('point',$order['order_points']);
				//扣除所需积分需要在支付时扣除
				//M('Member')->where(array('member_id'=>$order['member_id']))->setDec('point',$order['cost_points']);
				//TODO:积分日志
				//订单日志
				$log_data['order_id'] = $order['order_id'];
				$log_data['order_state'] = get_order_state_name(40);
				$log_data['change_state'] = get_order_state_name(50);
				$log_data['state_info'] = '系统自动完成订单';
				$log_data['log_time'] = $order['auto_finish_time'];//NOW_TIME;
				$log_data['operator'] = '系统';
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
					$data['data']['first']['value'] = '您的订单:'.$order['order_sn'].'已自动完成.';
					$data['data']['first']['color'] = '#173177';
					$data['data']['orderno']['value'] = $order['order_sn'];
					$data['data']['orderno']['color'] = '#173177';
					$data['data']['refundno']['value'] = 1;
					$data['data']['refundno']['color'] = '#173177';
					$data['data']['refundproduct']['value'] = price_format($order['order_amount']);
					$data['data']['refundproduct']['color'] = '#173177';
					$data['data']['remark']['value'] = '如有疑问，请联系客服894916947。';
					$data['data']['remark']['color'] = '#173177';
					sendTemplateMsg($data);
				}
			}
		}
	}
	
	//充值
	public function recharge()
	{
		if(IS_POST)
		{
			$amount = floatval($_POST['amount']);
			//生成订单并跳转
			$order['order_sn'] = order_sn();
			$order['member_id'] = $this->mid;
			$order['order_type'] = 2;
			$order['payment_id'] = 4;
			switch (intval($_POST['pay_type'])){
				case 1:$order['payment_name'] = 'alipay';break;
				case 2:$order['payment_name'] = 'bdpay';break;
				case 3:$order['payment_name'] = 'wxpay';break;
				default : $order['payment_name'] = 'undefine';break;
			}
			$order['order_points'] = 0;
			$order['cost_points'] = 0;
			$order['goods_amount'] = $amount;
			$order['discount'] = 0;
			$order['order_amount'] = $amount;
			$order['order_state'] = 10;
			$order['add_time'] = NOW_TIME;
			$res = M('Order')->add($order);
			if ($res)
			{
				//进行支付跳转
				switch (intval($_POST['pay_type'])){
					case 1:$this->success('订单生成成功',U('Pay/alipay',array('order_sn'=>$order['order_sn'])));break;
					case 2:$this->success('订单生成成功',U('Pay/bdpay',array('order_sn'=>$order['order_sn'])));break;
					case 3:$this->success('订单生成成功',U('Pay/wxpay',array('order_sn'=>$order['order_sn'])));break;
				}
			}else {
			}
		}elseif (IS_GET)
		{
			$this->member_info = M('Member')->where(array('member_id'=>$this->mid))->field('predeposit')->find();
			$this->display();
		}
	}
	//提现
	public function withdraw()
	{
		if(IS_POST)
		{
			$withdraw_status = M('Member')->where(array('member_id'=>$this->mid))->getField('withdraw_status');
			if (!$withdraw_status)
			{
				$this->error('抱歉,您没有提现的权限.');
			}
			$amount = floatval($_POST['amount']);
			$predeposit = M('Member')->where(array('member_id'=>$this->mid))->getField('predeposit');
			$judge_amount = intval($amount/10)*10;
			$order_count_where['member_id'] = $this->mid;
			$order_count_where['order_type'] = -2;
			$order_count_where['add_time'] = array('gt',strtotime(date('Y-m-d',time())));
			//$order_count = M('Order')->where($order_count_where)->count();
			$order_sum = M('Order')->where($order_count_where)->sum('order_amount');
			$bill_count_where['bill_type'] = -1;
			$bill_count_where['channel'] = -2;
			$bill_count_where['member_id'] = $this->mid;
			$bill_count_where['addtime'] = array('gt',strtotime(date('Y-m-d',time())));
			$bill_sum = M('MemberBill')->where($bill_count_where)->sum('amount');
			if ($order_sum+$amount > 200 || $bill_sum+$amount > 200)
			{
				$this->error('您今日已经没有提现额度了,请明天再来.');die;
			}
			/*$bill_count = M('MemberBill')->where($bill_count_where)->find();
			if ($order_count || $bill_count)
			{
				$this->error('您今日已经没有提现次数了,请明天再来.');die;
			}*/
			if (!$amount || $amount > $predeposit || $judge_amount != $amount)
			{
				$this->error('提现金额不合法.');die;
			}
			if ($amount < 1)
			{
				$this->error('提现金额不能小于1元.');die;
			}
			if ($amount > 200)
			{
				$this->error('提现金额不能大于200元.');die;
			}
			$res = M('Member')->where(array('member_id'=>$this->mid))->setDec('predeposit',$amount);
			if (!$res)
			{
				$this->error('网络繁忙,请稍后再试.');
			}
			$desc = '会员提现处理';
			$order_sn = order_sn('wd');
			$data = array(
				'order_sn' => $order_sn,
				'member_id' => $this->mid,
				'order_type' => -2,
				'payment_name' => 'wxpay',
				'payment_id' => 4,
				'order_amount' => $amount,
				'goods_amount' => $amount,
				'order_state' => 50,
				'payment_time' => NOW_TIME,
				'add_time' => NOW_TIME,
			);
			//加载支付类库
			Vendor('WxPayPubHelper.WxPayPubHelper');
			$wxPay = new \Common_util_pub();
			$openid = M('member')->where(array('member_id'=>$this->mid))->getField('openid');
			$info = array(
				'wxappid' => Wx_C('wx_appid'),
				'mch_id' => Wx_C('wx_mch_id'),
				'mch_billno' => $order_sn,
				'client_ip' => get_client_ip(),
				're_openid' => $openid,
				'total_amount' => intval($amount*100),
				'min_value' => intval($amount*100),
				'max_value' => intval($amount*100),
				'total_num' => 1,
				'send_name' => '泰鑫国际',
				'wishing' => '泰鑫国际提现',
				'act_name' => '提现',
				'remark' => $desc,
				'nonce_str' => $wxPay->createNoncestr(32),
			);
			$info['sign'] = $wxPay->getSign($info);
			$arr = $info;
			$xml = $wxPay->arrayToXml($arr);
			$url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
			$result = $wxPay->postXmlSSLCurl($xml, $url);
			$res = $wxPay->xmlToArray($result);
			if ($res['result_code'] == 'SUCCESS') {//!empty($res['partner_trade_no'])
				M('Order')->add($data);
				//生成账单流水
				$bill['member_id'] = $data['member_id'];
				$bill['bill_log'] = '提现成功';
				$bill['amount'] = $data['order_amount'];
				$bill['balance'] = M('Member')->where(array('member_id'=>$data['member_id']))->getField('predeposit');
				$bill['addtime'] = NOW_TIME;
				$bill['bill_type'] = -1;
				$bill['channel'] = -2;
				M('MemberBill')->add($bill);
				$this->success('提现成功.');
			}else {
				system_log('会员提现失败',json_encode($res),5,'wechat');
				M('Member')->where(array('member_id'=>$data['member_id']))->setInc('predeposit',$data['order_amount']);
				$this->error('提现失败,请稍后再试.');
			}
		}elseif (IS_GET)
		{
			$this->member_info = M('Member')->where(array('member_id'=>$this->mid))->field('predeposit')->find();
			$this->display();
		}
	}
	public function curdGoods()
	{
		$merchant_status = M('Member')->where(array('member_id'=>$this->mid))->getField('merchant_status');
		if (empty($merchant_status))
		{
			$this->error('抱歉,您的权限等级不够.');
		}
		if (IS_POST)
		{
			$goods_id = intval($_POST['id']);
			if ($goods_id)
			{
				$where['goods_id'] = $goods_id;
				$where['member_id'] = $this->mid;
				$goods = M('Goods')->where(array('goods_id'=>$goods_id))->find();
			}
			$data['goods_name'] = trim($_POST['goods_name']);
			$data['goods_body'] = trim($_POST['goods_body']);
			$data['cost_point'] = intval($_POST['cost_point']);
			$data['goods_storage'] = intval($_POST['goods_storage']);
			$data['gc_id'] = intval($_POST['gc_id']);
			$data['addtime'] = time();
			//图片上传
			if(!empty($_FILES['goods_pic']['name']))
			{
				$goods_img = 'mid_point_goods_'.$this->mid.'_'.time();
				if ($goods['goods_pic'])
				{
					$old_pic = BasePath.'/Uploads/'.$goods['goods_pic'];
					unlink($old_pic);
				}
				$param = array('savePath'=>'points_goods/','subName'=>'','files'=>$_FILES['goods_pic'],'saveName'=>$goods_img,'saveExt'=>'');
				$param['thumb']['width'] = 200;
				$param['thumb']['height'] = 250;
				$up_return = upload_one($param);
				if($up_return == 'error')
				{
					$this->error('图片上传失败');
					exit;
				}else{
					$data['goods_pic'] = $up_return;
				}
			}
			$data['member_id'] = $this->mid;
			$data['goods_status'] = 1;
			if ($goods_id)
			{
				$res = M('Goods')->where($where)->save($data);
			}else {
				$res = M('Goods')->add($data);
			}
			if ($res)
			{
				$this->success('发布商品成功!',U('Shop/point'));
			}else {
				$this->error('发布商品失败!');
			}
		}elseif(IS_GET){
			$goods_id = intval($_GET['id']);
			if ($goods_id)
			{
				$where['goods_id'] = $goods_id;
				$where['member_id'] = $this->mid;
				$this->info = M('Goods')->where($where)->find();
			}
			$this->goods_class = M('GoodsClass')->order('gc_sort')->select();
			$this->display();
		}
	}
	public function transfer()
	{
		$a_member_where['member_id'] = $this->mid;
		$a_member_where['member_status'] = 1;
		$withdraw_status = M('Member')->where(array('member_id'=>$this->mid))->getField('withdraw_status');
		if (!$withdraw_status)
		{
			$this->error('抱歉,您没有转账的权限.');
		}
		if (IS_POST)
		{
			$type = I('post.type','');
			$amount = I('post.amount',0,'float');
			switch ($type)
			{
				case 'point' :
					$type_name = '动态';
					if (!$amount || $amount != intval($amount))
					{
						$this->error('转账'.$type_name.'不正确.');
					}
					break;
				case 'predeposit' :
					$type_name = '静态';
					if (!$amount)
					{
						$this->error('转账静态不正确.');
					}
					break;
				default :
					$this->error('非法操作');
					break;
			}
			$mid = I('post.b_mid',0,'int');
			if ($mid == $this->mid)
			{
				$this->error('用户id无效.');
			}
			$b_member_where['member_id'] = $mid;
			$b_member_where['member_status'] = 1;
			$mid = M('Member')->where($b_member_where)->getField('member_id');
			$total_amount = M('Member')->where($a_member_where)->getField($type);
			//今日转账记录
			$count_today_transfer_record_where['member_id'] = $this->mid;
			$count_today_transfer_record_where['addtime'] = array('egt',date('Y-m-d',NOW_TIME));
			$count_today_transfer_record_where['status'] = 1;
			$count_today_transfer_record = M('TransferRecord')->where($count_today_transfer_record_where)->count();
			if ($count_today_transfer_record)
			{
				$this->error('您今日已经进行过转账操作了,请明日再来哦.');
			}
			if ($total_amount < $amount)
			{
				$this->error('您的剩余'.$type_name.'不足,处理失败.');
			}
			if ($amount > 500)
			{
				$this->error('转账上限不能超过500哦.');
			}
			if (!$mid)
			{
				$this->error('该用户不存在,请查证.');
			}
			$res_a = M('Member')->where($a_member_where)->setDec($type,$amount);
			if (!$res_a)
			{
				$this->error('网络繁忙,请稍后再试.');
			}
			$res_b = M('Member')->where($b_member_where)->setInc($type,$amount);
			if (!$res_b)
			{
				system_log('会员转移'.$type_name.'失败','甲方已扣,乙方未收',10,'system');
			}
			$field = 'member_id,openid,nickname,point,predeposit,mobile';
			$a_user_info = M('Member')->where($a_member_where)->field($field)->find();
			$b_user_info = M('Member')->where($b_member_where)->field($field)->find();
			if ($a_user_info['openid'])
			{
				if ($type == 'point')
				{
					$data['touser'] = $a_user_info['openid'];
					$data['template_id'] = trim('C-ODq44vKBM88QaKAoXdeTF_bJ3dkqkrFqprjVTiDK0');
					$data['url'] = C('SiteUrl').U('Member/index');
					$data['data']['first']['value'] = '亲，您的泰鑫国际账号最新交易信息';
					$data['data']['first']['color'] = '#173177';
					$data['data']['time']['value'] = date('Y-m-d H:i',time());
					$data['data']['time']['color'] = '#173177';
					$data['data']['type']['value'] = '减少';
					$data['data']['type']['color'] = '#173177';
					$data['data']['Point']['value'] = $amount;
					$data['data']['Point']['color'] = '#173177';
					$data['data']['From']['value'] = '动态转出';
					$data['data']['From']['color'] = '#173177';
					$data['data']['remark']['value'] = '截止'.$data['data']['time']['value'].'，您的泰鑫国际动态为'.$a_user_info['point'].'积分。如有疑问请咨询微信894916947';
					$data['data']['remark']['color'] = '#173177';
					sendTemplateMsg($data);
				}elseif ($type == 'predeposit')
				{
					$b_user_info['mobile'] ? $b_mobile = $b_user_info['mobile'] : $b_mobile = '未知';
					$data['touser'] = $a_user_info['openid'];
					$data['template_id'] = trim('hJTtNJfTzW4x4lEe8SRS6Cp662mzoaeTQHzyU7PqoVE');
					$data['url'] = C('SiteUrl').U('Member/bill',array('bill_type'=>-1));
					$data['data']['first']['value'] = get_member_nickname($this->mid).',您静态转出成功！';
					$data['data']['first']['color'] = '#173177';
					$data['data']['keyword1']['value'] = price_format($amount).'元';
					$data['data']['keyword1']['color'] = '#173177';
					$data['data']['keyword2']['value'] = $b_mobile;
					$data['data']['keyword2']['color'] = '#173177';
					$data['data']['remark']['value'] = '进入泰鑫国际个人中心查看静态，有疑问联系客服微信894916947';
					$data['data']['remark']['color'] = '#173177';
					sendTemplateMsg($data);
					//生成账单流水
					$bill['member_id'] = $a_user_info['member_id'];
					$bill['bill_log'] = '静态转出';
					$bill['amount'] = price_format($amount);
					$bill['balance'] = $a_user_info['predeposit'];
					$bill['addtime'] = NOW_TIME;
					$bill['bill_type'] = -1;
					$bill['channel'] = -8;
					M('MemberBill')->add($bill);
				}
				$transfer_record['member_id'] = $a_user_info['member_id'];
				$transfer_record['b_member_id'] = $b_user_info['member_id'];
				$transfer_record['type'] = $type;
				$transfer_record['amount'] = $amount;
				$transfer_record['addtime'] = NOW_TIME;
				$transfer_record['status'] = 1;
				M('TransferRecord')->add($transfer_record);
			}
			if ($b_user_info['openid'])
			{
				if ($type == 'point')
				{
					$data['touser'] = $b_user_info['openid'];
					$data['template_id'] = trim('C-ODq44vKBM88QaKAoXdeTF_bJ3dkqkrFqprjVTiDK0');
					$data['url'] = C('SiteUrl').U('Member/index');
					$data['data']['first']['value'] = '亲，您的泰鑫国际账号最新交易信息';
					$data['data']['first']['color'] = '#173177';
					$data['data']['time']['value'] = date('Y-m-d H:i',time());
					$data['data']['time']['color'] = '#173177';
					$data['data']['type']['value'] = '增加';
					$data['data']['type']['color'] = '#173177';
					$data['data']['Point']['value'] = $amount;
					$data['data']['Point']['color'] = '#173177';
					$data['data']['From']['value'] = '动态转入';
					$data['data']['From']['color'] = '#173177';
					$data['data']['remark']['value'] = '截止'.$data['data']['time']['value'].'，您的泰鑫国际动态为'.$b_user_info['point'].'积分。如有疑问请咨询微信894916947';
					$data['data']['remark']['color'] = '#173177';
					sendTemplateMsg($data);
				}elseif ($type == 'predeposit')
				{
					$a_user_info['mobile'] ? $a_mobile = $a_user_info['mobile'] : $a_mobile = '未知';
					$data['touser'] = $b_user_info['openid'];
					$data['template_id'] = trim('hJTtNJfTzW4x4lEe8SRS6Cp662mzoaeTQHzyU7PqoVE');
					$data['url'] = C('SiteUrl').U('Member/bill',array('bill_type'=>-1));
					$data['data']['first']['value'] = '亲，'.get_member_nickname($this->mid).'给您转账了';
					$data['data']['first']['color'] = '#173177';
					$data['data']['keyword1']['value'] = price_format($amount).'元';
					$data['data']['keyword1']['color'] = '#173177';
					$data['data']['keyword2']['value'] = $a_mobile;
					$data['data']['keyword2']['color'] = '#173177';
					$data['data']['remark']['value'] = '进入泰鑫国际个人中心查看静态，有疑问联系客服微信894916947';
					$data['data']['remark']['color'] = '#173177';
					sendTemplateMsg($data);
					//生成账单流水
					$bill['member_id'] = $b_user_info['member_id'];
					$bill['bill_log'] = '静态转入';
					$bill['amount'] = price_format($amount);
					$bill['balance'] = $b_user_info['predeposit'];
					$bill['addtime'] = NOW_TIME;
					$bill['bill_type'] = 1;
					$bill['channel'] = 8;
					M('MemberBill')->add($bill);
				}
			}
			$this->success('操作成功.');
		}elseif (IS_GET)
		{
			$this->member_info = M('Member')->where($a_member_where)->field('point,predeposit')->find();
			$this->display();
		}
	}
}