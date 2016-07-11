<?php
namespace Toadmin\Model;
use Think\Model\RelationModel;
class MemberAccountModel extends RelationModel{
	protected $_link = array(
		'Member' => array(
				'mapping_type' => self::BELONGS_TO,
				'foreign_key' => 'member_id',
		),
		'Platform' => array(
				'mapping_type' => self::BELONGS_TO,
				'foreign_key' => 'pf_id',
		),
	);
}