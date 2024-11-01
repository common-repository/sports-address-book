(function( $ ) {
	'use strict';
/**
 * @link              http://creativform.com/
 * @version           1.0.5
 * @author            Ivijan-Stefan Stipic
 * @description       WordPress Category Filter
 * @require           Ben Alman's Debounce Plugin (http://benalman.com/projects/jquery-throttle-debounce-plugin/)
 */
	$(document).ready(function () {

        var $categoryDivs = $('.categorydiv'),
			sda = WP_CF_SDA;
		$categoryDivs.each(function(){
			var $categoryID = $(this).parent().parent().attr('id');
			var $categoryTitle = $(this).parent().parent().find("h2 > span").text();
	
			$(this).prepend('<input type="search" class="'+$categoryID+'-search-field" placeholder="' + sda.label.filter_title.replace(/%s/ig,$categoryTitle) + '" style="width: 100%" />');
	
			$(this).on('keyup search', '.'+$categoryID+'-search-field', $.debounce(300,function (event) {

				var searchTerm = event.target.value,
					$listItems = $(this).parent().find('.categorychecklist li');
	
				if ($.trim(searchTerm)) {
	
					$listItems.hide().filter(function () {
						return $(this).text().toLowerCase().indexOf(searchTerm.toLowerCase()) !== -1;
					}).show();
	
				} else {
	
					$listItems.show();
	
				}
	
			}));
		});
	});

})( window.jQuery || window.Zepto );
