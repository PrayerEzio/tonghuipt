<?php
/**
 * 测试
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Think\Image;
use Think\Controller;

class TestController extends Controller{
	public function __construct(){
		parent::__construct();
		/*$this->getWechatInfo();
		if ($this->mid != 36 && $this->mid != 37)
		{
			redirect(U('Member/index'));
		}*/
		$this->mid = 36;
	}

	public function test()
	{
		$list_where['status'] = 1;
		$list_where['active'] = 1;
		$list = M('LoanRecord')->where($list_where)->select();
		foreach ($list as $key => $item)
		{
			$loan_info = M('Loan')->where(array('loan_id'=>$item['loan_id']))->find();
			if ($loan_info['cycle'] > $item['execution_times'])
			{
				$res1_where['id'] = $item['id'];
				$res1_data['execution_times'] = $item['execution_times']+1;
				$is_pay = 1;
				$parent_reward_status = M('LoanRecord')->where(array('id'=>$item['id'],'parent_reward_status'=>0))->getField('parent_reward_status');
				if ($res1_data['execution_times'] >= $loan_info['cycle'] && !$parent_reward_status)
				{
					//发放推荐人奖励
					$red_packet_where['reward_type'] = 'loan';
					$red_packet = M('RedPacket')->where($red_packet_where)->order('level')->select();
					$max = M('RedPacket')->where($red_packet_where)->max('level');
					$parents_member_list = getParentsMember($item['member_id'],'*',$max);
					$member_nickname = get_member_nickname($item['member_id']);
					$res_change_parent_reward_status = M('LoanRecord')->where(array('id'=>$item['id'],'parent_reward_status'=>0))->setField('parent_reward_status',1);
					if ($res_change_parent_reward_status)
					{
						foreach ($parents_member_list as $k => $parents_member)
						{
							$member_level_ch = ch_num($k+1);
							$p_reward = $red_packet[$k]['reward_price']/100*$loan_info['price'];
							if ($p_reward)
							{

								$res_p_reward = M('Member')->where(array('member_id'=>$parents_member['member_id']))->setInc('predeposit',$p_reward);
								if ($res_p_reward){
									$bill['member_id'] = $parents_member['member_id'];
									$bill['bill_log'] = '来自'.$member_level_ch.'级会员-'.$member_nickname.'的动态推荐奖收益';
									$bill['amount'] = $p_reward;
									$bill['balance'] = M('Member')->where(array('member_id'=>$parents_member['member_id']))->getField('predeposit');
									$bill['addtime'] = NOW_TIME;
									$bill['bill_type'] = 1;
									$bill['channel'] = 10;
									M('MemberBill')->add($bill);
								}else {
									//写入报错日志
									system_log('贷款推荐奖发放失败','LoanRecord:'.$item['id'].'的父级member_id:'.$parents_member['member_id'].'没有发放成功',10,'CrontabServer');
								}
							}
						}
					}
					$res1_data['active'] = 0;
					$count_other_active_where['member_id'] = $item['member_id'];
					$count_other_active_where['id'] = array('neq',$item['id']);
					$count_other_active_where['active'] = 1;
					$count_other_active = M('LoanRecord')->where($count_other_active_where)->count();
					$is_pay = $count_other_active;
				}
				if ($is_pay)
				{
					M()->startTrans();
					$res1 = M('LoanRecord')->where($res1_where)->save($res1_data);
					unset($res1_data);
					unset($res1_where);
					$res2_where['member_id'] = $item['member_id'];
					$res2_where['loan_status'] = 1;
					$res2_where['member_status'] = 1;
					$res2 = M('Member')->where($res2_where)->setInc('predeposit',$loan_info['daily_refund']);
					unset($res2_where);
					if ($res1 && $res2)
					{
						M()->commit();
						//写入收入日志
						$bill['member_id'] = $item['member_id'];
						$bill['bill_log'] = '来自排单返款';
						$bill['amount'] = $loan_info['daily_refund'];
						$bill['balance'] = M('Member')->where(array('member_id'=>$item['member_id']))->getField('predeposit');
						$bill['addtime'] = NOW_TIME;
						$bill['bill_type'] = 1;
						$bill['channel'] = 9;
						M('MemberBill')->add($bill);
					}else {
						M()->rollback();
						//写入报错日志
						system_log('贷款偿还失败',$item['id'].'事务回滚$res1='.$res1.'&$res2='.$res2,10,'CrontabServer');
					}
				}
			}
			unset($loan_info);
		}
		system_log('定时任务:每日贷款偿还(排单)任务','定时任务:每日贷款偿还(排单)任务.',0,'CrontabServer');
		p('success');
	}

	public function thumb()
	{
		$image = new Image();
		$goods_pic_list = M('Goods')->field('goods_id,goods_pic')->order('goods_id desc')->select();
		foreach ($goods_pic_list as $key => $item)
		{
			if (!empty($item['goods_pic']))
			{
				$pic_url = './Uploads/'.$item['goods_pic'];
				$image->open($pic_url);
				$image->thumb(200, 250)->save('./Uploads/'.$item['goods_pic']);
				p($item['goods_id'].':'.$pic_url);
			}else {
				p($item['goods_id'].':empty pic');
			}
		}
	}

	public function company_pay(){
		die;
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
		die;
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
			'send_name' => '泰鑫国际',
			're_openid' => $openid,
			'total_amount' => intval($amount*100),
			'total_num' => 1,
			'wishing' => '红包祝福语',
			'client_ip' => get_client_ip(),
			'act_name' => '泰鑫国际提现测试',
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