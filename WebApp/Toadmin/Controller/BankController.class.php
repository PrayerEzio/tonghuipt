<?php
/**
 * 银行
 * @copyright  Copyright (c) 2014-2015 muxiangdao-com Inc.(http://www.muxiangdao.com)
 * @license    http://www.muxiangdao.com
 * @link       http://www.muxiangdao.com
 * @author     muxiangdao-com Team Prayer (283386295@qq.com)
 */
namespace Toadmin\Controller;
use Think\Page;
class BankController extends GlobalController {
	public function _initialize() 
	{
        parent::_initialize();
		$this->model = D('Bank');
	}
	public function index(){
		if (IS_POST) {
			if (!empty($_POST['del_id'])) {
				$del_ids = '';
				foreach ($_POST['del_id'] as $bank_id){
					$del_ids .= $bank_id.',';
				}
				$del_ids = substr($del_ids, 0,-1);
				$res = $this->model->where(array('bank_id'=>array('IN',$del_ids)))->delete();
				if ($res) {
					$this->success('删除成功');
				}else {
					$this->error('删除失败');
				}
			}else {
				$this->error("请选择要操作的对象");
			}
		}elseif (IS_GET){
			$where = array();
			if (trim($_GET['bank_name'])) {
				$where['bank_name'] = array('LIKE','%'.str_rp(trim($_GET['bank_name'])).'%');
			}
			$count = $this->model->where($where)->count();
			$page = new Page($count,10);
			$list = $this->model->where($where)->limit($page->firstRow.','.$page->listRows)->limit('bank_sort desc')->select();
			$this->list = $list;
			$this->page = $page->show();
			$this->search = $_GET;
			$this->display();
		}
	}
	public function curd(){
		if (IS_POST) {
			$id = intval($_POST['bank_id']);
			$data['bank_name'] = str_rp(trim($_POST['bank_name']));
			$data['bank_tel'] = str_rp(trim($_POST['bank_tel']));
			$data['bank_sort'] = intval($_POST['bank_sort']);
			$data['bank_status'] = intval($_POST['bank_status']);
			if ($id) {
				$res = $this->model->where(array('bank_id'=>$id))->save($data);
			}else {
				$res = $this->model->add($data);
			}
			if ($res) {
				$this->success('操作成功',U('index'));
			}else {
				$this->error('操作失败');
			}
		}elseif (IS_GET){
			$id = intval($_GET['id']);
			if ($id) {
				$this->info = $this->model->where(array('bank_id'=>$id))->find();
			}
			$this->display();
		}
	}
	//删除
	public function bank_del(){
		$where['bank_id'] = intval($_GET['id']);
		$res = $this->model->where($where)->delete();
		if ($res) {
			$this->success('删除成功');
		}else {
			$this->error('删除失败');
		}
	}
	//在线编辑	
	public function ajax()
	{
		$id = intval($_GET['id']);
		switch(trim($_GET['branch']))
		{
			case 'bank_sort':
			$this->model->where(array('bank_id'=>$id))->setField($_GET['column'],intval($_GET['value']));
			break;
			case 'bank_name':
			$this->model->where(array('bank_id'=>$id))->setField($_GET['column'],trim($_GET['value']));
			break;	
			case 'case_sort':
			$this->model->where(array('bank_id'=>$id))->setField($_GET['column'],intval($_GET['value']));
			break;					
		}
	}
}