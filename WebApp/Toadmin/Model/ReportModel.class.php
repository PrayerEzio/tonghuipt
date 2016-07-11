<?php
namespace Toadmin\Model;
use Think\Model\RelationModel;
class ReportModel extends RelationModel{
	protected $_link = array(
		'ReportClass' => array(
				'mapping_type' => self::BELONGS_TO,
				'class_name' => 'ReportClass',
				'mapping_name' => 'ReportClass',
				'foreign_key' => 'rp_class_id',
		),
		'Seller' => array(
				'mapping_type' => self::BELONGS_TO,
				'class_name' => 'Seller',
				'mapping_name' => 'Seller',
				'foreign_key' => 'seller_id',
		),
		'Member' => array(
				'mapping_type' => self::BELONGS_TO,
				'class_name' => 'Member',
				'mapping_name' => 'Member',
				'foreign_key' => 'member_id',
		),
		'ReportDetail' => array(
				'mapping_type' => self::HAS_MANY,
				'class_name' => 'ReportDetail',
				'mapping_name' => 'Detail',
				'foreign_key' => 'report_id',
				'mapping_order' => 'addtime asc',
		),
	);
}