<?php
/**
 * 首页
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
class IndexController extends BaseController{
	public function __construct(){
		parent::__construct();
	}
	public function index(){
		redirect(U('Shop/index'));
	}

	public function test()
	{
		$uid = intval($_GET['uid']);
		$loop = intval($_GET['loop']);
		$list = $this->getParentsMember($uid,'*',$loop);
		p($list);
	}

	public function qrcode()
	{
		$value = 'http://www.zhihu.com?from=tonghui'; //二维码内容
		$errorCorrectionLevel = 'L';//容错级别
		$matrixPointSize = 6;//生成图片大小
		vendor('phpqrcode.phpqrcode');
		$qrcode = new \QRcode();
		$qrcode->png($value,'./Uploads/qrcode/'.md5($value).'.png',$errorCorrectionLevel,$matrixPointSize,2);
		echo '<img src="'.__ROOT__.'/Uploads/qrcode/'.md5($value).'.png'.'">';
	}
}