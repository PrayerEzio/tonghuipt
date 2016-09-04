<?php
/**
 * 商品
 * @copyright  Copyright (c) 2014-2030 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author	   muxiangdao-cn Team
 */
namespace Toadmin\Controller;
use Think\Page;
class GoodsController extends GlobalController {
	public function _initialize() 
	{
        parent::_initialize();
		$this->model = D('Goods');
		$this->goodsBrandModel = M("GoodsBrand");
		$this->goodsDiscountModel = M('Discount');
		$this->goodsClassModel = M('GoodsClass');
		$this->goods_class = M('goods_class')->order('gc_sort desc')->select();
	}
	//分类
	public function goods_class()
	{
	    $GoodsClass = M("GoodsClass");	
		$parent_id = $_GET['gc_parent_id']?intval($_GET['gc_parent_id']):0;
        //$gc_list =  $GoodsClass->where('gc_parent_id=0')->order('gc_sort asc')->select();		
		$tmp_list = getTreeClassList(3);
		if (is_array($tmp_list)){
			foreach ($tmp_list as $k => $v){
				if ($v['gc_parent_id'] == $parent_id){
					/**
					 * 判断是否有子类
					 */
					if ($tmp_list[$k+1]['deep'] > $v['deep']){
						$v['have_child'] = 1;
					}
					$class_list[] = $v;
				}
			}
		}	
		$this->assign('class_list', $class_list);			
		$this->display();		
	}
	/**
	 * 商品品牌
	 */
	public function goods_brand()
	{
		if (IS_POST) {
			$ids = $_POST['id'];
			foreach ($ids as $key => $val){
				$this->goodsBrandModel->where(array('brand_id'=>$val))->delete();
			}
		}
		$list = $this->goodsBrandModel->order('brand_sort desc')->select();
		$this->assign('list', $list);
		$this->title = '商品品牌';
		$this->display();
	}
	/**
	 * 删除商品品牌
	 */
	public function delGoodsBrand(){
		$brand_id = intval($_GET['brand_id']);
		if ($brand_id) {
			$res = $this->goodsBrandModel->where(array('brand_id'=>$brand_id))->delete();
			if ($res) {
				$this->success('删除成功');
			}else {
				$this->error('删除失败');
			}
		}
	}
	/**
	 * curd商品品牌
	 */
	public function curdGoodsBrand(){
		if (IS_POST) {
			$brand_id = intval($_POST['brand_id']);
			$data['brand_name'] = str_rp($_POST['brand_name'],1);
			$data['brand_url'] = I('post.brand_url','','url');
			$data['brand_desc'] = str_rp(I('post.brand_desc',''),1);
			$data['brand_keywords'] = str_rp(I('post.brand_keywords',''),1);
			$data['brand_sort'] = I('post.brand_sort',0,'int');
			$data['brand_status'] = I('post.brand_status',0,'int');
			$data['gc_id'] = I('post.gc_id',0,'int');
			if ($brand_id) {
				$res = $this->goodsBrandModel->where(array('brand_id'=>$brand_id))->save($data);
			}else {
				$res = $this->goodsBrandModel->add($data);
			}
			if ($res) {
				$this->success('操作成功',U('goods_brand'));
			}else {
				$this->error('操作失败');
			}
		}elseif (IS_GET){
			$where['brand_id'] = intval($_GET['brand_id']);
			$this->info = $this->goodsBrandModel->where($where)->find();
			$this->gc_list = M('GoodsClass')->order('gc_sort desc')->select();
			if ($this->info) {
				$this->title = '品牌编辑';
			}else {
				$this->title ='品牌添加';
			}
			$this->display();
		}
	}
	/**
	 * 商品折扣
	 */
	public function goods_discount()
	{
		if (IS_POST) {
			$ids = $_POST['id'];
			foreach ($ids as $key => $val){
				$this->goodsDiscountModel->where(array('id'=>val))->delete();
			}
		}
		$list = $this->goodsDiscountModel->order('goods_num')->select();
		$this->assign('list', $list);
		$this->title = '商品折扣';
		$this->display();
	}
	/**
	 * 删除商品折扣
	 */
	public function delGoodsDiscount(){
		$id = intval($_GET['id']);
		if ($id) {
			$res = $this->goodsDiscountModel->where(array('id'=>$id))->delete();
			if ($res) {
				$this->success('删除成功');
			}else {
				$this->error('删除失败');
			}
		}
	}
	/**
	 * curd商品折扣
	 */
	public function curdGoodsDiscount(){
		if (IS_POST) {
			$id = intval($_POST['id']);
			$data['goods_num'] = I('post.goods_num',0,'int');
			$data['goods_price_rate'] = floatval($_POST['goods_price_rate']);
			if ($id) {
				$res = $this->goodsDiscountModel->where(array('id'=>$id))->save($data);
			}else {
				$res = $this->goodsDiscountModel->add($data);
			}
			if ($res) {
				$this->success('操作成功',U('goods_discount'));
			}else {
				$this->error('操作失败');
			}
		}elseif (IS_GET){
			$where['id'] = intval($_GET['id']);
			$this->info = $this->goodsDiscountModel->where($where)->find();
			if ($this->info) {
				$this->title = '折扣编辑';
			}else {
				$this->title ='折扣添加';
			}
			$this->display();
		}
	}
    //产品类别添加
    public function goods_class_add()
	{
		$GoodsClass = M("GoodsClass");
		if(IS_POST && $_POST['form_submit'] == 'ok')
		{
			$map=array();
			$level = 0;
			$map['gc_name']      = str_rp(trim($_POST['gc_name']));
			$map['gc_parent_id'] = intval($_POST['gc_parent_id']);
			$map['gc_title']      = str_rp(trim($_POST['gc_title']));
			$map['gc_key']      = str_rp(trim($_POST['gc_key']));
			$map['gc_desc']      = str_rp(trim($_POST['gc_desc']));
            $map['gc_sort']      = intval($_POST['gc_sort']);
			if($map['gc_parent_id'])
			{
				$level = $GoodsClass->where('gc_id='.$map['gc_parent_id'])->getField('level');	
			}
			$map['level']      = $level+1;
            $return = $GoodsClass->add($map);
			if($return)
			{
				if(!empty($_FILES['gc_img']['name']))
	            {
	            	$gc_img = 'gc_'.time();
	            	$param = array('savePath'=>'goodsclass/','subName'=>'','files'=>$_FILES['gc_img'],'saveName'=>$gc_img,'saveExt'=>'');
	            	$up_return = upload_one($param);
	            	if($up_return == 'error')
	            	{
	            		$this->error('图片上传失败');
	            		exit;
	            	}else{
	            		$data['gc_img'] = $up_return;
	            		$GoodsClass->where('gc_id='.$return)->save($data);
	            	}
	            }
				$this->success('添加成功！', U('goods_class'));
			}else{
				$this->error('添加失败！');		
			}
		}else{
			/**
			 * 父类列表，只取到第二级
			 */
			$class_list = getTreeClassList(1);
			if (is_array($class_list)){
				foreach ($class_list as $k => $v){
					$class_list[$k]['gc_name'] = str_repeat("&nbsp;",$v['deep']*2).'├ '.$v['gc_name'];
				}
			}	
			$this->gc_parent_id = intval($_GET['gc_parent_id']);		
			$this->assign('class_list', $class_list);		
			$this->display();	
		}
		
    }	
	//购买限制
    public function goods_limit(){
    	$this->model = M('GoodsLimit');
    	if (IS_POST) {
			if (!empty($_POST['del_id'])) {
				$del_ids = '';
				foreach ($_POST['del_id'] as $bank_id){
					$del_ids .= $bank_id.',';
				}
				$del_ids = substr($del_ids, 0,-1);
				$res = $this->model->where(array('limit_id'=>array('IN',$del_ids)))->delete();
				if ($res) {
					$this->success('删除成功');
				}else {
					$this->error('删除失败');
				}
			}
		}elseif (IS_GET){
			$where = array();
			$count = $this->model->where($where)->count();
			$page = new Page($count,10);
			$list = $this->model->where($where)->limit($page->firstRow.','.$page->listRows)->limit('sort desc')->select();
			$this->list = $list;
			$this->page = $page->show();
			$this->search = $_GET;
			$this->display();
		}
    }
    //curd购买限制
    public function curd_goods_limit(){
    	$this->model = M('GoodsLimit');
    	if (IS_POST) {
    		$id = intval($_POST['limit_id']);
    		$data['limit_days'] = intval($_POST['limit_days']);
    		$data['limit_num'] = intval($_POST['limit_num']);
    		$data['price'] = floatval($_POST['price']);
    		$data['sort'] = intval($_POST['sort']);
    		$data['status'] = intval($_POST['status']);
    		if ($id) {
    			$res = $this->model->where(array('limit_id'=>$id))->save($data);
    		}else {
    			$res = $this->model->add($data);
    		}
    		if ($res) {
    			$this->success('操作成功',U('goods_limit'));
    		}else {
    			$this->error('操作失败');
    		}
    	}elseif (IS_GET){
    		$id = intval($_GET['id']);
    		if ($id) {
    			$this->info = $this->model->where(array('limit_id'=>$id))->find();
    		}
    		$this->display();
    	}
    }
    //删除购买限制
    public function limit_del(){
    	$this->model = M('GoodsLimit');
    	$where['limit_id'] = intval($_GET['id']);
    	$res = $this->model->where($where)->delete();
    	if ($res) {
    		$this->success('删除成功');
    	}else {
    		$this->error('删除失败');
    	}
    }
    //vip选项
    public function vip_option(){
    	$this->model = D('VipSelect');
    	if (IS_POST) {
    		if (!empty($_POST['del_id'])) {
    			$del_ids = '';
    			foreach ($_POST['del_id'] as $bank_id){
    				$del_ids .= $bank_id.',';
    			}
    			$del_ids = substr($del_ids, 0,-1);
    			$res = $this->model->where(array('select_id'=>array('IN',$del_ids)))->delete();
    			if ($res) {
    				M('VipOption')->where(array('select_id'=>array('IN',$del_ids)))->delete();
    				$this->success('删除成功');
    			}else {
    				$this->error('删除失败');
    			}
    		}
    	}elseif (IS_GET){
    		$where = array();
    		if (trim($_GET['select_name'])) {
    			$where['select_name'] = str_rp(trim($_POST['select_name']));
    		}
    		$count = $this->model->where($where)->count();
    		$page = new Page($count,10);
    		$list = $this->model->relation(true)->where($where)->limit($page->firstRow.','.$page->listRows)->limit('select_sort desc')->select();
    		$this->list = $list;
    		$this->page = $page->show();
    		$this->search = $_GET;
    		$this->display();
    	}
    }
    //curdvip选项
    public function curd_vip_option(){
    	$this->model = D('VipSelect');
    	if (IS_POST) {
    		$id = intval($_POST['select_id']);
    		$data['select_name'] = str_rp(trim($_POST['select_name']));
    		$data['select_tip'] = str_rp(trim($_POST['select_tip']));
    		$data['select_sort'] = intval($_POST['select_sort']);
    		$data['select_status'] = intval($_POST['select_status']);
    		if ($id) {
    			$this->model->where(array('select_id'=>$id))->save($data);
    		}else {
    			$id = $this->model->add($data);
    			if (!$id) {
    				$this->error('添加数据失败');
    			}
    		}
    		if (!empty($_POST['s_value'])) {
    			foreach ($_POST['s_value'] as $key => $val){
    				$option = $val;
    				$option['select_id'] = $id;
    				if ($val['option_id']) {
    					$rsc = M('VipOption')->where($option)->count();
    					if (!$rsc) {
    						$rc = M('VipOption')->where(array('option_id'=>$val['option_id']))->save($option);
    					}else {
    						$rc = true;
    					}
    				}else {
    					$rc = M('VipOption')->add($option);
    				}
    				unset($option);
    				if (!$rc) {
		    			$this->error('操作失败');
    				}
    			}
    		}
    		$this->success('操作成功',U('vip_option'));
    	}elseif (IS_GET){
    		$id = intval($_GET['id']);
    		$this->info = $this->model->relation(true)->where(array('select_id'=>$id))->find();
    		$this->option_num = count($this->info['option']);
    		$this->display();
    	}
    }
    //删除VIP选项
    public function option_del(){
    	$this->model = D('VipSelect');
    	$where['select_id'] = intval($_GET['id']);
    	$res = $this->model->where($where)->delete();
    	if ($res) {
    		M('VipOption')->where($where)->delete();
    		$this->success('删除成功');
    	}else {
    		$this->error('删除失败');
    	}
    }
    //发布提示
    public function publish_tip(){
    	$this->model = M('PublishTip');
    	$where = array();
    	if (trim($_GET['select_name'])) {
    		$where['select_name'] = str_rp(trim($_POST['select_name']));
    	}
    	$count = $this->model->where($where)->count();
    	$page = new Page($count,10);
    	$list = $this->model->where($where)->limit($page->firstRow.','.$page->listRows)->limit('select_sort desc')->select();
    	$this->list = $list;
    	$this->page = $page->show();
    	$this->title = '发布提示';
    	$this->search = $_GET;
    	$this->display();
    }
    
