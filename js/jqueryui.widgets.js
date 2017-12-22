jQuery(function(){

	var FileAdd = function(){
		var $input = $(this).addClass('jNiceHidden').wrap('<div class="jNiceFile"></div>');
		var $inner = $input.parent();
		var title = $(this).attr('title');
		var $asButton = $('<span><span>'+ title +'</span></span>');
		$inner.prepend($asButton);
		var $wrapper = $inner.wrap('<div class="jNiceFileWrapper"></div>').parent();
		$wrapper.append('<div class="jNiceFilePath"></div>');

		$(this).change(function() {
			value = $(this).attr('value');
			$wrapper.find('.jNiceFilePath').html(value);
		});
	};

	var ButtonAdd = function(){
        //var value = $(this).attr('value');
        //var dsbl = '';
        var dsblClass = '';
        if ($(this).attr("disabled")) {
            //dsbl = 'disabled="disabled"';
            dsblClass = ' jNiceButtonDisabled';
        }
        /*$(this).replaceWith('<div class="jNiceButton'+ dsblClass +' '+ this.className +'"><div><button id="'+ this.id +'" name="'+ this.name +'" type="'+ this.type +'" class="'+ this.className +'" value="'+ value +'"'+ dsbl +'>'+ value +'</button></div></div>');*/
        $(this).wrap('<div class="jNiceButton'+ dsblClass +' '+ this.className +'"><div></div></div>');
        $(this).addClass('jNiceButtonInput');
    };
    
    var CheckAdd = function(){
        var $input = $(this).addClass('jNiceHidden').wrap('<span class="jNiceWrapper jCheckboxWrapper"></span>');
        var $wrapper = $input.parent().append('<span class="jNiceCheckbox"></span>');
        /* Click Handler */
        var $a = $wrapper.find('.jNiceCheckbox').click(function(){
                var $a = $(this);
                var input = $a.siblings('input')[0];
                if (input.checked===true){
                    input.checked = false;
                    $a.removeClass('jNiceChecked');
                }
                else {
                    input.checked = true;
                    $a.addClass('jNiceChecked');
                }
                return false;
        });
        $input.click(function(){
            if(this.checked){ $a.addClass('jNiceChecked'); }
            else { $a.removeClass('jNiceChecked'); }
        }).focus(function(){ $a.addClass('jNiceFocus'); }).blur(function(){ $a.removeClass('jNiceFocus'); });
        
        /* set the default state */
        if (this.checked){$('.jNiceCheckbox', $wrapper).addClass('jNiceChecked');}
    };
    
	$('form.jNice input[type=text], form.jNice input[type=password]').addClass('jNiceInput').wrap('<div class="jNiceInputWrapper"><div class="jNiceInputInner"/></div>');
	$('form.jNice .jNiceInputWrapper:has(input.datePicker)').addClass('datePicker');
    $('form.jNice input[type=file]').each(FileAdd);
    $('form.jNice input:submit, form.jNice input:reset, form.jNice input:button').each(ButtonAdd);
    $('form.jNice button').focus(function(){ $(this).addClass('jNiceFocus')}).blur(function(){ $(this).removeClass('jNiceFocus')});
    $('form.jNice input:checkbox').each(CheckAdd);
    $('form.jNice select:not(.noJNice)').combobox();
    $('.jNiceHidden').css({opacity:0});
});