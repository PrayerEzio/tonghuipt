<?php
namespace Toadmin\Model;
use Think\Model\RelationModel;
class SellerAccountModel extends RelationModel{
	protected $_link = array(
		'Seller' => array(
				'mapping_type' => self::BELONGS_TO,
				'foreign_key' => 'seller_id',
		),
		'Platform' => array(
				'mapping_type' => self::BELONGS_TO,
				'foreign_key' => 'pf_id',
		),
	);
}