
(function($){
$(document).ready(function(){
	
	/*导航*/
	$(".navClick").on('click',function(){
		var $cate=$(".navList");
		if($cate.is(":visible")){
			$cate.slideUp(100);
		}else{
			$cate.slideDown(100);
		};
	});
	/*导航*/
	
	/*数量*/
	$('.increase').on('click',function(){
		var v = $(this).siblings('.amount').val();
		if(v>1){
		v--;
		$(this).siblings('.amount').val(v);}
	});
	$('.reduce').click(function(){
		var v = $(this).siblings('.amount').val();
		v++;
		$(this).siblings('.amount').val(v);
	});
	/*数量*/
	
	/*全选*/
	$(".CKAll").click(function(){
		$('input[name=items]:checkbox').attr("checked", this.checked );
	});
	$('input[name=items]:checkbox').click(function(){
		var $tmp=$(this).parents(".musicList").children("li").children("a").children('input[name=items]:checkbox');
		$('.CKAll').attr('checked',$tmp.length==$tmp.filter(':checked').length);
	});
	/*全选*/
	
	/*Tab 切换*/
	$(".tabList li").on('click',function(){
		var index=$(this).index();
		$(this).siblings("li").children("a").removeClass("on");
		$(this).children("a").addClass("on");
		$(this).parents(".tabList").next(".tabBox").children(".tbCont").eq(index).show().siblings().hide();
		return false;
	});
	/*Tab 切换*/
	
	/*弹出层*/
	$(".tclose").on('click',function(){
		$(".tcBox").hide();
	})
	$(".tcBox").on('click',function(){
		$(this).hide();
	})
	$(".tcBox").on('click','.dibox',function(){
		if (window.event) {
		e.cancelBubble=true;
		} else {
		e.stopPropagation();
		}
	})
	/*弹出层*/
	
	$('.sel-tl').on('click',function(){
		$this = $(this);
		$next = $this.next();
		if($next.is(':visible')){
			$next.slideUp(100);
		}else{
			$next.slideDown(100);
		}
	});

});
})(jQuery);

