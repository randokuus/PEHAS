/*
	Scripts for "ISIC"
	Version:  24.11.10
*/


// Only on document ready
$(document).ready(function() {

/*---------- Attach calender ----------*/

	if ($('input.datePicker').length) {
		$('input.datePicker').datepicker({
			dateFormat: 'dd.mm.yy'
		});
	}

	$('#birthday').click(function() {
		$('#birthday').datepicker({dateFormat: 'dd.mm.yy'}).focus();
	});
	
	$('#ui-datepicker-div').hide();


/*---------- Add a decorative tags ----------*/

	$('div.selectopt').append('<i class="tl"></i><i class="tr"></i><i class="bl"></i><i class="br"></i>');

	$('div.box').append('<i class="tt"></i><i class="bb"></i><i class="ll"></i><i class="rr"></i><i class="tl"></i><i class="tr"></i><i class="bl"></i><i class="br"></i>');

	$('div.bpanel').append('<i class="bl"></i><i class="br"></i>');

	$('.icow, .ico, table.tList th span.sort, p.path a, div.pagin a.prev, div.pagin a.next').append('<i></i>');

	$('div.selectw a.select').append('<i></i><b></b>');
	$('p.msg').append('<i class="ico"></i><i class="tl"></i><i class="tr"></i><i class="bl"></i><i class="br"></i>');

	$('div.termsText').append('<i class="tbl"></i><i class="tbr"></i><i class="ttl"></i><i class="ttr"></i>');

	$('div.formTable div.fHint p').append('<i title="open hint">&hellip;</i><b title="close hint">&uarr;</b>');

});


/*---------- Popups ----------*/

$(function() {
	// For default popups are hidden
	var is_visible = false;

	// Show/hide popap when clicking on needed element
	$('a.select').click(function() {
		$(this).parent().find('.selectopt').toggle();
		is_visible = !is_visible;
		return false;
	});

	// Hide popaps when clicking outside
	$('body').click(function() {
		$('.selectopt').hide();
	});
});


/*---------- Add z-index for IE6/7 ----------*/

if ($.browser.msie && $.browser.version <= 7) {
	$(function() { 
		var zIndexNumber = 100; 
		$('div.box').each(function() { 
			$(this).css('zIndex', zIndexNumber); 
			zIndexNumber -= 1; 
		}); 
	});
}

if ($.browser.msie && $.browser.version == 6) {
	$(function() { 
		var zIndexNumber = 1000; 
		$('div.formTable div.fLine').each(function() { 
			$(this).css('zIndex', zIndexNumber); 
			zIndexNumber -= 1; 
		}); 
	});
}


/*---------- Add class for even rows ----------*/

function addClassToEven(evenEl,evenRows,evenOrder) {
	$(evenEl).each(function() {
		if(evenOrder && evenOrder=='reverse') {
			$(this).find(evenRows+':odd').removeClass('even').end()
					.find(evenRows+':even').addClass('even');
		} else {
			$(this).find(evenRows+':even').removeClass('even').end()
					.find(evenRows+':odd').addClass('even');
		}
	});
}

$(function() { 
	addClassToEven('table.tList','tr','reverse');
	addClassToEven('div.formTable','div.fRow');
	addClassToEven('ul.newslist','li');
	addClassToEven('ul.userlist','li');
	addClassToEven('div.selectopt ul','li');
});


/*---------- Switching checkboxes in the table ----------*/

function toggleCheck(is_checked) {
	$('.tList td input:checkbox:enabled').each(function() {
		this.checked = is_checked;
	});
}

$(function() {
	// If checkbox was checked
	if ($('#mainCheckbox').is(':checked')) {
		toggleCheck(true);
	}

	// Switching checkboxes
	$('#mainCheckbox').change(function() {
		if (this.checked) {
			toggleCheck(true);
		} else {
			toggleCheck(false);
		}
	});
});


/*---------- Multiple srelects control ----------*/

function moveOption(idFrom, idTo) {
	$(idFrom + ' option:selected').each(function() { 
		$(this).appendTo(idTo);
	});
}

$(function(){

	// Move options to second select:
	// by clicking on button
	$('#addGroup').click(function() {
		moveOption('#groupsFrom','#groupsTo');
	});

	// by double click on option
	$('#groupsFrom option').live('dblclick',function() {
		$(this).appendTo('#groupsTo');
	});


	// Move options to first select:
	// by clicking on button
	$('#removeGroup').click(function() { 
		moveOption('#groupsTo','#groupsFrom');
	});

	// by double click on option
	$('#groupsTo option').live('dblclick',function() {
		$(this).appendTo('#groupsFrom');
	});


	// Move options to second select:
	// by clicking on button
	$('#addUser').click(function() {
		moveOption('#usersFrom','#usersTo');
	});

	// by double click on option
	$('#usersFrom option').live('dblclick',function() {
		$(this).appendTo('#usersTo');
	});


	// Move options to first select:
	// by clicking on button
	$('#removeUser').click(function() { 
		moveOption('#usersTo','#usersFrom');
	});

	// by double click on option
	$('#usersTo option').live('dblclick',function() {
		$(this).appendTo('#usersFrom');
	});

	/*// Select needed options on submited
	$('#select_all').click(function() {
		$('#groupsTo option').attr('selected', 'selected');
	});*/
});


/*---------- Add icon to table rows ----------*/

$(function() {
	$('table.tLog .ico').each(function() { 
		var tRow = $(this).parents('tr');
		if (tRow.hasClass('tRowOk')) {
			$(this).addClass('iok').attr('title','Ok');
		} else if (tRow.hasClass('tRowNotOk')) {
			$(this).addClass('idel').attr('title','Not Ok');
		}
	});
});


/*---------- Toggle display sorting in table ----------*/

$(function() {
	$('table.tList th span.sort').click(function() {
		$(this).toggleClass('sortUp');
	});
});


/*---------- Toggle display hints in form ----------*/

$(function() {
	$('div.formTable div.fHint p i, div.formTable div.fHint p b').click(function() {
		$(this).parent().toggleClass('opened');
	});
});