    //产品选择一级类别异步加载二级分类 goods_class_add.html
	public function goods_class_ajax()
	{
		$gc_parent_id = intval($_GET['gc_parent_id']); 
		if($gc_parent_id)
		{
			$GoodsClass = M("GoodsClass");
		    $gc_list =$GoodsClass->where('gc_parent_id='.$gc_parent_id)->select(); 
            if(is_array($gc_list) && !empty($gc_list))
			{
			   foreach($gc_list as $rs)
			   {	
				   $gc_id = $rs['gc_id'];
				   $gc_name = $rs['gc_name'];
				   $gc_v.="<option  value='$gc_id'>&nbsp;&nbsp;$gc_name</option>";	
			   }
			}			
		}
		echo "<select name='gc_parent_id_2' id='gc_parent_id_2'><option value='0'>请选择...</option>".$gc_v."</select>";
	}	

	//异步获取产品下级分类
	public function goods_nc_ajax()
	{
	 	$gc_parent_id = $_GET['gc_parent_id']?intval($_GET['gc_parent_id']):0;
		$tmp_list = getTreeClassList(3); //取分类列表
		if (is_array($tmp_list))
		{
			foreach ($tmp_list as $k => $v)
			{
				if ($v['gc_parent_id'] == $gc_parent_id)
				{
					/**
					 * 判断是否有子类
					 */
					if ($tmp_list[$k+1]['deep'] > $v['deep'])
					{
						$v['have_child'] = 1;
					}
					$class_list[] = $v;
				}
			}
		}	
		$output = json_encode($class_list);
		print_r($output);
		exit;			
	}
	
