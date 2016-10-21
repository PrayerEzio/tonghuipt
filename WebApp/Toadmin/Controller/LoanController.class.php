<?php
/**
 * 排单
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
namespace Toadmin\Controller;
use Think\Page;
class LoanController extends GlobalController {
	public function _initialize() 
	{
        parent::_initialize();
		$this->model = D('LoanRecord');
	}		
			
	//管理
	public function index()
	{
		$map = array();
		$member_id = trim($_GET['member_id']);
		if($member_id)$map['member_id'] = array('eq',$member_id);
		$totalRows = $this->model->where($map)->count();
		$page = new Page($totalRows,10);
		$list = $this->model->where($map)->limit($page->firstRow.','.$page->listRows)->order('create_time desc')->select();
		$this->assign('list',$list);
		$this->assign('search',$_GET);
		$this->assign('page_show',$page->show());
		$this->display();
	}
}