<?php
/**
 * 商品
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Home\Controller;
use Home\Controller\BaseController;
use Common\Lib\Cart\Cart;
class GoodsController extends BaseController{
	public function __construct(){
		parent::__construct();
	}
	public function index(){
		
		$this->display();
	}
	/**
	 * 产品细节
	 */
	public function detail(){
		$where['goods_id'] = intval($_GET['goods_id']);
		$spec_id = intval($_GET['spec_id']);
		if ($spec_id) {
			$spec = M('GoodsSpec')->where(array('spec_id'=>$spec_id))->find();
			$this->spec = $spec;
			$where['goods_id'] = $spec['goods_id'];
		}
		$where['goods_status'] = 1;
		$goods = D('Goods')->relation(true)->where($where)->find();
		if (get_distributor($this->mid)) {
			$goods['goods_price'] = MSC('distributor_discount')*$goods['goods_price'];
		}
		$goods['GoodsPic'] = M('GoodsPic')->where(array('goods_id'=>$goods['goods_id'],'pic_status'=>1))->select();
		$goods['CommentSum'] = count($goods['GoodsComment']);
		if (!empty($goods)) {
			$this->goods = $goods;
			$this->discount = M('Discount')->order('goods_num')->select();
			$notice_where['notice_status'] = 1;
			$notice_where['notice_type'] = 2;
			$this->notice = M('Notice')->where($notice_where)->order('notice_sort desc')->select();
			$this->display();
		}else {
			$this->error('没有找到相关信息');
		}
	}
	public function thratre(){
		$spec_id = intval($_GET['spec_id']);
		$spec = M('GoodsSpec')->where(array('spec_id'=>$spec_id))->find();
		$this->spec = $spec;
		$where['goods_id'] = $spec['goods_id'];
		$where['goods_status'] = 1;
		$goods = D('Goods')->relation(true)->where($where)->find();
		if (get_distributor($this->mid)) {
			$goods['goods_price'] = MSC('distributor_discount')*$goods['goods_price'];
		}
		$goods['GoodsPic'] = M('GoodsPic')->where(array('goods_id'=>$goods['goods_id'],'pic_status'=>1))->select();
		$goods['CommentSum'] = count($goods['GoodsComment']);
		if (!empty($goods)) {
			$this->brand = M('GoodsBrand')->where(array('brand_status'=>1,'gc_id'=>array('in','0,'.$goods['gc_id'])))->order('brand_sort desc')->select();
			$this->goods = $goods;
			$this->discount = M('Discount')->order('goods_num')->select();
			$this->gc = 2;
			$notice_where['notice_status'] = 1;
			$notice_where['notice_type'] = 3;
			$this->notice = M('Notice')->where($notice_where)->order('notice_sort desc')->select();
			$this->display('Index/theatre');
		}else {
			$this->error('没有找到相关信息');
		}
	}
	public function buy(){
		if (IS_AJAX) {
			$goods_id = intval($_POST['goods_id']);
			$goods_num = intval($_POST['goods_num']);
			$Cart = new Cart();
			$goods = D('Goods')->where(array('goods_id'=>$goods_id,'goods_status'=>1))->find();
			if (!empty($goods)) {
				if ($goods['goods_storage'] < $goods_num) {
					$result['code'] = 300;
					$result['msg'] = '商品库存不足.';
					$result['data'] = array();
				}else {
					$Cart->addItem($goods['goods_id'], $goods['goods_name'], $goods['goods_price'], $goods_num, $goods['goods_pic']);
					$result['code'] = 200;
					$result['msg'] = '操作成功.';
					$result['data'] = array();
				}
			}else {
				$result['code'] = 300;
				$result['msg'] = '该商品不存在或者已下架,您无法购买.';
				$result['data'] = array();
			}
			echo json_encode($result);
		}
	}
	//预订
	public function reserve(){
		if (!$this->mid) {
// 			json_return(300,'请先登录.',array(),U('Login/index'));
			$this->error('请先登录',U('Login/index'));
		}
		$goods_id = intval($_POST['goods_id']);
		$spec_id = intval($_POST['spec_id']);
		$num = intval($_POST['num']);
		if ($num) {
			if ($spec_id){
				$goods_id = M('GoodsSpec')->where(array('spec_id'=>$spec_id))->getField('goods_id');
			}
			$goods = M('Goods')->where(array('goods_id'=>$goods_id))->find();
			if (get_distributor($this->mid)) {
				$cdisc = MSC('distributor_discount');
			}else {
				$cdisc = 1;
			}
			$data['order_sn'] = order_sn();
			$data['member_id'] = $this->mid;
			$data['order_type'] = 2;
			$data['goods_amount'] = $cdisc*get_discount($num)*$num*$goods['goods_price'];
			$data['discount'] = 0;
			$data['order_amount'] = $data['goods_amount']-$data['discount'];
			$data['order_state'] = 0;
			$data['add_time'] = NOW_TIME;
			$data['goods_id'] = $goods_id;
			$data['spec_id'] = $spec_id;
			$data['reserve_num'] = $num;
			$res = M('Reserve')->add($data);
			if ($res) {
				$this->success('预订成功',$_SERVER['HTTP_REFERER']);
// 				json_return(200,'预订成功.');
			}else {
				$this->error('预订失败');
// 				json_return(300,'预定失败.');
			}
		}else {
			$this->error('预订数量不能为空');
			json_return(300,'预订数量不能为空');
		}
	}
}