	//编辑分类
	public function goods_class_edit()
	{
		$GoodsClass = M("GoodsClass");
		if(IS_POST && $_POST['gc_id'])
		{
			$data = array();
			$level = 0;
			$data['gc_id'] = intval($_POST['gc_id']);
			$data['gc_name'] = str_rp(trim($_POST['gc_name']));
			$data['gc_parent_id'] = intval($_POST['gc_parent_id']);
			$data['gc_title']      = str_rp(trim($_POST['gc_title']));
			$data['gc_key']      = str_rp(trim($_POST['gc_key']));
			$data['gc_desc']      = str_rp(trim($_POST['gc_desc']));			
            $data['gc_sort']      = intval($_POST['gc_sort']);
			if($data['gc_parent_id'])
			{
				$level = $GoodsClass->where('gc_id='.$data['gc_parent_id'])->getField('level');	
			}
			$data['level']      = $level+1;			
            //图片上传
            if(!empty($_FILES['gc_img']['name']))
            {
            	$gc_img = 'gc_'.time();
            	$param = array('savePath'=>'goodsclass/','subName'=>'','files'=>$_FILES['gc_img'],'saveName'=>$gc_img,'saveExt'=>'');
            	$up_return = upload_one($param);
            	if($up_return == 'error')
            	{
            		$this->error('图片上传失败');
            		exit;
            	}else{
            		$data['gc_img'] = $up_return;
            	}
            }
            if ($GoodsClass->where('gc_id='.$data['gc_id'])->save($data)) {
            	$this->success('操作成功！', U('goods_class'));
            }else {
            	$this->error('操作失败！');
            }
		}else{
			$gc_id = intval($_GET['gc_id']);
			$rs = $GoodsClass->where('gc_id='.$gc_id)->find();
				/**
				 * 父类列表，只取到第二级
				 */
				$class_list = getTreeClassList(1);
				if (is_array($class_list)){
					foreach ($class_list as $k => $v){
						$class_list[$k]['gc_name'] = str_repeat("&nbsp;",$v['deep']*2).'├ '.$v['gc_name'];
					}
				}		
			$this->assign('class_list', $class_list);	
			$this->assign('rs',$rs);	
			$this->display();	
		}
	}	
	//删除分类信息
	public function goods_class_del()
	{
		$GoodsClass = M("GoodsClass");
		if (IS_POST)
		{
			$gc_id_list = $_POST['check_gc_id'];
			foreach ($gc_id_list as $key => $gc_id)
			{
				if($gc_id)
				{
					$map = array();
					$array = $GoodsClass->select();
					$in_arr = getChildsId ($array, $gc_id, 'gc_id', 'gc_parent_id'); //该分类下的所有分类
					$in_arr[] = $gc_id;
					$map['gc_id'] = array('in',$in_arr);
					$gc_list = $GoodsClass->where($map)->select();
					if(is_array($gc_list) && !empty($gc_list))
					{
						foreach($gc_list as $gc)
						{
							if($gc['gc_img'])
							{
								//删除图片
								@unlink(BasePath.'/Uploads/'.$gc['gc_img']);
							}
						}
					}
				}
			}
			$res = $GoodsClass->where(array('gc_id'=>array('in',$gc_id_list)))->delete();
			if($res)
			{
				$this->success('操作成功！', U('goods_class'));
			}else{
				$this->error('操作失败！');
			}
		}elseif (IS_GET){
			$gc_id = intval($_GET['gc_id']);
			if($gc_id)
			{
				$map = array();
				$all_next_gc_id='';
				$in_arr = get_all_gc_id($gc_id); //该分类下的所有分类
				$all_next_gc_id='';
				$map['gc_id'] = array('in',$in_arr);
				$gc_list = $GoodsClass->where($map)->select();
				if(is_array($gc_list) && !empty($gc_list))
				{
					foreach($gc_list as $gc)
					{
						if($gc['gc_img'])
						{
							//删除图片
							@unlink(BasePath.'/Uploads/'.$gc['gc_img']);
						}
					}
				}
				$delnum = $GoodsClass->where($map)->delete();
				if($delnum)
				{
					$this->success('操作成功！', U('goods_class'));
				}else{
					$this->error('操作失败！');
				}
			}
		}
	}
			
