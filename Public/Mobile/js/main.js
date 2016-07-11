$(function(){
    //select
    var brand = $("#brand");
    var next = $(".next");
    brand.change(function(){
        next.attr("disabled",false);
    });
    next.change(function(){
        location.href="detail.html";
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
    var style2 = $("#style2");
    var delivery = $(".delivery");
    var noDelivery = $("#noDelivery");
    style.change(function(){
        if($("#style2").is(":checked")){
            $(".style1").hide();
        }else{
            $(".style1").show();
        }
    });
    delivery.change(function(){
        if(noDelivery.is(":checked")){
            $(".noDelivery").show();
            delivery.hide();
        }else{
            $(".noDelivery").hide();
            delivery.show();
        }
    });
    //收货地址
    var address= $(".address");
    address.click(function(){
        address.removeClass("current");
        $(this).addClass("current");
    });
    //选择购物车中商品
    var choose = $(".choose");
    var all = $(".allCheck");
    all.click(function(){
        if($(this).is(":checked")){
            choose.each(function(){
                if($(this).not(":checked")){
                    $(this).attr("checked",true)
                }
            })
        }else{
            choose.each(function(){
                if($(this).is(":checked")){
                    $(this).attr("checked",false)
                }
            })
        }
    });
    //订单管理
    var item = $(".tab li");
    var items = $(".tabs");
    item.click(function(){
        item.removeClass("on");
        $(this).addClass("on");
        var index = $(this).index();
        items.hide();
        $(".tab"+index).show();
    });
    //编辑资料
    var mobil = $("#mobil");
    var yzm = $(".yzm");
    mobil.change(function(){
        yzm.show();
    });
    //商品详细与评价切换
    var menu = $(".menu li");
    var tab = $(".tab");
    menu.click(function(){
        menu.removeClass("current");
        $(this).addClass("current");
        var index = $(this).index();
        tab.hide();
        $(".tab"+index).show();
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
    //购买数量
    var add = $(".add");
    var cut = $(".cut");
    var many = $(".many");
    add.click(function(){
        many.val(many.val()*1+1);
    });
    cut.click(function(){
        many.val(many.val()*1-1);
        if(many.val()<2){
            many.val(1);
        }
    });
});

