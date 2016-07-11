<?php
/**
 * 全站搜索
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Home\Controller;
use Home\Controller\BaseController;
use Common\Lib\Cart\Cart;
use Think\Page;
class SearchController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->model = M('Search');
	}
	public function index(){
		$word = trim($_GET['word']);
		$condition = array('like','%'.$word.'%');
		$where['title'] = $condition;
		$where['keywords'] = $condition;
		$where['description'] = $condition;
		$where['remark'] = $condition;
		$where['tag'] = $condition;
		$where['_logic'] = 'or';
		$map['_complex'] = $where;
		if (trim($_GET['type'])) {
			$map['type'] = trim($_GET['type']);
		}
		$count = $this->model->where($map)->count();
		$page = new Page($count,10);
		$list = $this->model->where($map)->limit($page->firstRow.','.$page->listRows)->order('sort desc,addtime desc')->select();
		$this->list = $list;
		$this->page = $page->show();
		$this->display();
	}
}