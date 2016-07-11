<?php
namespace Common\Lib\Jdpay;
/**
 * 签名
 *
 * @author wylitu
 *        
 */
class SignUtil {
	public static $unSignKeyList = array (
			"merchantSign",
			"token",
			"version" 
	);
	public static function sign($params) {
		ksort($params);
  		$sourceSignString = self::signString ( $params, self::$unSignKeyList );
  		$sha256SourceSignString = hash ( "sha256", $sourceSignString );	
  		$RSAUtils = new RSAUtils();
        return $RSAUtils->encryptByPrivateKey($sha256SourceSignString);
	}
	public static function signString($params, $unSignKeyList) {
		// 拼原String
		$sb = "";
		// 删除不需要参与签名的属性
		foreach ( $params as $k => $arc ) {
			for($i = 0; $i < count ( $unSignKeyList ); $i ++) {
				
				if ($k == $unSignKeyList [$i]) {
					unset ( $params [$k] );
				}
			}
		}
		foreach ( $params as $k => $arc ) {
			
			$sb = $sb . $k . "=" . ($arc == null ? "" : $arc) . "&";
		}
		// 去掉最后一个&
		$sb = substr ( $sb, 0, - 1 );
		return $sb;
	}
}
?>