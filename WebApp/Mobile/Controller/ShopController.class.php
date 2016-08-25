<?php
/**
 * 商城
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Think\Page;
use Muxiangdao\DesUtils;
class ShopController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->model = D('Goods');
	}

	/**
	 * 商城列表页
	 */
	public function index()
	{
		$order = 'goods_sort desc';
		$where['goods_status'] = 1;
		$gc_id = intval($_GET['gc']);
		if (!empty($gc_id))
		{
			$where['gc_id'] = $gc_id;
		}
		$count = $this->model->where($where)->count();
		$page = new Page($count,10);
		$page->rollPage = 3;
		$page->setConfig('prev','上一页');
		$page->setConfig('next','下一页');
		$page->setConfig('theme','%UP_PAGE% %LINK_PAGE% %DOWN_PAGE%');
		$list = $this->model->where($where)->order($order)->limit($page->firstRow.','.$page->listRows)->select();
		$ad_where['is_use'] = 1;
		$this->banner = M('AdvPosition')->where($ad_where)->order('ap_sort desc')->select();
		$this->list = $list;
		$this->page = $page->show();
		$this->display();
	}

	public function point()
	{
		$order = 'goods_sort desc';
		$where['member_id'] = array('neq',0);
		$where['goods_status'] = 1;
		$gc_id = intval($_GET['gc']);
		if (!empty($gc_id))
		{
			$where['gc_id'] = $gc_id;
		}
		$count = $this->model->where($where)->count();
		$page = new Page($count,10);
		$page->rollPage = 3;
		$page->setConfig('prev','上一页');
		$page->setConfig('next','下一页');
		$page->setConfig('theme','%UP_PAGE% %LINK_PAGE% %DOWN_PAGE%');
		$list = $this->model->where($where)->order($order)->limit($page->firstRow.','.$page->listRows)->select();
		$ad_where['is_use'] = 1;
		$this->banner = M('AdvPosition')->where($ad_where)->order('ap_sort desc')->select();
		$this->list = $list;
		$this->page = $page->show();
		$this->display();
	}

	/**
	 * 商品分类页
	 */
	public function category()
	{
		//$where['gc_id'] = array('neq',47);
		$where = array();
		$list = M('GoodsClass')->where($where)->order('gc_sort desc')->select();
		$list = unlimitedForLayer($list,'child','gc_parent_id','gc_id');
		$this->list = $list;
		$this->display();
	}

	public function ajaxGetGoodsList()
	{
		if (IS_AJAX)
		{
			$order = 'goods_sort desc';
			$where['goods_status'] = 1;
			$gc_id = intval($_POST['gc']);
			$page = intval($_POST['page']);
			$limit = intval($_POST['limit']);
			if (!$page)
			{
				$page = 1;
			}
			if ($gc_id)
			{
				$where['gc_id'] = $gc_id;
			}
			if (!$limit)
			{
				$limit = 10;
			}
			$fields = 'goods_id,goods_pic,goods_sales,goods_name,goods_price';
			$count = $this->model->where($where)->count();
			$list = $this->model->where($where)->order($order)->field($fields)->limit(($page-1)*$limit.','.$limit)->select();
			json_return(1,'success',$list);die;
		}
	}

	public function detail()
	{
		$m_info = M('Member')->where(array('member_id'=>$this->mid))->find();
		if (empty($m_info['openid']))
		{
			$this->getWechatInfo();
		}
		$goods_id = intval($_GET['id']);
		$where['goods_id'] = $goods_id;
		$goods_info = $this->model->relation(true)->where($where)->find();
		if (empty($goods_info))
		{
			$this->error('没有找到相关信息');
		}
		$this->info = $goods_info;
		//JS-SDK
		$signPackage = wx_js_sdk();
		$this->assign('signPackage',$signPackage);
		$shareConfig['title'] = $goods_info['goods_name'];
		$shareConfig['desc'] = $goods_info['goods_desc'];
		$shareConfig['linkUrl'] = C('SiteUrl').U('',I());
		$shareConfig['imgUrl'] = C('SiteUrl').'/Uploads/'.$goods_info['goods_pic'];
		$this->shareConfig = $shareConfig;
		$this->display();
	}

	public function collectionGoods()
	{
		$Model = M('Favorite');
		if (IS_AJAX)
		{
			$goods_id = intval($_POST['goods_id']);
			$uid = $this->mid;
			if (empty($goods_id))
			{
				json_return(-2,'商品id不能为空');die;
			}
			if (empty($uid))
			{
				json_return(-1,'请先登陆');die;
			}
			$data['goods_id'] = $goods_id;
			$data['member_id'] = $uid;
			$count = $Model->where($data)->count();
			if ($count)
			{
				$res = $Model->where($data)->delete();
				if ($res)
				{
					json_return(1,'取消收藏成功');die;
				}else {
					json_return(0,'取消收藏失败,请重试');die;
				}
			}else {
				$data['add_time'] = time();
				$res = $Model->add($data);
				if ($res)
				{
					json_return(1,'收藏成功');die;
				}else {
					json_return(0,'收藏失败,请重试');die;
				}
			}
		}
	}
}