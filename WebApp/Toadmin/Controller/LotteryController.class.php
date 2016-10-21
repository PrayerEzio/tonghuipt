<?php
/**
 * 抽奖
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
namespace Toadmin\Controller;
use Think\Page;
class LotteryController extends GlobalController {
	public function _initialize() 
	{
        parent::_initialize();
		$this->model = D('Lottery');
	}

	public function index()
	{
		$where = array();
		$count = $this->model->where($where)->count();
		$page = new Page($count,10);
		$list = $this->model->where($where)->order('lottery_start_time desc,lottery_end_time desc')->limit($page->firstRow.','.$page->listRows)->select();
		$this->list = $list;
		$this->display();
	}

	public function curd()
	{
		if (IS_POST)
		{
			$id = intval($_POST['id']);
			$data['lottery_title'] = trim($_POST['lottery_title']);
			$data['lottery_start_time'] = strtotime($_POST['lottery_start_time']);
			$data['lottery_end_time'] = strtotime($_POST['lottery_end_time']);
			$data['lottery_cycle_day'] = intval($_POST['lottery_cycle_day']);
			$data['lottery_cycle_times'] = intval($_POST['lottery_cycle_times']);
			$data['lottery_status'] = intval($_POST['lottery_status']);
			$data['lottery_cost_point'] = intval($_POST['lottery_cost_point']);
			$data['lottery_cost_money'] = floatval($_POST['lottery_cost_money']);
			$lotteryAward = $_POST['LotteryAward'];
			M()->startTrans();
			$where['lottery_id'] = $id;
			if ($id)
			{
				$res1 = $this->model->relation(true)->where($where)->save($data);
				$lottery_id = $id;
			}else {
				$res1 = $this->model->relation(true)->add($data);
				$lottery_id = $res1;
			}
			$res1 ? $e = 0 : $e = 1;
			$pool_key = 0;
			foreach ($lotteryAward as $key => $item)
			{
				$award_data = array_filter($item);
				if ($award_data['award_id'])
				{
					$t = M('LotteryAward')->save($award_data);
					$award_id = $award_data['award_id'];
				}else {
					$t = M('LotteryAward')->add($award_data);
					$award_id = $t;
				}
				if (!$t)
				{
					$e = 1;
				}
				//初始化奖池
				for ($i=0;$i<$item['award_stock'];)
				{
					$pool[$pool_key]['award_id'] = $award_id;
					$pool[$pool_key]['member_id'] = 0;
					$pool[$pool_key]['addr_id'] = 0;
					$pool[$pool_key]['addr_id'] = 0;
					$pool[$pool_key]['release_time'] = 0;
					$pool[$pool_key]['status'] = 1;
					$pool[$pool_key]['lottery_time'] = 0;
					$pool[$pool_key]['lottery_id'] = $lottery_id;
					$pool_key++;
				}
				$res2 = M('LotteryAwardPool')->addAll($pool);
			}
			if ($e)
			{
				M()->commit();
				$this->success('操作成功',U('list'));
			}else{
				M()->rollback();
				$this->error('操作失败,请重试.');
			}
		}elseif(IS_GET) {
			$id = intval($_GET['id']);
			$where['lottery_id'] = $id;
			$info = $this->model->relation(true)->where($where)->find();
			$this->info = $info;
			$this->display();
		}
	}

	public function delLottery()
	{
		//todo
	}
}