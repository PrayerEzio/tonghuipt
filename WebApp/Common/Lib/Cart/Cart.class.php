<?php
/**
 * 购物车类
 * @copyright  Copyright (c) 2014-2015 muxiangdao-cn Inc.(http://www.muxiangdao.cn)
 * @license    http://www.muxiangdao.cn
 * @link       http://www.muxiangdao.cn
 * @author     muxiangdao-cn Team Prayer (283386295@qq.com)
 * @version    1.0.0
 */
namespace Common\Lib\Cart;
class Cart{
    public function __cunstruct() {
        if(!isset($_SESSION['cart'])){
            $_SESSION['cart'] = array();
        }
    }
 
    /**
     * 添加商品
     * @param int $id 商品主键
     * @param string $name 商品名称
     * @param float $price 商品价格
     * @param int $num 购物数量
     * @param string $img 商品图片
     * @param int $spec_id 规格id
     */
    public function addItem($id,$name,$price,$num,$img,$spec_id = 0) {
        //如果该商品已存在则直接加其数量
        if (isset($_SESSION['cart'][$id][$spec_id])) {
            $this->incNum($id,$spec_id,$num);
            return;
        }
        $item = array();
        $item['id'] = $id;
        $item['name'] = $name;
        $item['price'] = $price;
        $item['num'] = $num;
        $item['img'] = $img;
        $item['spec_id'] = $spec_id;
        $_SESSION['cart'][$id][$spec_id] = $item;
    }
 
    /**
     * 修改购物车中的商品数量
     * @param int $id 商品主键
     * @param int $num 某商品修改后的数量，即直接把某商品的数量改为$num
     * @return boolean
     */
    public function modNum($id,$spec_id = 0,$num=1) {
        if (!isset($_SESSION['cart'][$id][$spec_id])) {
            return false;
        }
        $_SESSION['cart'][$id][$spec_id]['num'] = $num;
    }
 
    /**
     * 增加商品数量
     * @param int $id 商品主键
     * @param number $num 增加数量,默认为1
     */
    public function incNum($id,$spec_id = 0,$num=1) {
        if (isset($_SESSION['cart'][$id][$spec_id])) {
            $_SESSION['cart'][$id][$spec_id]['num'] += $num;
        }
    }
 
    /**
     * 减少商品数量
     * @param int $id 商品主键
     * @param number $num 减少数量,默认为1
     */
    public function decNum($id,$spec_id = 0,$num=1) {
        if (isset($_SESSION['cart'][$id][$spec_id])) {
            $_SESSION['cart'][$id][$spec_id]['num'] -= $num;
        }
        //如果减少后，数量为0，则把这个商品删掉
        if ($_SESSION['cart'][$id][$spec_id]['num'] <1) {
            $this->delItem($id,$spec_id);
        }
    }
 
    /**
     * 删除商品
     * @param int $id 商品主键
     */
    public function delItem($id,$spec_id) {
        unset($_SESSION['cart'][$id][$spec_id]);
        if (empty($_SESSION['cart'][$id]))
        {
            unset($_SESSION['cart'][$id]);
        }
    }
    /**
     * 获取购物车所有商品
     */
    public function getList(){
    	return $_SESSION['cart'];
    }
     
    /**
     * 获取单个商品
     * @param int $id 商品主键
     */
    public function getItem($id,$spec_id = 0) {
        if (is_int($spec_id))
        {
            return $_SESSION['cart'][$id][$spec_id];
        }else {
            return $_SESSION['cart'][$id];
        }
    }
 
    /**
     * 查询购物车中商品的种类
     * @return number
     */
    public function getCnt() {
        return count($_SESSION['cart']);
    }
     
    /**
     *  查询购物车中商品的个数
     * @return number
     */
    public function getNum(){
        if ($this->getCnt() == 0) {
            //种数为0，个数也为0
            return 0;
        }
 
        $sum = 0;
        $data = $_SESSION['cart'];
        foreach ($data as $item) {
            foreach ($item as $value)
            {
                $sum += $value['num'];
            }
        }
        return $sum;
    }
 
    /**
     * 购物车中商品的总金额
     * @return float 
     */
    public function getPrice() {
        //数量为0，价钱为0
        if ($this->getCnt() == 0) {
            return 0;
        }
        $price = 0.00;
        $data = $_SESSION['cart'];
        foreach ($data as $item) {
            foreach ($item as $value)
            {
                $price += $value['num'] * $value['price'];
            }
        }
        return sprintf("%01.2f", $price);
    }
 
    /**
     * 清空购物车
     */
    public function clearCart() {
        $_SESSION['cart'] = array();
    }
}