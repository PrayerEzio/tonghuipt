<?php
namespace Toadmin\Model;
use Think\Model\RelationModel;
class SellerModel extends RelationModel{
	protected $_link = array(
		'SellerAccount' => array(
				'mapping_type' => self::HAS_MANY,
				'class_name' => 'SellerAccount',
				'mapping_name' => 'Account',
				'foreign_key' => 'seller_id',
		),
		'SellerVip' => array(
				'mapping_type' => self::HAS_MANY,
				'class_name' => 'SellerVip',
				'mapping_name' => 'SellerVip',
				'foreign_key' => 'seller_id',
				'mapping_order' => 'end_time desc',
		),
		'SellerOrder' => array(
				'mapping_type' => self::HAS_MANY,
				'class_name' => 'SellerOrder',
				'mapping_name' => 'SellerOrder',
				'foreign_key' => 'seller_id',
				'mapping_order' => 'add_time desc',
		),
		'SellerBill' => array(
				'mapping_type' => self::HAS_MANY,
				'class_name' => 'SellerBill',
				'mapping_name' => 'SellerBill',
				'foreign_key' => 'seller_id',
				'mapping_order' => 'addtime desc',
		),
		'SellerMessage' => array(
				'mapping_type' => self::HAS_MANY,
				'class_name' => 'SellerMessage',
				'mapping_name' => 'SellerMessage',
				'foreign_key' => 'seller_id',
				'mapping_order' => 'addtime desc',
		),	
	);
}