	//管理
	public function goods()
	{
		$map = array();
		$goods_name = trim($_GET['goods_name']);
		$gc_id = intval($_GET['gc_id']);
		$goods_status = intval($_GET['goods_status']);
		$brand_id = intval($_GET['brand_id']);
		$goods_id = intval($_GET['goods_id']);
		$map['member_id'] = 0;
		if($goods_id){
			$map['goods_id'] = $goods_id;
		}
		if ($brand_id) {
			$map['brand_id'] = $brand_id;
		}
		if($goods_status == 1){
			$map['goods_status'] = 1;
		}elseif ($goods_status == -1){
			$map['goods_status'] = 0;
		}
		if($goods_name)$map['goods_name'] = array('like','%'.$goods_name.'%');
		if(intval($_GET['goods_type_id'])) $map['goods_type_id'] = array('eq',intval($_GET['goods_type_id']));
		if(!empty($gc_id))
		{
/* 			$all_next_gc_id='';
			$in_arr = getChildsId($this->goods_class,$gc_id,'gc_id','gc_parent_id'); //该分类下的所有分类
			$all_next_gc_id=''; */
			$map['gc_id'] = array('eq',$gc_id);	
		}
		$order_post = I('get.order','goods_sort-desc');
		$order = explode('-', $order);
		if (!empty($order)) {
			$order = $order[0].' '.$order[1];
		}
		$totalRows = $this->model->where($map)->count();
		$page = new Page($totalRows,10);	
		$list = $this->model->relation('GoodsClass')->where($map)->limit($page->firstRow.','.$page->listRows)->order('add_time desc')->select();				
		$this->assign('list',$list);
		$search = $_GET;
		$search['order'] = $order_post;
		$this->assign('search',$search);	
		$this->assign('page_show',$page->show());
		$this->brand_list = M('GoodsBrand')->where(array('brand_status'=>1))->order('brand_sort desc')->select();
		/**
		 * 父类列表，只取到第二级
		 */
		$class_list = getTreeClassList(3);
		if (is_array($class_list)){
			foreach ($class_list as $k => $v){
				$class_list[$k]['gc_name'] = str_repeat("&nbsp;",$v['deep']*2).'├ '.$v['gc_name'];
			}
		}
		$this->assign('class_list', $class_list);	
		$this->display();
	}

	//管理
	public function point_goods()
	{
		$map = array();
		$goods_name = trim($_GET['goods_name']);
		$gc_id = intval($_GET['gc_id']);
		$goods_status = intval($_GET['goods_status']);
		$brand_id = intval($_GET['brand_id']);
		$goods_id = intval($_GET['goods_id']);
		$member_id = intval($_GET['member_id']);
		$map['member_id'] = array('neq',0);
		if ($member_id)
		{
			$map['member_id'] = $member_id;
		}
		if($goods_id){
			$map['goods_id'] = $goods_id;
		}
		if ($brand_id) {
			$map['brand_id'] = $brand_id;
		}
		if($goods_status == 1){
			$map['goods_status'] = 1;
		}elseif ($goods_status == -1){
			$map['goods_status'] = 0;
		}
		if($goods_name)$map['goods_name'] = array('like','%'.$goods_name.'%');
		if(intval($_GET['goods_type_id'])) $map['goods_type_id'] = array('eq',intval($_GET['goods_type_id']));
		if(!empty($gc_id))
		{
			/* 			$all_next_gc_id='';
                        $in_arr = getChildsId($this->goods_class,$gc_id,'gc_id','gc_parent_id'); //该分类下的所有分类
                        $all_next_gc_id=''; */
			$map['gc_id'] = array('eq',$gc_id);
		}
		$order_post = I('get.order','goods_sort-desc');
		$order = explode('-', $order_post);
		if (!empty($order)) {
			$order = $order[0].' '.$order[1];
		}
		$totalRows = $this->model->where($map)->count();
		$page = new Page($totalRows,10);
		$list = $this->model->relation('GoodsClass')->where($map)->limit($page->firstRow.','.$page->listRows)->order('add_time desc')->select();
		$this->assign('list',$list);
		$search = $_GET;
		$search['order'] = $order_post;
		$this->assign('search',$search);
		$this->assign('page_show',$page->show());
		$this->brand_list = M('GoodsBrand')->where(array('brand_status'=>1))->order('brand_sort desc')->select();
		/**
		 * 父类列表，只取到第二级
		 */
		$class_list = getTreeClassList(3);
		if (is_array($class_list)){
			foreach ($class_list as $k => $v){
				$class_list[$k]['gc_name'] = str_repeat("&nbsp;",$v['deep']*2).'├ '.$v['gc_name'];
			}
		}
		$this->assign('class_list', $class_list);
		$this->display('goods');
	}

