<?php
/**
 * 提现
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
namespace Toadmin\Controller;
use Think\Page;
class WithdrawController extends GlobalController {
	public function _initialize() 
	{
        parent::_initialize();
		$this->model = D('Withdraw');
	}
	public function index(){
		$where = array();
		$count = $this->model->where($where)->count();
		$page = new Page($count,10);
		$list = $this->model->relation(true)->where($where)->limit($page->firstRow.','.$page->listRows)->order('wd_time desc')->select();
		$this->assign('list',$list);
		$this->assign('page',$page);
		$this->assign('search',$_GET);
		$this->assign('title','提现管理');
		$this->display();
	}
	public function ajaxHandleWithdraw(){
		if (IS_AJAX) {
			$id = intval($_POST['id']);
			$status = intval($_POST['status']);
			if ($id) {
				$data = $this->model->where(array('wd_id'=>$id))->find();
				if ($data['status'] != 0) {
					$result['code'] = 300;
					die;
				}
				if ($status == 1) {
					//同意提现
// 					D('Member')->where(array('member_id'=>$data['member_id'],'status'=>1))->setDec('frozen',$data['amount']);
					//生成账单流水
					$bill['member_id'] = $data['member_id'];
					$bill['bill_log'] = '提现成功';
					$bill['amount'] = $data['amount'];
					$bill['balance'] = M('Member')->where(array('member_id'=>$data['member_id']))->getField('predeposit');
					$bill['addtime'] = NOW_TIME;
					$bill['bill_type'] = -1;
				}elseif ($status == -1){
					//拒绝提现
					D('Member')->where(array('member_id'=>$data['member_id'],'status'=>1))->setInc('point',$data['wd_amount']*MSC('point_exchange_rate'));
// 					D('Member')->where(array('member_id'=>$data['member_id'],'status'=>1))->setInc('predeposit',$data['amount']);
// 					D('Member')->where(array('member_id'=>$data['member_id'],'status'=>1))->setDec('frozen',$data['amount']);
					//生成账单流水
					$bill['member_id'] = $data['member_id'];
					$bill['bill_log'] = '提现失败';
					$bill['amount'] = $data['amount'];
					$bill['balance'] = M('Member')->where(array('member_id'=>$data['member_id']))->getField('predeposit');
					$bill['addtime'] = NOW_TIME;
					$bill['bill_type'] = 2;
				}
				M('MemberBill')->add($bill);
				$res = $this->model->where(array('id'=>$id))->setField('status',$status);
				if ($res) {
					$result['code'] = 200;
				}else {
					$result['code'] = 300;
				}
			}else {
				$result['code'] = 404;
			}
		}else {
			$result['code'] = 500;
		}
		echo json_encode($result);
	}
}