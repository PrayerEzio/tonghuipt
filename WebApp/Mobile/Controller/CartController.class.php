<?php
/**
 * 购物车
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Mobile\Controller\BaseController;
use Common\Lib\Cart\Cart;
class CartController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->check_login();
		$this->m_info = M('Member')->where('member_id='.$this->mid)->find();
		if (empty($this->m_info['openid']))
		{
			$this->getWechatInfo();
		}
		$this->model = D('Goods');
		$this->Cart = new Cart();
	}
	/**
	 * 购物车
	 */
	public function index(){
		if (IS_POST) {
			;
		}elseif (IS_GET) {
			$list = $this->Cart->getList();
			$field = 'goods_id,member_id,goods_name,goods_price,goods_mktprice,goods_pic,goods_point,cost_point,freight,goods_storage';
			$i = 0;
			$cart_list = array();
			foreach ($list as $key => $item)
			{
				foreach ($item as $k => $value)
				{
					$list[$key][$k]['Goods'] = M('Goods')->where(array('goods_id'=>$value['id'],'goods_status'=>1))->field($field)->find();
					if ($item['spec_id'])
					{
						$list[$key][$k]['GoodsSpec'] = M('GoodsSpec')->where(array('spec_id'=>$value['spec_id']))->find();
					}
					$cart_list[$i] = $list[$key][$k];
					$i++;
				}
			}
			$this->list = $cart_list;
			$this->display();
		}
	}
	/**
	 * 加入购物车
	 */
	public function addCart(){
		if (IS_AJAX) {
			$spec_id = intval($_POST['spec_id']);
			$where['goods_id'] = intval($_POST['goods_id']);
			$where['goods_status'] = 1;
			$num = intval($_POST['num']);
			if ($num) {
				$goods = $this->model->where($where)->find();
				if ($goods['goods_storage'] >= $num) {
					$this->Cart = new Cart();
					$this->Cart->addItem($goods['goods_id'], $goods['goods_name'], $goods['goods_price'], $num, $goods['goods_pic'], $spec_id);
				}
			}
		}
	}
	/**
	 * 删除购物车
	 */
	public function removeCart(){
		if (IS_AJAX) {
			$goods_id = intval($_POST['goods_id']);
			$spec_id = intval($_POST['spec_id']);
			$this->Cart->delItem($goods_id,$spec_id);
		}
	}

	/**
	 * ajax获取价格
	 */
	public function ajaxGetCartPrice()
	{
		if (IS_AJAX)
		{
			$goods_id_str = trim($_POST['goods_id_str']);
			$goods_id_array = explode(',',$goods_id_str);
			$Cart = new Cart();
			$goods = array();
			$total_price = 0;
			foreach ($goods_id_array as $key => $goods_id)
			{
				$goods[$key] = $Cart->getItem($goods_id);
				$total_price = $goods[$key]['price']*$goods[$key]['num'];
			}
			$data['total_price'] = $total_price;
			json_return(200,'获取价格成功',$data);
		}
	}
}