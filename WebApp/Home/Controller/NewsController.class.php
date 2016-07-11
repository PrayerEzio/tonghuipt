<?php
/**
 * 新闻中心
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Home\Controller;
use Home\Controller\BaseController;
use Think\Page;
class NewsController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->model = M('Article');
	}
	public function index(){
		$ac_id = I('cate',0,'int');
		$pid = M('ArticleClass')->where(array('ac_id'=>$ac_id))->getField('ac_parent_id');
		if ($pid) {
			$this->ac_list = M('ArticleClass')->where(array('ac_parent_id'=>$pid))->order('ac_sort desc')->select();
		}else {
			$this->ac_list = M('ArticleClass')->where(array('ac_parent_id'=>$ac_id))->order('ac_sort desc')->select();
		}
		$ac_list = M('ArticleClass')->order('ac_sort desc')->select();
		$ac_list = getChildsId($ac_list, $ac_id, 'ac_id', 'ac_parent_id');
		if (is_array($ac_list)) {
			$ac_list_str = $ac_id.',';
			foreach ($ac_list as $key => $val){
				$ac_list_str .= $val.',';
			}
			$ac_list_str = substr($ac_list_str, 0, -1);
		}
		$where['ac_id'] = array('IN',$ac_list_str);
		$where['article_show'] = 1;
		$count = $this->model->where($where)->count();
		$page = new Page($count,10);
		$list = $this->model->where($where)->limit($page->firstRow.','.$page->listRows)->order('article_sort desc,article_time desc')->select();
		$this->list = $list;
		$this->page = $page->show();
		$this->display();
	}
	public function detail(){
		$id = intval($_GET['id']);
		$info = $this->model->where(array('article_id'=>$id))->find();
		$this->info = $info;
		$this->display();
	}
}