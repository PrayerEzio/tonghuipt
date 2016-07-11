// 模拟网站LOGO上传input type='file'样式
$(function(){
	$("input[id^='goods_pic']").change(function(){
		$("#txt_goods_pic").val($(this).val());
	});
// 上传图片类型
$('input[class="type-file-file"]').change(function(){
	var filepatd=$(this).val();	
	var extStart=filepatd.lastIndexOf(".");
	var ext=filepatd.substring(extStart,filepatd.lengtd).toUpperCase();		
		if(ext!=".PNG"&&ext!=".GIF"&&ext!=".JPG"&&ext!=".JPEG"){
			alert("图片格式错误");
				$(this).attr('value','');
			return false;
		}
	});
});
