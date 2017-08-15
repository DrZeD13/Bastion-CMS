function limitChars(textid, min, limit, infodiv, type)
{
	var text = $('#'+textid).val();	
	var textlength = text.length;
	if(textlength > limit)
	{
			$('#'+textid).css('background', '#F2DEDE');
			if (type == "title")
			{
				$('#' + infodiv).html('Вы ввели '+ textlength +' символов. Рекомендуемое от 10 до 70 символов');
			}
			else
			{
				$('#' + infodiv).html('Вы ввели '+ textlength +' символов. Рекомендуемое от 70 до 160 символов');
			}	
		return false;
	}
	if (textlength < min)
	{
		$('#'+textid).css('background', '#F2DEDE');
		if (type == "title")
		{
			$('#' + infodiv).html('Вы ввели '+ textlength +' символов. Рекомендуемое от 10 символов');
		}
		else
		{
			$('#' + infodiv).html('Вы ввели '+ textlength +' символов. Рекомендуемое от 70 символов');
		}
		return false;
	}
	else
	{
		$('#'+textid).css('background', '#F5F5F5');
		$('#' + infodiv).html('Вы ввели '+ textlength +' символов');
		return true;
	}
}
/*
//если нужно что бы сразу выводилось
$(document).ready(function() {
	limitChars('description', 160, 'charlimitinfo');
});*/

$(function(){
	$('#description').keyup(function(){
		limitChars('description', 70, 160, 'charlimitinfo', 'description');
	})
});

$(function(){
	$('#head_title').keyup(function(){
		limitChars('head_title', 10, 70, 'charlimitinfotitle', 'title');
	})
});

//спойлер
$(document).ready(function(){
 $('.seo-spoiler-links').click(function(){
  $(this).parent().children('table.seo-spoiler-body').toggle('normal');
  return false;
 });
});

// красивый селект
$(document).ready(function(){
var config = {
  '.chosen-select'           : {},
}
for (var selector in config) {
  $(selector).chosen(config[selector]);
}
});

// вкладки
(function($) {
$(function() {

	$('ul.tabs').each(function(i) {
		var storage = localStorage.getItem('tab'+i);
		if (storage) $(this).find('li').eq(storage).addClass('current').siblings().removeClass('current')
			.parents('div.section').find('div.box').hide().eq(storage).show();
	})

	$('ul.tabs').on('click', 'li:not(.current)', function() {
		$(this).addClass('current').siblings().removeClass('current')
			.parents('div.section').find('div.box').eq($(this).index()).fadeIn(150).siblings('div.box').hide();
		var ulIndex = $('ul.tabs').index($(this).parents('ul.tabs'));
		// закоментировать две строчки что бы не сохранялаь последняя открытая вкладка при перезагрузке страницы
		//localStorage.removeItem('tab'+ulIndex);
		//localStorage.setItem('tab'+ulIndex, $(this).index());
	})

})

})(jQuery)

// кнопка выбрать все checkbox
$(document).ready(function(){
var flag = true;
$("#checkbox_all").click(function() {
		$('input[type="checkbox"]').prop('checked', flag);
		flag = (flag)?false:true;
	});
});

// подгоняем высоту тело под sidebar
$(function () {
	var footer_height = $('.main-footer').outerHeight() || 0;
	var sidebar_height = $(".main-sidebar").height() || 0;
    $(".content-wrapper").css('min-height', sidebar_height - footer_height);
});
// запускаем маску для форм
$(function() {
	$('input').inputmask();
});
// переключатель класса для sidebar
$(document).ready(function() {
  $("#menutoggle").click(function() {
    $("body").toggleClass("sidebar-collapse");
    $("body").toggleClass("sidebar-open");
  });
});