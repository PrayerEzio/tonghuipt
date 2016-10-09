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
class DayController extends BaseController{
	public function __construct(){
		parent::__construct();
	}

	public function every()
	{
		die;
		$this->increaseInterest();
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