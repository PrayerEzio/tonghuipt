$(function(){
	var tophtml="<div id=\"izl_rmenu\" class=\"izl-rmenu\"><a href=\"tencent://Message/?Uin=1821308909&websiteName=sc.chinaz.com=&Menu=yes\" class=\"btn btn-qq\"></a><div class=\"btn btn-wx\"><img class=\"pic\" src=\"http://www.zorsika.com/Uploads/common/weixin_qrcode.png\"/></div><div class=\"btn btn-phone\"><div class=\"phone\">4008-552-336</div></div><div class=\"btn btn-top\"></div></div>";
	$("#top").html(tophtml);
	$("#izl_rmenu").each(function(){
	    $(this).find(".btn-wx").mouseenter(function(){
	        $(this).find(".pic").fadeIn("fast");
	    });
	    $(this).find(".btn-wx").mouseleave(function(){
	        $(this).find(".pic").fadeOut("fast");
	    });
	    $(this).find(".btn-phone").mouseenter(function(){
	        $(this).find(".phone").fadeIn("fast");
	    });
	    $(this).find(".btn-phone").mouseleave(function(){
	        $(this).find(".phone").fadeOut("fast");
	    });
	    $(this).find(".btn-top").click(function(){
	        $("html, body").animate({
	            "scroll-top":0
	        },"fast");
	    });
	});
	var lastRmenuStatus=false;
	$(window).scroll(function(){//bug
	    var _top=$(window).scrollTop();
	    if(_top>200){
	        $("#izl_rmenu").data("expanded",true);
	    }else{
	        $("#izl_rmenu").data("expanded",false);
	    }
	    if($("#izl_rmenu").data("expanded")!=lastRmenuStatus){
	        lastRmenuStatus=$("#izl_rmenu").data("expanded");
	        if(lastRmenuStatus){
	            $("#izl_rmenu .btn-top").slideDown();
	        }else{
	            $("#izl_rmenu .btn-top").slideUp();
	        }
	    }
	});
    //select
    var brand = $("#brand");
	var next1 = $(".next1");
    var next2 = $(".next2");
    var gc = $("#gc").val();
    var viewhtml = $("#viewhtml").val();
	getGoods();
    brand.change(function(){
    	getGoods();
    });
    next1.change(function(){
    	var goods_id = $(".next1").val();
    	if (viewhtml == 'index'){
    		location.href="/Goods/detail/goods_id/"+goods_id;
    	}
    	if (viewhtml == 'theatre'){
    		
    	}
    });
    next2.change(function(){
    	var spec_id = $(".next2").val();
        if (viewhtml == 'index'){
        	location.href="/Goods/detail/spec_id/"+spec_id;
    	}
    	if (viewhtml == 'theatre'){
    		location.href="/Goods/thratre/spec_id/"+spec_id;
//    		getSpecInfo();
    	}
    });

    // Carousel
    $(function() {
        $('#listing').carouFredSel({
            prev: '#prev_c1',
            next: '#next_c1',
            scroll: 1,
            auto: false
        });
        $('#list_banners').carouFredSel({
            prev: '#ban_next',
            next: '#ban_prev',
            pagination  : "#ban_pagination",
            scroll: 1,
            auto: false
        });
        $('#thumblist').carouFredSel({
            prev: '#img_prev',
            next: '#img_next',
            pagination  : '#pagination',
            auto: false
        });
        $(window).resize();
    });
    //接收维修品
    var style = $(".style");
    var style1 = $(".style1");
    var noDelivery = $(".noDelivery");
    var delivery = $(".delivery");
    var other = $(".other");
    style.click(function(){
        if($("#style2").is(":checked")){
            style1.hide()
        }else{
            style1.show()
        }
    });
    noDelivery.click(function(){
    	$("#customDelivery").val(1);
        delivery.hide();
        other.show();
    });
    //收货地址
    var address= $(".address");
    address.click(function(){
        address.removeClass("current");
        $(this).addClass("current");
        $("#addr_id").val($(this).attr('addr_id'));
    });
    //选择购物车中商品
    var choose = $(".choose");
    var all = $(".allCheck");
    var useDikou = $(".useDikou");
    all.click(function(){
        if($(this).is(":checked")){
            choose.each(function(){
                if($(this).not(":checked")){
                    $(this).attr("checked",true)
                }
            });
        }else{
            choose.each(function(){
                if($(this).is(":checked")){
                    $(this).attr("checked",false)
                }
            })
        }
    });
    getPrice();
    choose.click(function(){
    	getPrice();
    });
    useDikou.click(function(){
    	getPrice();
    });
    //注册
    var email = $(".email");
    var mobile = $(".mobile");
    var regirstE = $(".regirstE");
    var regirstM = $(".regirstM");
    regirstE.click(function(){
        mobile.removeClass("hide");
        email.addClass("hide");
        $("#s_class").val('email');
    });
    regirstM.click(function(){
        email.removeClass("hide");
        mobile.addClass("hide");
        $("#s_class").val('mobile');
    });
    //编辑资料
    var onlyShow = $(".onlyShow");
    var editInfo = $(".editInfo");
    var saveInfo = $(".saveInfo");
    var mobil = $("#mobil");
    var mobil1 = $("#mobil1");
	$("#file").change(function(){
		editInfo.hide();
        saveInfo.show();
        onlyShow.removeClass("onlyShow");
        onlyShow.attr("disabled",false);
	});
    editInfo.click(function () {
        $(this).hide();
        saveInfo.show();
        onlyShow.removeClass("onlyShow");
        onlyShow.attr("disabled",false);
    });
    mobil.change(function(){
        $("#get").show();
    });
    mobil1.change(function(){
        $("#get1").show();
    });
    //评价打星
    var star = $(".star a");
    star.hover(function(){
        $(this).parent(".star").find("a").removeClass("plus");
        var n = $(this).index();
        for(m=1;m<=n;m++){
            $(this).addClass("plus");
            $(this).siblings(".star"+m).addClass("plus");
        }
    });
    //打印报告
    var print = $(".print");
    print.click(function(){
        $("#report").jqprint({});
    });
    //管理收货地址
    var close = $(".close");
    var addressNew = $(".addressNew");
    var addressBtn = $(".addressBtn");
    var cash = $(".cash");
    addressBtn.click(function(){
        addressNew.slideDown();
    });
    cash.click(function(){
        addressNew.slideDown();
    });
    close.click(function(){
        addressNew.slideUp();
    });
    //故障筛选
    var dalei = $("input[name='dalei']");
    var erlei = $("#erlei");
    dalei.click(function(){
        erlei.show()
    });
    //获取验证码
    var wait=60;
    function time(o)
    {
        $(".yzm").show();
        if (wait == 0)
        {
            o.removeAttribute("disabled");
            o.value="获取验证码";
            wait = 60;
        } else {
            o.setAttribute("disabled", true);
            o.value="重新发送(" + wait + ")";
            wait--;
            setTimeout(function() {
                    time(o)
                },
                1000)
        }
    }
    $("#get").click(function(){
        time(this);
    });
    var wait1=60;
    function time1(o)
    {
        $(".yzm1").show();
        if (wait1 == 0)
        {
            o.removeAttribute("disabled");
            o.value="获取验证码";
            wait1 = 60;
        } else {
            o.setAttribute("disabled", true);
            o.value="重新发送(" + wait1 + ")";
            wait1--;
            setTimeout(function() {
                    time(o)
                },
                1000)
        }
    }
    $("#get1").click(function(){
        time1(this);
    });
    //购买数量
    var add = $(".add");
    var cut = $(".cut");
    var many = $(".many");
    var proNum = $("#proNum").html()*1;
    add.click(function(){
        if(many.val()*1<proNum){
            many.val(many.val()*1+1);
        }else{
            alert("购买数量不能大于库存")
        }
    });
    cut.click(function(){
        many.val(many.val()*1-1);
        if(many.val()*1<2){
            many.val(1);
        }
    });
    many.change(function(){

    	if($(this).val()>proNum){
    		$(this).val(proNum)
    	}
    });
    //评论
    var tab = $(".comment .tab li");
    var tabs = $(".tabs");
    tab.click(function(){
        tab.removeClass("current");
        $(this).addClass("current");
        var index = $(this).index();
        tabs.hide();
        $(".tab"+index).show();
    });
    //加入购物车
    var cart = $(".cart");
    cart.click(function(){
        var mainPic = $(".clone");
        var fly = mainPic.clone().css({
            'opacity': 0.75,
            'position': "absolute",
            'left':0,
            'top':0
        });
        $("#MagicZoom").prepend(fly);
        fly.animate({
            top:-225,
            left:1060,
            width: 20,
            height: 20
        }, 'slow', function() {
            fly.remove();
        });
    });
    function getPrice(){
    	var amount = 0;
    	var lastAmount = 0;
    	$(".choose").each(function(){
    		if($(this).is(":checked")){
    			var m = $(this).siblings("li").find(".xiaoji").html();
    			if (isNaN(m)){
    				m = 0;
    			}
    			amount = amount+m*1;
    		}
    	});
    	$(".totalBefore").html(amount);
    	if (amount == 0){
    		if($(".useDikou").is(":checked")){
    			$(".useDikou").attr("checked", false);
    		}
    	}
    	var dikou = $(".dikou").html();
    	if($(".useDikou").is(":checked")){
    		lastAmount = amount*1-dikou*1;
    	}else{
    		lastAmount = amount;
    	}
    	$(".total").html(lastAmount);
    }
    function getGoods(){
        var brand_id = $("#brand").val();
    	var URL = '/Index/getGoods';
    	var gc = $("#gc").val();
    	var data = {"brand_id":brand_id,"gc":gc};
    	$(".next_value").remove();
    	$.post(URL,data,function(res){
    		if (null != res){
    			var i = 0;
        		var next1_html = '';
        		var next2_html = '';
        		if (res.list != 'null'){
        			for(i;i<res.list.length;i++){
            			next1_html += '<option value="'+res.list[i].goods_id+'" class="next_value">'+res.list[i].goods_name+'</option>';
            			var k = 0;
            		}
        		}
        		if (res.GoodsSpec != 'null'){
        			var n = 0;
            		for(n;n<res.GoodsSpec.length;n++){
            			next2_html += '<option value="'+res.GoodsSpec[n].spec_id+'" class="next_value">'+res.GoodsSpec[n].spec_name+'</option>';
            		}
        		}
        		next1.append(next1_html);
        		next2.append(next2_html);
    		}
    	},'json');
        next1.attr("disabled",false);
        next2.attr("disabled",false);
    }
    function getSpecInfo(spec_id){
    	var URL = "/Index/getSpecInfo";
    	var data = {"spec_id":spec_id};
    	$.post(URL,data,function(result){
    		if (result.code != 200){
    			alert(result.msg);
    		}else {
    			
    		}
    	});
    }
});