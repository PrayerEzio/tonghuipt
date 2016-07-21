$(function(){

	changeFont();

	$(window).resize(function(){
		 changeFont();
	});

function changeFont(){
	var fontSize;
	var $h = $("html");
	fontSize = $("body").width()/10;
	$h.css("font-size",fontSize);
}
});