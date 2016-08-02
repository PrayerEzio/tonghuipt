<?php
/**
 * 分销
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Toadmin\Controller;
use Think\Page;

class DistributionController extends GlobalController{
	public function _initialize()
	{
		parent::_initialize();
	}

	/**
	 * 公排管理
	 */
	public function indexBoard()
	{
		$where = array();
		$count = D('Board')->where($where)->count();
		$page = new Page($count,10);
		$list = D('Board')->relation(true)->where($where)->limit($page->firstRow.','.$page->listRows)->order('create_time')->select();
		$this->list = $list;
		$this->page = $page->show();
		$this->display();
	}

	public function detailBoard()
	{
		$where['board_id'] = intval($_GET['board_id']);
		$info = M('Board')->where($where)->find();
		$this->info = $info;
		$this->display();
	}

	public function curdBoard()
	{

	}

	/**
	 * 代理
	 */
	public function indexAgent()
	{
		$where = array();
		$count = D('Agent')->where($where)->count();
		$page = new Page($count,10);
		$list = D('Agent')->relation(true)->where($where)->limit($page->firstRow.','.$page->listRows)->order('create_time')->select();
		$this->list = $list;
		$this->page = $page->show();
		$this->display();
	}

	public function detailAgent()
	{
		$where['agent_id'] = intval($_GET['board_id']);
		$info = M('Agent')->where($where)->find();
		$this->info = $info;
		$this->display();
	}

}