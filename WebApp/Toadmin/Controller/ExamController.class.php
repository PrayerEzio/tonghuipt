<?php
/**
 * 考试
 * @copyright  Copyright (c) 2014-2015 muxiangdao-com Inc.(http://www.muxiangdao.com)
 * @license    http://www.muxiangdao.com
 * @link       http://www.muxiangdao.com
 * @author     muxiangdao-com Team Prayer (283386295@qq.com)
 */
namespace Toadmin\Controller;
use Think\Page;
class ExamController extends GlobalController {
	public function _initialize() 
	{
        parent::_initialize();
		$this->model = D('Exam');
	}
	public function index(){
		if (IS_POST) {
			if (!empty($_POST['del_id'])) {
				$del_ids = '';
				foreach ($_POST['del_id'] as $exam_id){
					$del_ids .= $exam_id.',';
				}
				$del_ids = substr($del_ids, 0,-1);
				$res = $this->model->where(array('exam_id'=>array('IN',$del_ids)))->delete();
				if ($res) {
					$this->success('删除成功');
				}else {
					$this->error('删除失败');
				}
			}
		}elseif (IS_GET){
			$where = array();
			if (trim($_GET['exam_question'])) {
				$where['exam_question'] = array('LIKE','%'.str_rp(trim($_GET['exam_question'])).'%');
			}
			$count = $this->model->where($where)->count();
			$page = new Page($count,10);
			$list = $this->model->where($where)->limit($page->firstRow.','.$page->listRows)->limit('exam_sort desc')->select();
			$this->list = $list;
			$this->page = $page->show();
			$this->search = $_GET;
			$this->display();
		}
	}
	public function curd(){
		if (IS_POST) {
			$id = intval($_POST['exam_id']);
			$data['exam_question'] = str_rp(trim($_POST['exam_question']));
			$data['exam_option_a'] = str_rp(trim($_POST['exam_option_a']));
			$data['exam_option_b'] = str_rp(trim($_POST['exam_option_b']));
			$data['exam_option_c'] = str_rp(trim($_POST['exam_option_c']));
			$data['exam_option_d'] = str_rp(trim($_POST['exam_option_d']));
			$data['exam_answer'] = str_rp(trim($_POST['exam_answer']));
			$data['exam_sort'] = intval($_POST['exam_sort']);
			$data['exam_score'] = intval($_POST['exam_score']);
			if ($id) {
				$res = $this->model->where(array('exam_id'=>$id))->save($data);
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
				$this->info = $this->model->where(array('exam_id'=>$id))->find();
			}
			$this->display();
		}
	}
	//删除
	public function exam_del(){
		$where['exam_id'] = intval($_GET['id']);
		$res = $this->model->where($where)->delete();
		if ($res) {
			$this->success('删除成功');
		}else {
			$this->error('删除失败');
		}
	}
	//在线编辑	
	public function ajax(){
		$id = intval($_GET['id']);
		switch(trim($_GET['branch']))
		{
			case 'exam_sort':
			$this->model->where(array('exam_id'=>$id))->setField($_GET['column'],intval($_GET['value']));
			break;
			case 'exam_question':
			$this->model->where(array('exam_id'=>$id))->setField($_GET['column'],trim($_GET['value']));
			break;
			case 'exam_option':
			$this->model->where(array('exam_id'=>$id))->setField($_GET['column'],trim($_GET['value']));
			break;
		}
	}
}