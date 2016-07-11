<?php
/**
 * 公牌
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Mobile\Controller;
use Mobile\Controller\BaseController;
use Think\Page;
use Muxiangdao\DesUtils;
class BoardController extends BaseController{
	public function __construct(){
		parent::__construct();
		$this->check_login();
	}

	/**
	 * 公牌
	 */
	public function index()
	{

	}
}