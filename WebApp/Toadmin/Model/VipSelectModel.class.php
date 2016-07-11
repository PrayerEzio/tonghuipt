<?php
namespace Toadmin\Model;
use Think\Model\RelationModel;
class VipSelectModel extends RelationModel{
	protected $_link = array(
		'VipOption' => array(
				'mapping_type' => self::HAS_MANY,
				'class_name' => 'VipOption',
				'mapping_name' => 'option',
				'foreign_key' => 'select_id',
		),
	);
}