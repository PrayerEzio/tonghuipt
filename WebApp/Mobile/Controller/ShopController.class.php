<?php
/**
 * 商城
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Mobile\Controller\BaseController;
use Think\Page;
use Muxiangdao\DesUtils;
class ShopController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->model = M('Goods');
	}

	/**
	 * 商城列表页
	 */
	public function index()
	{
		$gc_id = intval($_GET['gc']);
		if ($gc_id)
		{
			$where['gc_id'] = $gc_id;
		}
		$where['goods_status'] = 1;
		$count = $this->model->where($where)->count();
		$page = new Page($count,10);
		$order = 'goods_sort desc';
		$list = $this->model->where($where)->order($order)->limit($page->firstRow.','.$page->listRows)->select();
		$this->list = $list;
		$this->page = $page->show();
		$this->display();
	}

	public function detail()
	{
		$goods_id = intval($_GET['id']);
		$where['goods_id'] = $goods_id;
		$goods_info = $this->model->where($where)->find();
		$this->info = $goods_info;
		$this->display();
	}
}