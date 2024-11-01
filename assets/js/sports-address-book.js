(function( $ ) {
	'use strict';
/**
 * @link              http://creativform.com/
 * @version           1.0.0
 * @author            Ivijan-Stefan Stipic
 * @description       Sports Address Book
 */
	/* Open Description */
	$("#sab-result-container .sab-parallax").on('click tap',function(e){
		e.preventDefault();
		var This = $(this),
			id = This.data('id'),
			el = $(id),
			plus = $('.' + id.replace(/\#/g,'') + '-plus');
			
			
		if(el.is(":visible"))
		{
			el.fadeOut(300,function(){
				plus.text('+');
			});
			
		}
		else
		{
			el.fadeIn(300,function(){
				plus.text(' - ');
			});
		}
	});
	
	/* Remove empty */
	$(document).ready(function(){
		$("#sab-result-container .sab-result").each(function(){
			$(".sab-data", this).each(function(){
				var el = $(this);
				
				if(!el.is(":empty")){
					el.parent().show();
				}
			});
		});
	});

})( window.jQuery || window.Zepto );
