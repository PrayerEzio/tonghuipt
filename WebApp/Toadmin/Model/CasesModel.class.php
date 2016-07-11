<?php
/**
 * 案例模型
 * @copyright  Copyright (c) 2014-2015 muxiangdao-com Inc.(http://www.muxiangdao.com)
 * @license    http://www.muxiangdao.com
 * @link       http://www.muxiangdao.com
 * @author     muxiangdao-com Team Prayer (283386295@qq.com)
 */
namespace Toadmin\Model;
use Think\Model\RelationModel;

class CasesModel extends RelationModel{
	protected $_link = array(
		'CasesClass' => array(             
		 'mapping_type' => self::BELONGS_TO,         
		 'class_name' => 'CasesClass', 
		 'foreign_key' => 'cc_id',			 
		),
	); 	
}
