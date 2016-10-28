<?php
/**
 * 每日定时任务
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Crontab\Controller;
use Think\Controller;
class DayController extends BaseController
{
	public function __construct()
	{
		parent::__construct();
		//站点状态判断
		$web_stting['site_status'] = MSC('site_status');
		if($web_stting['site_status'] != 1){
			die;
		}
	}

	public function every()
	{
		$this->repaymentOfLoan();
		//$this->increaseInterest();
	}

	private function repaymentOfLoan()
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
							$count_parents_active_loan_where['member_id'] = $parents_member['member_id'];
							$count_parents_active_loan_where['status'] = 1;
							$count_parents_active_loan_where['active'] = 1;
							$count_parents_active_loan_where['parent_reward_status'] = 0;
							$count_parents_active_loan = M('LoanRecord')->where($count_parents_active_loan_where)->count();
							if ($p_reward && $count_parents_active_loan)
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
						//推送消息
						$open_id = M('Member')->where(array('member_id'=>$bill['member_id']))->getField('openid');
						if ($open_id)
						{
							$member_nickname = get_member_nickname($bill['member_id']);
							$data['touser'] = $open_id;
							$data['template_id'] = trim('O1byAyvnVv6dtj1wrwQiL9LdUS6Zb6S6E65APrUmf7I');
							$data['url'] = C('SiteUrl').U('Member/bill',array('bill_type'=>9));
							$data['data']['first']['value'] = $member_nickname.'您好,您的排单获得返利回馈.';
							$data['data']['first']['color'] = '#173177';
							$data['data']['keyword1']['value'] = $item['order_sn'];
							$data['data']['keyword1']['color'] = '#173177';
							$data['data']['keyword2']['value'] = price_format($loan_info['price']).'元';
							$data['data']['keyword2']['color'] = '#173177';
							$data['data']['keyword3']['value'] = date('Y年m月d日 H:i',time());
							$data['data']['keyword3']['color'] = '#173177';
							$data['data']['keyword4']['value'] =  price_format($bill['amount']).'元';
							$data['data']['keyword4']['color'] = '#173177';
							$data['data']['remark']['value'] = '感谢您的支持！';
							$data['data']['remark']['color'] = '#173177';
							sendTemplateMsg($data);
						}
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
	}

	//每日余额利息
	private function increaseInterest()
	{
		$member_list_where['predeposit'] = array('gt',0);
		$member_list_where['member_status'] = 1;
		$member_list_field = 'member_id,predeposit';
		$member_list = M('Member')->where($member_list_where)->field($member_list_field)->select();
		$interest_rate = MSC('interest_rate')/100;
		if (is_array($member_list) && $interest_rate)
		{
			foreach ($member_list as $key => $member)
			{
				$interest = round($member['predeposit']*$interest_rate,2);
				$res = M('Member')->where(array('member_id'=>$member['member_id']))->setInc('predeposit',$interest);
				if ($res)
				{
					//写入收入日志
					$bill['member_id'] = $member['member_id'];
					$bill['bill_log'] = '来自余额利息';
					$bill['amount'] = $interest;
					$bill['balance'] = M('Member')->where(array('member_id'=>$member['member_id']))->getField('predeposit');
					$bill['addtime'] = NOW_TIME;
					$bill['bill_type'] = 1;
					$bill['channel'] = 3;
					M('MemberBill')->add($bill);
				}else {
					$fail_member[] = $member;
				}
			}
			system_log('定时任务:每日余额利息任务','定时任务:每日余额利息任务完成.',0,'CrontabServer');
			if (!empty($fail_member_id))
			{
				//写入错误日志
				system_log('每日余额利息增加失败.',json_encode($fail_member),10,'CrontabServer');
			}
		}else {
			$reason = '';
			if (empty($member_list))
			{
				$reason .= '[没有获取到用户列表]';
			}
			if (empty($interest_rate))
			{
				$reason .= '[没有获取到利率]';
			}
			system_log('定时任务:每日余额利息任务','定时任务:每日余额利息任务没有执行,原因:'.$reason,1,'CrontabServer');
		}
	}
}