<?php
/**
 * 商品模型
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Common\Model;
use Think\Model\RelationModel;
class GoodsModel extends RelationModel{
	protected $_link = array(
		'GoodsBrand' => array(
				'mapping_type' => self::BELONGS_TO,
				'class_name' => 'GoodsBrand',
				'mapping_name' => 'GoodsBrand',
				'foreign_key' => 'brand_id',
		),
		'GoodsPic' => array(
				'mapping_type' => self::HAS_MANY,
				'class_name' => 'GoodsPic',
				'mapping_name' => 'GoodsPic',
				'foreign_key' => 'goods_id',
		),
		'GoodsComment' => array(
				'mapping_type' => self::HAS_MANY,
				'class_name' => 'GoodsComment',
				'mapping_name' => 'GoodsComment',
				'foreign_key' => 'goods_id',
		),
		'GoodsSpec' => array(
				'mapping_type' => self::HAS_MANY,
				'class_name' => 'GoodsSpec',
				'mapping_name' => 'GoodsSpec',
				'foreign_key' => 'goods_id',
		),
		'GoodsClass' => array(
				'mapping_type' => self::BELONGS_TO,
				'class_name' => 'GoodsClass',
				'mapping_name' => 'GoodsClass',
				'foreign_key' => 'gc_id',
		),
	);
}