	//添加
	public function goods_add()
	{
		if(IS_POST){
			$data = array();
			$data['member_id'] = intval($_POST['member_id']);
			$data['gc_id'] = intval($_POST['gc_id']);
			$data['brand_id'] = intval($_POST['brand_id']);
			$data['goods_name'] = str_rp(trim($_POST['goods_name']));
			$data['goods_code'] = str_rp(trim($_POST['goods_code']));
			$data['goods_type'] = str_rp(trim($_POST['goods_type']));
			$data['goods_type_id'] = get_goods_type_id($data['gc_id']);
			$data['goods_city_id'] = intval(trim($_POST['goods_city_id']));
			$data['goods_sales'] = intval($_POST['goods_sales']);
			$data['goods_key'] = str_rp(trim($_POST['goods_key']));
			$data['goods_desc'] = str_rp(trim($_POST['goods_desc']));
			$data['goods_url'] = str_rp(trim($_POST['goods_url']));
			$data['goods_storage'] = intval($_POST['goods_storage']);
			$data['goods_serial'] = str_rp(trim($_POST['goods_serial']));
			$data['store_name'] = str_rp(trim($_POST['store_name']));
			$data['goods_cost'] = price_format($_POST['goods_cost']);
			$data['goods_price'] = price_format($_POST['goods_price']);
			$data['goods_mktprice'] = price_format($_POST['goods_mktprice']);
			$data['member_price'] = price_format($_POST['member_price']);
			$data['freight'] = price_format($_POST['freight']);
			$data['goods_point'] = intval($_POST['goods_point']);
			$data['cost_point'] = intval($_POST['cost_point']);
			$data['goods_sort'] = intval($_POST['goods_sort']);
			$data['goods_body'] = str_replace('\'','&#39;',$_POST['goods_body']);
			$data['goods_status'] = intval($_POST['goods_status']);
			$data['add_time'] = NOW_TIME;

			//图片上传
			if(!empty($_FILES['goods_pic']['name']))
			{
				$goods_img = 'g_'.$data['add_time'];
				$param = array('savePath'=>'goods/','subName'=>'','files'=>$_FILES['goods_pic'],'saveName'=>$goods_img,'saveExt'=>'');
				$param['thumb']['width'] = 200;
				$param['thumb']['height'] = 250;
				$up_return = upload_one($param);
				if($up_return == 'error')
				{
					$this->error('图片上传失败');
					exit;	
				}else{
					$data['goods_pic'] = $up_return;	
				}					
			}	
			$goods_id = $this->model->add($data);
			if($goods_id)
			{	
				//search处理
				$search['url'] = U('Goods/detail',array('goods_id'=>$goods_id));
				$search['title'] = $data['goods_name'];
				$search['keywords'] = $data['goods_key'];
				$search['description'] = $data['goods_desc'];
				$search['img'] = $up_return;
			    //规格处理
				$spec_val = $_POST['s_value'];
				if(is_array($spec_val) && !empty($spec_val))
				{
					foreach ($spec_val as $k=>$val)
					{
						$val['sort']	= intval($val['sort']);	
						$val['name']	= trim($val['name']);
                        if($val['name'])
						{
							/**
							 * 新增规格值
							 */
							$val_add = array();
							$val_add['goods_id'] = $goods_id;
							$val_add['spec_name'] = trim($val['name']);
							$val_add['spec_goods_sort']	= intval($val['sort']);
							$return = M('GoodsSpec')->add($val_add);
							$search['keywords'] .= $val_add['spec_name'];
							unset($val_add);	
						}
					}
					$search_id = addSearch($search['url'],$search['title'],$search['keywords'],$search['description'],'',$search['img']);
					$this->model->where(array('goods_id'=>$goods_id))->setField('search_id',$search_id);
					//更新商品列表默认规格信息
					$df_spec = M('GoodsSpec')->where('goods_id='.$goods_id)->order('spec_goods_price asc')->find();
					if(is_array($df_spec) && !empty($df_spec))
					{
						$spec_data = array();
						$spec_data['spec_id'] = $df_spec['spec_id'];	
						$spec_data['spec_name'] = $df_spec['spec_name'];	
					}
				}	
				
				//商品多图片处理
				$GoodsPic = M('GoodsPic');
				$pic_val = $_POST['s_pic1'];
				if(is_array($pic_val) && !empty($pic_val))
				{
					$pic_data = array();
					$n=1;
					foreach ($pic_val as $p=>$pv)
					{
						$pic_data['p_sort']	= intval($pv['sort']);	
						$pic_data['goods_id'] = $goods_id;	
						$pic_name = 's_pic1_'.$p;			
						if($_FILES[$pic_name]['size'] > 0)
						{
							$goods_img = 'm1_'.$goods_id.'_'.$n.'_'.NOW_TIME;
							$param = array('savePath'=>'goods/','subName'=>'','files'=>$_FILES[$pic_name],'saveName'=>$goods_img,'saveExt'=>'');
							$param['thumb']['width'] = 200;
							$param['thumb']['height'] = 250;
							$up_return = upload_one($param);
							if($up_return == 'error')
							{
								$this->error('图片上传失败');
								exit;	
							}else{
								$pic_data['pic'] = $up_return;
								$pic_data['pic_status'] = 1;
							}	
		                    $GoodsPic->add($pic_data);  							
						}
						$n++;						
					}
				}
				unset($pic_val);
				//图片处理END
				$pic_val = $_POST['s_pic2'];
				if(is_array($pic_val) && !empty($pic_val))
				{
					$pic_data = array();
					$n=1;
					foreach ($pic_val as $p=>$pv)
					{
						$pic_data['p_sort']	= intval($pv['sort']);
						$pic_data['goods_id'] = $goods_id;
						$pic_name = 's_pic2_'.$p;
						if($_FILES[$pic_name]['size'] > 0)
						{
							$goods_img = 's2_'.$goods_id.'_'.$n.'_'.NOW_TIME;
							$param = array('savePath'=>'goods/','subName'=>'','files'=>$_FILES[$pic_name],'saveName'=>$goods_img,'saveExt'=>'');
							$up_return = upload_one($param);
							if($up_return == 'error')
							{
								$this->error('图片上传失败');
								exit;
							}else{
								$pic_data['pic'] = $up_return;
								$pic_data['pic_status'] = 2;
							}
							$GoodsPic->add($pic_data);
						}
						$n++;
					}
				}
				unset($pic_val);
				//图片处理END				
																 
			 	$this->success('操作成功', U('goods'));
				exit;		
			}else{
				 $this->error('操作失败');
			}				
		}else{
			/**
			 * 父类列表
			 */
			$class_list = getTreeClassList(3);
			if (is_array($class_list)){
				foreach ($class_list as $k => $v){
					$class_list[$k]['gc_name'] = str_repeat("&nbsp;",$v['deep']*2).'├ '.$v['gc_name'];
				}
			}
			$brand_list = $this->goodsBrandModel->where(array('brand_status'=>1))->order('brand_sort desc')->select();
				
			//规格
			$spec_list = array();
			//多图片
			$pic1_list = array();
			$pic2_list = array();
			//常用城市
			$this->city_list = D('District')->where('usetype=1')->order('d_sort desc')->select();
			
			$this->assign('spec_list', $spec_list);
			$this->assign('spec_list_i', count($spec_list)+1);
			
			$this->assign('pic1_list', $pic1_list);
			$this->assign('pic1_list_i', count($pic1_list)+1);
			$this->assign('pic2_list', $pic2_list);
			$this->assign('pic2_list_i', count($pic2_list)+1);
			$this->assign('brand_list',$brand_list);
			$this->assign('class_list', $class_list);
			$this->assign('spec_list', $spec_list);		
			$this->assign('sign_i', count($spec_list));				
			$this->display('goods_edit');	
		}
	}
	//编辑
	public function goods_edit()
	{
		$goods_id = intval($_REQUEST['goods_id']);
		if(IS_POST){
			$data = array();
			$data['member_id'] = intval($_POST['member_id']);
			$data['gc_id'] = intval($_POST['gc_id']);
			$data['brand_id'] = intval($_POST['brand_id']);
			$data['goods_name'] = str_rp(trim($_POST['goods_name']));
			$data['goods_code'] = str_rp(trim($_POST['goods_code']));
			$data['goods_type'] = str_rp(trim($_POST['goods_type']));
			$data['goods_type_id'] = get_goods_type_id($data['gc_id']);
			$data['goods_city_id'] = intval(trim($_POST['goods_city_id']));
			$data['goods_sales'] = intval($_POST['goods_sales']);
			$data['goods_key'] = str_rp(trim($_POST['goods_key']));
			$data['goods_desc'] = str_rp(trim($_POST['goods_desc']));
			$data['goods_url'] = str_rp(trim($_POST['goods_url']));
			$data['goods_storage'] = intval($_POST['goods_storage']);
			$data['goods_serial'] = str_rp(trim($_POST['goods_serial']));
			$data['store_name'] = str_rp(trim($_POST['store_name']));
			$data['goods_cost'] = price_format($_POST['goods_cost']);
			$data['goods_price'] = price_format($_POST['goods_price']);
			$data['goods_mktprice'] = price_format($_POST['goods_mktprice']);
			$data['member_price'] = price_format($_POST['member_price']);
			$data['freight'] = price_format($_POST['freight']);
			$data['goods_point'] = intval($_POST['goods_point']);
			$data['cost_point'] = intval($_POST['cost_point']);
			$data['goods_sort'] = intval($_POST['goods_sort']);
			$data['goods_body'] = str_replace('\'','&#39;',$_POST['goods_body']);
			$data['goods_status'] = intval($_POST['goods_status']);
			$data['add_time'] = NOW_TIME;
			//图片上传
			if(!empty($_FILES['goods_pic']['name']))
			{
				$goods_img = 'g_'.$data['add_time'];
				$gd = $this->model->where('goods_id='.$goods_id)->find();
				if($gd['goods_pic'])
				{	
					$old_pic = BasePath.'/Uploads/'.$gd['goods_pic'];			
					unlink($old_pic);	
				}
				$param = array('savePath'=>'goods/','subName'=>'','files'=>$_FILES['goods_pic'],'saveName'=>$goods_img,'saveExt'=>'');
				$param['thumb']['width'] = 200;
				$param['thumb']['height'] = 250;
				$up_return = upload_one($param);
				if($up_return == 'error')
				{
					$this->error('图片上传失败');
					exit;	
				}else{
					$data['goods_pic'] = $up_return;	
				}
			}	
						
			$return = $this->model->where('goods_id='.$goods_id)->save($data);
			if($return)
			{
				//search处理
				$search['url'] = U('Goods/detail',array('goods_id'=>$goods_id));
				$search['title'] = $data['goods_name'];
				$search['keywords'] = $data['goods_key'];
				$search['description'] = $data['goods_desc'];
				$search['img'] = $up_return;
			    //规格处理
				$GoodsSpec = M('GoodsSpec');
				$spec_val = $_POST['s_value'];
				if(is_array($spec_val) && !empty($spec_val))
				{
					$GoodsSpec->where('goods_id='.$goods_id)->delete($data); // 删除原来的规格
					foreach ($spec_val as $k=>$val)
					{
						$val['sort']	= intval($val['sort']);	
						$val['name']	= trim($val['name']);
                        if($val['name'])
						{
							/**
							 * 新增规格值
							 */
							$val_add	= array();
							$val_add['goods_id'] = $goods_id;
							$val_add['spec_name'] = trim($val['name']);
							$val_add['spec_goods_price'] = price_format(trim($val['price']));
							$val_add['spec_goods_sort']	= intval($val['sort']);
							$return = $GoodsSpec->add($val_add);
							$search['keywords'] .= ','.$val_add['spec_name'];
							unset($val_add);	
						}
					}
					$search_id = $this->model->where(array('goods_id'=>$goods_id))->getField('search_id');
					if (empty($search_id)) {
						$search_id = '';
					}
					$search_id = addSearch($search['url'],$search['title'],$search['keywords'],$search['description'],$search_id,$search['img']);
					$this->model->where(array('goods_id'=>$goods_id))->setField('search_id',$search_id);
					//更新商品列表默认规格信息
					$df_spec = M('GoodsSpec')->where('goods_id='.$goods_id)->order('spec_goods_price asc')->find();
					if(is_array($df_spec) && !empty($df_spec))
					{
						$spec_data = array();
						$spec_data['spec_id'] = $df_spec['spec_id'];	
						$spec_data['spec_name'] = $df_spec['spec_name'];	
					}					
				}else{
					$spec_data = array();
					$spec_data['spec_id'] = 0;	
					$spec_data['spec_name'] = '';	
					$this->model->where('goods_id='.$goods_id)->save($spec_data);						
					$GoodsSpec->where('goods_id='.$goods_id)->delete($data); // 删除原来的规格	
				}	
				
				//商品多图片处理
				$GoodsPic = M('GoodsPic');
				$pic_val = $_POST['s_pic1'];
				if(is_array($pic_val) && !empty($pic_val))
				{
					$pic_data = array();
					$n=1;
					foreach ($pic_val as $p=>$pv)
					{
						$pic_data['p_sort']	= intval($pv['sort']);	
						$pic_data['goods_id'] = $goods_id;	
						$pic_name = 's_pic1_'.$p;			
						if($_FILES[$pic_name]['size'] > 0)
						{
							$goods_img = 'm1_'.$goods_id.'_'.$n.'_'.NOW_TIME;
							$param = array('savePath'=>'goods/','subName'=>'','files'=>$_FILES[$pic_name],'saveName'=>$goods_img,'saveExt'=>'');
							$param['thumb']['width'] = 200;
							$param['thumb']['height'] = 250;
							$up_return = upload_one($param);
							if($up_return == 'error')
							{
								$this->error('图片上传失败');
								exit;	
							}else{
								$pic_data['pic'] = $up_return;
								$pic_data['pic_status'] = 1;
							}	
		                    $GoodsPic->add($pic_data);  							
						}
						$n++;						
					}
				}
				unset($pic_val);
				//图片处理END
				$pic_val = $_POST['s_pic2'];
				if(is_array($pic_val) && !empty($pic_val))
				{
					$pic_data = array();
					$n=1;
					foreach ($pic_val as $p=>$pv)
					{
						$pic_data['p_sort']	= intval($pv['sort']);
						$pic_data['goods_id'] = $goods_id;
						$pic_name = 's_pic2_'.$p;
						if($_FILES[$pic_name]['size'] > 0)
						{
							$goods_img = 's2_'.$goods_id.'_'.$n.'_'.NOW_TIME;
							$param = array('savePath'=>'goods/','subName'=>'','files'=>$_FILES[$pic_name],'saveName'=>$goods_img,'saveExt'=>'');
							$up_return = upload_one($param);
							if($up_return == 'error')
							{
								$this->error('图片上传失败');
								exit;
							}else{
								$pic_data['pic'] = $up_return;
								$pic_data['pic_status'] = 2;
							}
							$GoodsPic->add($pic_data);
						}
						$n++;
					}
				}
				unset($pic_val);
				//图片处理END
																		 
			 	$this->success('操作成功', U('goods'));
				exit;		
			}else{
				 $this->error('操作失败');
			}
		}else{
			/**
			 * 父类列表
			 */
			$class_list = getTreeClassList(3);
			if (is_array($class_list)){
				foreach ($class_list as $k => $v){
					$class_list[$k]['gc_name'] = str_repeat("&nbsp;",$v['deep']*2).'├ '.$v['gc_name'];
				}
			}
			$brand_list = $this->goodsBrandModel->where(array('brand_status'=>1))->order('brand_sort desc')->select();
			
			$rs = $this->model->relation(true)->where('goods_id='.$goods_id)->find();
			$this->assign('rs', $rs);	
			
			//规格
			$spec_list = M('GoodsSpec')->where('goods_id='.$goods_id)->order('spec_goods_sort asc')->select();
			//多图片
			$pic1_list = M('GoodsPic')->where(array('goods_id'=>$goods_id,'pic_status'=>1))->order('p_sort asc')->select();
			$pic2_list = M('GoodsPic')->where(array('goods_id'=>$goods_id,'pic_status'=>2))->order('p_sort asc')->select();
			//常用城市
			$this->city_list = D('District')->where('usetype=1')->order('d_sort desc')->select();
						
			$this->assign('spec_list', $spec_list);
			$this->assign('spec_list_i', count($spec_list)+1);

			$this->assign('pic1_list', $pic1_list);			
			$this->assign('pic1_list_i', count($pic1_list)+1);	
			$this->assign('pic2_list', $pic2_list);
			$this->assign('pic2_list_i', count($pic2_list)+1);
			$this->assign('brand_list',$brand_list);
			$this->assign('class_list', $class_list);
			$this->assign('title','商品编辑');
			$this->display();
		}
	}	
	//删除
	public function goods_del()
	{
		if(IS_GET)
		{
			$goods_pic = $this->model->where('goods_id='.$_GET['goods_id'])->getField('goods_pic');
			if($goods_pic)
			{
				$old_pic = BasePath.'/Uploads/'.$goods_pic;			
				@unlink($old_pic);					
			} 
			$this->model->where('goods_id='.$_GET['goods_id'])->delete(); 			
			M('GoodsSpec')->where('goods_id='.$_GET['goods_id'])->delete();	
			$pics = M('GoodsPic')->where('goods_id='.intval($_GET['goods_id']))->select();
			if(is_array($pics) && !empty($pics))
			{
				foreach($pics as $vo)
				{
					$old_pic = BasePath.'/Uploads/'.$vo['pic'];			
					@unlink($old_pic);					
					M('GoodsPic')->where('id='.$vo['id'])->delete();
				}
			}				
		}
		if(IS_POST)
		{
			$map = array();
			$map['goods_id'] = array('in',$_POST['goods_id']);
			$goods_pic = $this->model->where($map)->getField('goods_pic');
			if($goods_pic)
			{
				$old_pic = BasePath.'/Uploads/'.$goods_pic;			
				@unlink($old_pic);					
			} 			
			$this->model->where($map)->delete();
			M('GoodsSpec')->where($map)->delete(); 
			$pics = M('GoodsPic')->where($map)->select();
			if(is_array($pics) && !empty($pics))
			{
				foreach($pics as $vo)
				{
					$old_pic = BasePath.'/Uploads/'.$vo['pic'];			
					@unlink($old_pic);					
					M('GoodsPic')->where('id='.$vo['id'])->delete();
				}
			}				
		}
		$this->success("操作成功",U('goods'));  	
		exit;			
	}

