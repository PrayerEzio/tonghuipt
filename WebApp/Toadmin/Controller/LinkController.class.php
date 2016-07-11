<?php
/**
 * 友情链接
 * @copyright  Copyright (c) 2014-2015 muxiangdao-com Inc.(http://www.muxiangdao.com)
 * @license    http://www.muxiangdao.com
 * @link       http://www.muxiangdao.com
 * @author     muxiangdao-com Team Prayer (283386295@qq.com)
 */
namespace Toadmin\Controller;
use Think\Page;
class LinkController extends GlobalController {
	public function _initialize() 
	{
        parent::_initialize();
		$this->linkMod = M('Link');
	}
	//友情链接列表页
	public function index()
	{
		if (IS_POST) {
			$ids = $_POST['del_id'];
			foreach ($ids as $key => $val){
				$this->linkMod->where(array('id'=>$val))->delete();
			}
			$this->success('友情链接删除成功');
		}elseif (IS_GET){
			$count = $this->linkMod->count();
			$page = new Page($count,10);
			$list = $this->linkMod->order('sort desc')->limit($page->firstRow.','.$page->listRows)->select();
			$this->assign('list',$list);
			$this->assign('title','友情链接');
			$this->assign('page',$page->show());
			$this->display();
		}
	}
	//友情链接curd
	public function curdLink(){
		if (IS_AJAX) {
			echo '错误!';
		}elseif (IS_POST) {
			$id = intval($_POST['id']);
			$data['title'] = str_rp(trim($_POST['title']));
			$data['url'] = str_rp(trim($_POST['url']));
			$data['sort'] = intval($_POST['sort']);
			$data['status'] = intval($_POST['status']);
			if ($id) {
				$res = $this->linkMod->where(array('id'=>$id))->save($data);
			}else {
				$res = $this->linkMod->add($data);
			}
			if ($res) {
				$this->success('操作成功',U('index'));
			}else {
				$this->error('操作失败');
			}
		}elseif (IS_GET){
			$id = intval($_GET['id']);
			$info = $this->linkMod->where(array('id'=>$id))->find();
			$this->assign('info',$info);
			$this->display();
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