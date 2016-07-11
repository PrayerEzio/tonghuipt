<?php
/**
 * 维修模型
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Common\Model;
use Think\Model\RelationModel;
class RepairModel extends RelationModel{
	protected $_link = array(
		'Goods' => array(
				'mapping_type' => self::BELONGS_TO,
				'class_name' => 'Goods',
				'mapping_name' => 'Goods',
				'foreign_key' => 'goods_id',
		),
		'Member' => array(
				'mapping_type' => self::BELONGS_TO,
				'class_name' => 'Member',
				'mapping_name' => 'Member',
				'foreign_key' => 'member_id',
		),
		'Order' => array(
				'mapping_type' => self::BELONGS_TO,
				'foreign_key' => 'order_id',
		),
		'Breakdown' => array(
				'mapping_type' => self::BELONGS_TO,
				'class_name' => 'Breakdown',
				'mapping_name' => 'Breakdown',
				'foreign_key' => 'bd_id',
		),
		'RepairLog' => array(
				'mapping_type' => self::HAS_MANY,
				'foreign_key' => 'rp_id',
				'mapping_order' => 'log_time',
		),
		'RepairMenu' => array(
				'mapping_type' => self::HAS_MANY,
				'foreign_key' => 'rp_id',
		)
	);
}