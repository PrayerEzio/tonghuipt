<?php
namespace Toadmin\Model;
use Think\Model\RelationModel;
class MemberWithdrawModel extends RelationModel{
	protected $_link = array(
		'Payment' => array(
				'mapping_type' => self::BELONGS_TO,
				'class_name' => 'Payment',
				'mapping_name' => 'Payment',
				'foreign_key' => 'payment_id',
		),
		'Member' => array(
				'mapping_type' => self::BELONGS_TO,
				'class_name' => 'Member',
				'mapping_name' => 'Member',
				'foreign_key' => 'member_id',
		),
	);
}