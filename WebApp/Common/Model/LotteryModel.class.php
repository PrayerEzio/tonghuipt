<?php
/**
 * 抽奖模型
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 */
namespace Common\Model;
use Think\Model\RelationModel;
class LotteryModel extends RelationModel{
	protected $_link = array(
		'LotteryAward' => array(
				'mapping_type' => self::HAS_MANY,
				'class_name' => 'LotteryAward',
				'mapping_name' => 'LotteryAward',
				'foreign_key' => 'lottery_id',
		),
	);
}