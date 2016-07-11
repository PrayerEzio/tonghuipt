<?php
/**
 * 预订模型
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Common\Model;
use Think\Model\RelationModel;
class ReserveModel extends RelationModel{
	protected $_link = array(
		'Member' => array(
				'mapping_type' => self::BELONGS_TO,
				'class_name' => 'Member',
				'mapping_name' => 'Member',
				'foreign_key' => 'member_id',
		),
		'Goods' => array(
				'mapping_type' => self::BELONGS_TO,
				'foreign_key' => 'goods_id',
		),
		'GoodsSpec' => array(
				'mapping_type' => self::BELONGS_TO,
				'foreign_key' => 'spec_id',
		),
	);
}