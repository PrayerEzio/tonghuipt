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
				if ($count)
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
						$data['data']['first']['value'] = '亲，您的积分已到账！';
						$data['data']['first']['color'] = '#173177';
						$data['data']['time']['value'] = date('Y年m月d日 H:i',time());
						$data['data']['time']['color'] = '#173177';
						$data['data']['org']['value'] = '通汇大商圈';
						$data['data']['org']['color'] = '#173177';
						$data['data']['type']['value'] = '个人消费';
						$data['data']['type']['color'] = '#173177';
						$data['data']['money']['value'] = price_format($order['order_amount']).'元';
						$data['data']['money']['color'] = '#173177';
						$data['data']['point']['value'] = $order['order_points'].'积分';
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
			$order_count = M('Order')->where($order_count_where)->count();
			$bill_count_where['bill_type'] = -1;
			$bill_count_where['channel'] = -2;
			$bill_count_where['member_id'] = $this->mid;
			$bill_count_where['addtime'] = array('gt',strtotime(date('Y-m-d',time())));
			$bill_count = M('MemberBill')->where($bill_count_where)->find();
			if ($order_count || $bill_count)
			{
				$this->error('您今日已经没有提现次数了,请明天再来.');die;
			}
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
				'mch_appid' => Wx_C('wx_appid'),
				'mchid' => Wx_C('wx_mch_id'),
				'nonce_str' => $wxPay->createNoncestr(32),
				'partner_trade_no' => $order_sn,
				'openid' => $openid,
				'check_name' => 'NO_CHECK',
				'amount' => intval($amount*100),
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
				$data['touser'] = $openid;
				$data['template_id'] = trim('RyeUJ1L4zRD4DzQ_lQGuUlYldAmBudVZ3fF6R0zY_w4');
				$data['url'] = C('SiteUrl').U('Member/bill',array('bill_type'=>-1));
				$data['data']['first']['value'] = get_member_nickname($this->mid).'，恭喜您提现成功！';
				$data['data']['first']['color'] = '#173177';
				$data['data']['keyword1']['value'] = price_format($amount);
				$data['data']['keyword1']['color'] = '#173177';
				$data['data']['keyword2']['value'] = date('Y-m-d H:i',time());
				$data['data']['keyword2']['color'] = '#173177';
				$data['data']['remark']['value'] = '小额系统自提，大额请联系客服：894916947';
				$data['data']['remark']['color'] = '#173177';
				sendTemplateMsg($data);
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
				M('Member')->where(array('member_id'=>$data['member_id']))->setInc('predeposit',$data['order_amount']);
				$this->error('提现失败,请稍后再试.');
			}
		}elseif (IS_GET)
		{
			$this->member_info = M('Member')->where(array('member_id'=>$this->mid))->field('predeposit')->find();
			$this->display();
		}
	}

	public function addGoods()
	{
		$merchant_status = M('Member')->where(array('member_id'=>$this->mid))->getField('merchant_status');
		if (empty($merchant_status))
		{
			$this->error('抱歉,您的权限等级不够.');
		}
		if (IS_POST)
		{

		}elseif(IS_GET){
			$where = array();
			$this->goods_class = M('GoodsClass')->where($where)->order('gc_sort')->select();
			$this->display();
		}
	}
}