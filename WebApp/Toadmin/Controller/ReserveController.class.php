<?php
/**
 * 预订
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
namespace Toadmin\Controller;
use Think\Page;
class ReserveController extends GlobalController {
	public function _initialize() 
	{
        parent::_initialize();
		$this->model = D('Reserve');
	}
	public function index(){
		$map = array();
		$add_time_from = trim($_GET['add_time_from']) ? strtotime(trim($_GET['add_time_from'])) : 0;
		$add_time_to = trim($_GET['add_time_to']) ? strtotime(trim($_GET['add_time_to']))+86400 : NOW_TIME;
		$map['add_time'] = array('between',array($add_time_from,$add_time_to));
		$count = $this->model->where($map)->count();
		$page = new Page($count,10);
		$list = $this->model->relation(true)->where($map)->order('add_time desc')->select();
		$this->assign('list',$list);
		$this->assign('search',$_GET);
		$this->display();
	}
	public function detail(){
		if (IS_POST) {
			$id = intval($_POST['id']);
			$order_state = intval($_POST['order_state']);
			$res = $this->model->where(array('id'=>$id))->setField('order_state',$order_state);
			if ($res) {
				$this->success('处理预订成功',U('index'));
			}else {
				$this->error('处理订单失败');
			}
		}elseif (IS_GET) {
			$id = intval($_GET['id']);
			$info = $this->model->where(array('id'=>$id))->find();
			if (!empty($info)) {
				$this->assign('info',$info);
				$this->display();
			}else {
				$this->error('没有找到相关信息');
			}
		}
	}
}