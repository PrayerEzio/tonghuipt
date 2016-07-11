<?php
namespace Toadmin\Model;
use Think\Model\RelationModel;
class SellerMessageModel extends RelationModel{
	protected $_link = array(
		'Seller' => array(
				'mapping_type' => self::BELONGS_TO,
				'class_name' => 'Seller',
				'mapping_name' => 'Seller',
				'foreign_key' => 'seller_id',
		),
	);
}