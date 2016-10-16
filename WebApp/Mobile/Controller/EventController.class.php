<?php
/**
 * 活动中心
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Muxiangdao\DesUtils;
use Think\Page;

class EventController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->check_login();
        //检查登录
        //session('wechat_openid',null);
        $this->m_info = M('Member')->where(array('member_id'=>$this->mid))->find();
		if (empty($this->m_info['openid']))
		{
			$this->getWechatInfo();
		}
		$this->lotteryModel = D('Lottery');
	}

	public function lottery()
	{
		$id = intval($_GET['id']);
		$info_where['lottery_id'] = $id;
		$info_where['lottery_status'] = 1;
		$info = $this->lotteryModel->relation(true)->where($info_where)->find();
		$this->info = $info;
		$record_list_where['member_id'] = array('neq',0);
		$record_list_where['lottery_id'] = $info['lottery_id'];
		$record_list = M('LotteryAwardPool')->where($record_list_where)->select();
		$this->record_list = $record_list;
		$this->display();
	}

	private function lotteryAuth($lottery_id)
	{
		$lottery_info_where['lottery_id'] = $lottery_id;
		$lottery_info_where['lottery_start_time'] = array('elt',NOW_TIME);
		$lottery_info_where['lottery_end_time'] = array('egt',NOW_TIME);
		$lottery_info_where['lottery_status'] = 1;
		$lottery_info = $this->lotteryModel->relation(true)->where($lottery_info_where)->find();
		if (empty($lottery_info))
		{
			return array(500,'抱歉,该活动尚未开始或已经结束.');
		}
		if ($lottery_info['agent_id_str'])
		{
			$agent_id_array = explode(',',$lottery_info['agent_id_str']);
			$agent_id = M('Member')->where(array('member_id'=>$this->mid,'member_status'=>1))->getField('agent_id');
			if (!in_array($agent_id,$agent_id_array))
			{
				return array(300,'抱歉,您还没有抽奖的资格.');
			}
		}
		if ($lottery_info['lottery_cycle_day'])
		{
			$cycle_pool_count_where['member_id'] = $this->mid;
			$cycle_pool_count_where['lottery_time'] = array('between',array(NOW_TIME-$lottery_info['lottery_cycle_day']*24*60*60,NOW_TIME));
			$cycle_pool_count = M('LotteryAwardPool')->where($cycle_pool_count_where)->count();
			if ($cycle_pool_count >= $lottery_info['lottery_cycle_times'])
			{
				return array(300,'抱歉,您已经没有抽奖次数啦.');
			}
		}
		//扣除积分和金额
		$cost_where['member_id'] = $this->mid;
		if ($lottery_info['lottery_cost_point'])
		{
			$user_point = M('Member')->where($cost_where)->getField('point');
			if ($user_point < $lottery_info['lottery_cost_point'])
			{
				return array(300,'抱歉,您的动态不足,无法参与抽奖.');
			}
			$res_cost_point = M('Member')->where($cost_where)->setDec('point',$lottery_info['lottery_cost_point']);
			if (!$res_cost_point)
			{
				return array(300,'网络繁忙,请稍后再试.');
			}
		}
		if ($lottery_info['lottery_cost_money'] != 0.00)
		{
			$user_predeposit = M('Member')->where($cost_where)->getField('predeposit');
			if ($user_predeposit < $lottery_info['lottery_cost_money'])
			{
				M('Member')->where($cost_where)->setInc('point',$lottery_info['lottery_cost_point']);
				return array(300,'抱歉,您的静态不足,无法参与抽奖.');
			}
			$res_cost_predeposit = M('Member')->where($cost_where)->setDec('predeposit',$lottery_info['lottery_cost_money']);
			if (!$res_cost_predeposit)
			{
				if ($res_cost_point)
				{
					M('Member')->where($cost_where)->setInc('point',$lottery_info['lottery_cost_point']);
				}
				return array(300,'网络繁忙,请稍后再试.');
			}
		}
	}

	public function ajaxLottery()
	{
			$lottery_id = intval($_GET['id']);
			$res = $this->lotteryAuth($lottery_id);
			if ($res)
			{
				if (empty($res[2]))
				{
					$res[2] = array();
				}
				json_return($res[0],$res[1],$res[2]);
			}
			$award_field = 'award_id,lottery_id,award_level,award_name,award_type,award_pic,award_msg,award_parameter,award_is_entity';
			$default_award_where['lottery_id'] = $lottery_id;
			$default_award_where['is_default'] = 1;
			$default_award = M('LotteryAward')->where($default_award_where)->field($award_field)->find();
			$pool_where['release_time'] = array('elt',NOW_TIME);
			$pool_where['status'] = 1;
			$pool_where['lottery_time'] = 0;
			$pool_where['member_id'] = 0;
			$pool = D('LotteryAwardPool')->where($pool_where)->find();
			if (empty($pool))
			{
				//写入pool池
				$pool_data['award_id'] = $default_award['award_id'];
				$pool_data['member_id'] = $this->mid;
				$pool_data['release_time'] = NOW_TIME;
				$pool_data['status'] = 0;
				$pool_data['lottery_time'] = NOW_TIME;
				$pool_data['lottery_id'] = $default_award['lottery_id'];
				M('LotteryAwardPool')->add($pool_data);
				json_return(200,'success',$default_award);
			}
			$seed = rand(1,100);
			if ($seed < 50)//MSC();
			{
				//轮空 获得默认奖品 写入pool池
				$pool_data['award_id'] = $default_award['award_id'];
				$pool_data['member_id'] = $this->mid;
				$pool_data['release_time'] = NOW_TIME;
				$pool_data['status'] = 0;
				$pool_data['lottery_time'] = NOW_TIME;
				$pool_data['lottery_id'] = $default_award['lottery_id'];
				M('LotteryAwardPool')->add($pool_data);
				json_return(200,'success',$default_award);
			}
			$award_info_where['award_id'] = $pool['award_id'];
			$award_info = D('LotteryAward')->where($award_info_where)->field($award_field)->find();
			if ($award_info)
			{
				$pool_data['member_id'] = $this->mid;
				$pool_data['status'] = 0;
				$pool_data['lottery_time'] = NOW_TIME;
				$res_where['id'] = $pool['id'];
				$res = M('LotteryAwardPool')->where($res_where)->save($pool_data);
				if ($res)
				{
					json_return(200,'success',$award_info);
				}
			}
			//出错 获得默认奖品 写入pool池
			$pool_data['award_id'] = $default_award['award_id'];
			$pool_data['member_id'] = $this->mid;
			$pool_data['release_time'] = NOW_TIME;
			$pool_data['status'] = 0;
			$pool_data['lottery_time'] = NOW_TIME;
			$pool_data['lottery_id'] = $default_award['lottery_id'];
			M('LotteryAwardPool')->add($pool_data);
			json_return(200,'success',$default_award);
	}
}