<?php
namespace Mobile\Controller;
use Think\Controller;
class WeixinController extends Controller {
	const TOKEN = 'mxdmxd';
	public function valid()
	{
		$echoStr = $_GET["echostr"];
		if($this->checkSignature()){
			echo $echoStr;
			exit;
		}
		//$this->responseMsg();
	}

	//消息处理
	public function responseMsg()
	{
		//get post data, May be due to the different environments
		//接收数据
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"]; //获取POST数据

		//extract post data
		if(!empty($postStr))
		{
			$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);  //用SimpleXML解析POST过来的XML数据
			$fromUsername = $postObj->FromUserName;    //获取发送方帐号(OpenID)
			$toUsername = $postObj->ToUserName;        //获取接收方账号
			$keyword = trim($postObj->Content);        //获取消息内容
			$msgType = $postObj->MsgType;              //消息类型

			//返回数据处理

			//消息处理类型
			if($msgType == 'text')
			{
				if(!empty($keyword))
				{
					switch ($keyword)
					{
						case '100':
							$contentStr  = '0755';
							$resultStr = $this->seedTextMessage($fromUsername,$toUsername,$contentStr);
							break;
						default:
							$contentStr  = '您想说什么呢';
							$resultStr = $this->seedTextMessage($fromUsername,$toUsername,$contentStr);
					} //switch end
				}else{
					echo '你想说点什么？';
				}

			}

			//地理位置处理类型
			if($msgType == 'location')
			{
				$db = C('DB_PREFIX')."distance_".$fromUsername;
				M()->execute("DROP TABLE IF EXISTS ".$db);
				$lat = $postObj->Location_X;
				$lng = $postObj->Location_Y;
				$list = M('Store')->where(array('store_status'=>1))->select();
				if (is_array($list)) {
					foreach ($list as $key => $val){
						$list[$key]['distance'] = getDistance($lat, $lng, $val['store_lat'], $val['store_lng']);
					}
				}
				$sql = "CREATE TEMPORARY TABLE ".$db." (
								  `store_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
								  `store_name` varchar(255) DEFAULT NULL ,
								  `store_tel` varchar(14) DEFAULT NULL ,
								  `store_address` varchar(255) DEFAULT NULL,
							      `store_lat` varchar(255) DEFAULT NULL,
  								  `store_lng` varchar(255) DEFAULT NULL,
								  `store_distance` int(11) DEFAULT NULL,
								  `store_num` varchar(255) DEFAULT NULL,
								  `store_pic` varchar(255) DEFAULT NULL,
								PRIMARY KEY (`store_id`)
								) ENGINE=MyISAM DEFAULT CHARSET=utf8";
				$res = M()->execute($sql);
				foreach ($list as $k => $v){
					$sql = "INSERT INTO ".$db." (`store_id`, `store_name`,`store_tel`,`store_address`,`store_lat`,`store_lng`,`store_distance`,`store_num`,`store_pic`) VALUES (".$v['store_id'].", '".$v['store_name']."','".$v['store_tel']."','".str_replace('中国', '', $v['store_address'])."','".$v['store_lat']."','".$v['store_lng']."','".$v['distance']."','".$v['store_num']."','".$v['store_pic']."')";
					M()->execute($sql);
				}
				$item_str = '
							<item>
		                      <Title><![CDATA[您附近的文学荟门店]]></Title>
		                      <PicUrl><![CDATA[http://www.ggmmww.com/Public/static/defualt_img/WX_QD.png]]></PicUrl>
		                      <Url><![CDATA['.C('SiteUrl').']]></Url>
		                    </item>';
				$list = M()->query("select * from ".$db." ORDER BY store_distance LIMIT 4");
				foreach ($list as $key => $val){
					$url = 'http://api.map.baidu.com/marker?location='.$val['store_lat'].','.$val['store_lng'].'&title='.$val['store_name'].'&content='.$val['store_address'].'&output=html';
					$item_str .= '
				                 <item>
				                    <Title><![CDATA['.$val['store_name']."  TEL:".$val['store_tel']."\n".$val['store_address'].']]></Title>
				                    <Url><![CDATA['.$url.']]></Url>
			                     </item>';
				}
				M()->execute("DROP TABLE ".$db);
				$resultStr = $this->sendPicTextMessage($fromUsername, $toUsername, $item_str, 5);
			}

			//事件处理类型
			if($msgType == 'event')
			{
				$event = $postObj->Event; //事件类型
				switch($event)
				{
					//关注事件
					case 'subscribe':
						// $EventKey = $postObj->EventKey;
						$contentStr  = '谢谢您的关注！';
						$resultStr = $this->seedTextMessage($fromUsername,$toUsername,$contentStr);
						break;
					case "unsubscribe":
						$contentStr = "取消关注";
						break;
					//已关注的用户 扫描二维码时
					case 'SCAN':
						$contentStr = '您好，欢迎回来！';
						$resultStr = $this->seedTextMessage($fromUsername,$toUsername,$contentStr);
						break;
					//自定义菜单点击事件
					case 'CLICK':
						$EventKey = $postObj->EventKey; //事件KEY值
						if($EventKey == 'M1001_1') //点餐系统
						{
							$contentStr = '点餐系统正在开发中...';
						}elseif($EventKey == 'M1001_2'){ //营销插件
							$contentStr = '众多营销插件正在水深火热的测试中...';
						}
						$resultStr = $this->seedTextMessage($fromUsername,$toUsername,$contentStr);
						break;
					case "LOCATION":
						$db = C('DB_PREFIX')."distance_".$fromUsername;
						M()->execute("DROP TABLE IF EXISTS ".$db);
						$lat = $postObj->Location_X;
						$lng = $postObj->Location_Y;
						$list = M('Store')->where(array('store_status'=>1))->select();
						if (is_array($list)) {
							foreach ($list as $key => $val){
								$list[$key]['distance'] = getDistance($lat, $lng, $val['store_lat'], $val['store_lng']);
							}
						}
						$sql = "CREATE TEMPORARY TABLE ".$db." (
										  `store_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
										  `store_name` varchar(255) DEFAULT NULL ,
										  `store_tel` varchar(14) DEFAULT NULL ,
										  `store_address` varchar(255) DEFAULT NULL,
									      `store_lat` varchar(255) DEFAULT NULL,
		  								  `store_lng` varchar(255) DEFAULT NULL,
										  `store_distance` int(11) DEFAULT NULL,
										  `store_num` varchar(255) DEFAULT NULL,
										  `store_pic` varchar(255) DEFAULT NULL,
										PRIMARY KEY (`store_id`)
										) ENGINE=MyISAM DEFAULT CHARSET=utf8";
						$res = M()->execute($sql);
						foreach ($list as $k => $v){
							$sql = "INSERT INTO ".$db." (`store_id`, `store_name`,`store_tel`,`store_address`,`store_lat`,`store_lng`,`store_distance`,`store_num`,`store_pic`) VALUES (".$v['store_id'].", '".$v['store_name']."','".$v['store_tel']."','".str_replace('中国', '', $v['store_address'])."','".$v['store_lat']."','".$v['store_lng']."','".$v['distance']."','".$v['store_num']."','".$v['store_pic']."')";
							M()->execute($sql);
						}
						$item_str = '
									<item>
				                      <Title><![CDATA[您附近的文学荟门店]]></Title>
				                      <PicUrl><![CDATA[http://www.ggmmww.com/Public/static/defualt_img/WX_QD.png]]></PicUrl>
				                      <Url><![CDATA['.C('SiteUrl').']]></Url>
				                    </item>';
						$list = M()->query("select * from ".$db." ORDER BY store_distance LIMIT 4");
						foreach ($list as $key => $val){
							$url = 'http://api.map.baidu.com/marker?location='.$val['store_lat'].','.$val['store_lng'].'&title='.$val['store_name'].'&content='.$val['store_address'].'&output=html';
							$item_str .= '
						                 <item>
						                    <Title><![CDATA['.$val['store_name']."  TEL:".$val['store_tel']."\n".$val['store_address'].']]></Title>
						                    <Url><![CDATA['.$url.']]></Url>
					                     </item>';
						}
						M()->execute("DROP TABLE ".$db);
						$resultStr = $this->sendPicTextMessage($fromUsername, $toUsername, $item_str, 5);
						break;
					/* case "scancode_waitmsg":
                        $content = "扫码带提示：类型 ".$object->ScanCodeInfo->ScanType." 结果：".$object->ScanCodeInfo->ScanResult;
                        break;
                    case "scancode_push":
                        $content = "扫码推事件";
                        break;
                    case "pic_sysphoto":
                        $content = "系统拍照";
                        break;
                    case "pic_weixin":
                        $content = "相册发图：数量 ".$object->SendPicsInfo->Count;
                        break;
                    case "pic_photo_or_album":
                        $content = "拍照或者相册：数量 ".$object->SendPicsInfo->Count;
                        break;
                    case "location_select":
                        $content = "发送位置：标签 ".$object->SendLocationInfo->Label;
                        break; */
					//自定义菜单跳转事件
					/*						 case 'VIEW':
                                                $EventKey = $postObj->EventKey; //事件URL值
                                                break;	*/
				}  //switch end

			}
			echo $resultStr;  //输出结果
		}else{
			echo "";
			exit;
		}
	}

	//发送普通消息
	public function seedTextMessage($fromUsername,$toUsername,$contentStr)
	{
		$msg_type = 'text';
		$textTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[%s]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					<FuncFlag>0</FuncFlag>
					</xml>";

		$resultStr = sprintf($textTpl, $fromUsername, $toUsername, NOW_TIME, $msg_type, $contentStr);
		return $resultStr;
	}

	//发送图片消息
	public function sendPicTextMessage($fromUsername,$toUsername,$item_str,$item_count)
	{
		$msg_type ='news';
		$textTpl="<xml>
				  <ToUserName><![CDATA[%s]]></ToUserName>
			      <FromUserName><![CDATA[%s]]></FromUserName>
				  <CreateTime>%s</CreateTime>
				  <MsgType><![CDATA[%s]]></MsgType>
				  <ArticleCount>%s</ArticleCount>
				  <Articles>$item_str</Articles>
				  </xml>";
		$resultStr = sprintf($textTpl, $fromUsername, $toUsername, NOW_TIME, $msg_type, $item_count);
		return $resultStr;
	}

	//服务器配置验证
	private function checkSignature()
	{
		// you must define TOKEN by yourself
		$token = self::TOKEN;
		if (!$token) {
			throw new Exception('TOKEN is not defined!');
		}

		$signature = $_GET["signature"];
		$timestamp = $_GET["timestamp"];
		$nonce = $_GET["nonce"];

		$tmpArr = array($token, $timestamp, $nonce);
		// use SORT_STRING rule
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );

		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}

}