	public function class_del()
	{
		if (IS_POST)
		{
			$map = array();
			$map['gc_id'] = array('in',$_POST['del_id']);
			$this->goodsClassModel->where($map)->delete();
			$this->model->where($map)->setField('gc_id',0);
		}
		$this->success("操作成功",U('goods_class'));
	}

	public function goods_query(){
		$words = trim($_GET['words']);
		$field = trim($_GET['field']);
		$where = array();
		if ($words && $field && $field != 'spec_name') {
			$where[$field] = array('like','%'.$words.'%');
		}elseif ($words && $field && $field == 'spec_name'){
			$goods_ids = M('GoodsSpec')->where(array('spec_name'=>array('like','%'.$words.'%')))->select();
			$goods_ids_str = '';
			foreach ($goods_ids as $key => $goods){
				$goods_ids_str .= $goods['goods_id'].',';
			}
			$goods_ids_str = substr($goods_ids_str, 0, -1);
			if (!empty($goods_ids_str)) {
				$where['goods_id'] = array('in',$goods_ids_str);
			}
		}
		if (!empty($where)) {
			$count = $this->model->where($where)->count();
			$page = new Page($count,10);
			$list = $this->model->relation(true)->where($where)->order('goods_sort desc')->limit($page->firstRow.','.$page->listRows)->select();
			$this->list = $list;
			$this->page = $page->show();
		}
		$this->search = $_GET;
		$this->display();
	}
	//异步获取图片
	public function get_album_list()
	{
		$sign = intval($_GET['sign']);
		$aclass_id = intval($_GET['aclass_id']);
		if($aclass_id)
		{
			//$AlbumClass = M('AlbumClass');
			$AlbumPic = M('AlbumPic');
			$pic_list=$AlbumPic->where('aclass_id='.$aclass_id)->order('upload_time asc')->select();
			if(is_array($pic_list) && !empty($pic_list))
			{
				foreach($pic_list as $rs)
				{
					$apic_cover = C('SiteUrl').'/Uploads/'.$rs['apic_cover'];
					if($sign==1)
					{
						$pic_list_str.='<li onclick="insert_img_editor(\''.$apic_cover.'\');"><a href="JavaScript:void(0);"><span class="thumb size90"><img src="'.$apic_cover.'" title="点击插入"></span></a></li>';
					}else{
						$pic_list_str.='<li onclick="insert_img(\''.$apic_cover.'\');"><a href="JavaScript:void(0);"><span class="thumb size90"><img src="'.$apic_cover.'" title="点击插入"></span></a></li>';
					}
				}	
				 header("Cache-Control: no-cache");
				 echo $pic_list_str;
			}							
		}else{
			echo'';	
		}				
	}
	//异步删除图片
	public function del_pic()
	{
		$id = intval($_GET['id']);
		if($id)
		{
			$vo = M('GoodsPic')->where('id='.$id)->find();
			if(is_array($vo) && !empty($vo))
			{
				$old_pic = BasePath.'/Uploads/'.$vo['pic'];			
				unlink($old_pic);					
				$rst = M('GoodsPic')->where('id='.$id)->delete();
				echo json_encode(array('done'=>'1','msg'=>'添加成功'));
				exit;				
			}
		}
	}
	//异步获取商品类型
	public function goods_type_show()
	{
	   $GoodsClass = M("GoodsClass");	
       $gc_id = intval($_GET['gc_id']);	
	   $gc = $GoodsClass->where('gc_id='.$gc_id)->find();
	   if($gc['gc_parent_id'] == 16 || $gc['gc_parent_id'] == 18 || $gc_id == 16 || $gc_id == 18)
	   {
			echo json_encode(array('done'=>'1'));
			exit;				   
	   }else{
			echo json_encode(array('done'=>'2'));
			exit;			   
	   }			
	}
	public function addSerialNumber(){
		if (IS_AJAX) {
			$goods_name = trim($_POST['goods_name']);
			$goods_id = M('Goods')->where(array('goods_name'=>$goods_name))->getField('goods_id');
			json_return(200,$goods_id,$goods_id);
		}elseif (IS_POST) {
			$data['goods_id'] = intval($_POST['goods_id']);
			$data['serial_number'] = trim($_POST['serial_number']);
			$res = M('Serial')->add($data);
			if ($res){
				$this->success('添加序列号成功');
			}else {
				$this->error('添加序列号失败');
			}
		}elseif (IS_GET){
			$serial_number = trim($_GET['serial_number']);
			$this->info = M('Serial')->where(array('serial_number'=>$serial_number))->find();
			$this->display();
		}
	}
	public function serial_query(){
		$serial_number = trim($_POST['serial_number']);
		$where = array();
		if ($serial_number) {
			$where['serial_number'] = $serial_number;
		}
		$count = M('Serial')->where($where)->count();
		$page = new Page($count,10);
		$list = M('Serial')->where($where)->limit($page->firstRow.','.$page->listRows)->select();
		foreach ($list as $key => $val){
			$list[$key]['Goods'] = D('Goods')->relation(true)->where(array('goods_id'=>$val['goods_id']))->find();
		}
		$this->list = $list;
		$this->page = $page->show();
		$this->search = $_GET;
		$this->display();
	}
	public function get_goods_type()
	{
	   $GoodsClass = M("GoodsClass");	
       $gc_id = intval($_GET['gc_id']);	
	   $gc = $GoodsClass->where('gc_id='.$gc_id)->find();
	   if($gc['gc_parent_id'] == 16 || $gc['gc_parent_id'] == 18 || $gc_id == 16 || $gc_id == 18)
	   {
		   if($gc['gc_parent_id'] == 16 || $gc_id == 16)
		   {
			   $info = '<input name="goods_type" type="radio" value="大房" />大房
						<input name="goods_type" type="radio" value="双人房" />双人房
						<input name="goods_type" type="radio" value="家庭套房"/>家庭套房'; 
		   }else{
			   $info = '<input name="goods_type" type="radio" value="单人餐" />单人餐
						<input name="goods_type" type="radio" value="双人餐" />双人餐
						<input name="goods_type" type="radio" value="3-4人" />3-4人
						<input name="goods_type" type="radio" value="5-6人" />5-6人
						<input name="goods_type" type="radio" value="其他" />其他';			   
		   }
	   }else{
		   $info = '';
	   }	
	   header("Cache-Control: no-cache");  
	   echo $info; 	
	}
	//在线编辑	
	public function ajax()
	{
		$id = intval($_GET['id']);
		switch(trim($_GET['branch']))
		{
			case 'gc_sort':
			M('GoodsClass')->where('gc_id='.$id)->setField($_GET['column'],intval($_GET['value']));
			break;
			case 'gc_name':
			M('GoodsClass')->where('gc_id='.$id)->setField($_GET['column'],trim($_GET['value']));
			break;	
			case 'goods_sort':
			M('Goods')->where('goods_id='.$id)->setField($_GET['column'],intval($_GET['value']));
			break;
			case 'goods_price':
			M('Goods')->where(array('goods_id'=>$id))->setField($_GET['column'],floatval($_GET['value']));
			break;
			case 'goods_storage':
			M('Goods')->where(array('goods_id'=>$id))->setField($_GET['column'],intval($_GET['value']));
			break;
			case 'limit_days':
			M('GoodsLimit')->where(array('limit_id'=>$id))->setField($_GET['column'],intval($_GET['value']));
			break;
			case 'limit_num':
			M('GoodsLimit')->where(array('limit_id'=>$id))->setField($_GET['column'],intval($_GET['value']));
			break;
			case 'limit_price':
			M('GoodsLimit')->where(array('limit_id'=>$id))->setField('price',floatval($_GET['value']));
			break;
			case 'limit_sort':
			M('GoodsLimit')->where(array('limit_id'=>$id))->setField('sort',intval($_GET['value']));
			break;
			case 'select_sort':
			M('VipSelect')->where(array('select_id'=>$id))->setField($_GET['column'],intval($_GET['value']));
			break;
			case 'select_name':
			M('VipSelect')->where(array('select_id'=>$id))->setField($_GET['column'],str_rp(trim($_GET['value'])));
			break;
			case 'select_tip':
			M('VipSelect')->where(array('select_id'=>$id))->setField($_GET['column'],str_rp(trim($_GET['value'])));
			break;
			case 'tip_content':
			M('PublishTip')->where(array('tip_id'=>$id))->setField($_GET['column'],str_rp(trim($_GET['value'])));
			break;
			case 'tip_time':
			M('PublishTip')->where(array('tip_id'=>$id))->setField($_GET['column'],intval($_GET['value']));
			break;
			case 'brand_sort':
			M('GoodsBrand')->where(array('brand_id'=>$id))->setField($_GET['column'],intval($_GET['value']));
			break;
			case 'brand_name':
			M('GoodsBrand')->where(array('brand_id'=>$id))->setField($_GET['column'],str_rp(trim($_GET['value'])));
			break;
			case 'goods_num':
			M('Discount')->where(array('id'=>$id))->setField($_GET['column'],floatval($_GET['value']));
			break;
			case 'goods_price_rate':
			M('Discount')->where(array('id'=>$id))->setField($_GET['column'],floatval($_GET['value']));
			break;
		}
	}
}