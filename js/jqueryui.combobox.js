/*$(function(){
	if (!window.console) {
		window.console = {
			log: function(msg) {
				window.console.el.append(msg + "<br/>");
			}
		};
		window.console.el = jQuery('<div style="position: absolute; top:0px; width: 100%; height: 80px; overflow:auto; border: 1px solid red; z-index: 10001"/>')
			.appendTo(document.body);
	}
	
});*/

(function( $ ) {
    $.widget( "ui.combobox", {
        _create: function() {
            var self = this,
            	selectWidth = this.element.width()
                select = this.element.hide(),
                selected = select.children(":selected"),
                text = selected.val() ? selected.text() : "";
            this.options = {
                inKeyMode: false
            }
            this.options.selectedIndex = select.get(0).selectedIndex;
            select.wrap('<div class="jNiceWrapperSelect"/>');
            this.element = $('<input type="hidden">')
            	.insertAfter(select)
            	.attr('name', select.attr('name'))
            	.attr('id', select.attr('id'))
            	.val(select.val());
            if (select.get(0).onchange) {
            	this.element.change(select.get(0).onchange);
            }
            this.options.selectedText = text;
            this.element.input = $("<input>")
            	.insertAfter(this.element)
	            .wrap('<div class="jNiceSelectWrapper"><div class="jNiceSelectCurrent"/>')
	            .val(text)
	            .addClass('jNiceInput')
	            .attr('autocomplete', 'off')
	            .focus(function(){
	                var input = this;
	                if (!self.isExpanded()) {
	                    setTimeout(function(){
	                    	input.select(); 
	                    }, 200);
	                }
	            })
	            .blur(function(e){
	            	if (self.options.noBlur) {
	            		delete self.options.noBlur;
	            		return;
	            	}
	            	if (!self.options.inSelect && self.isExpanded()) {
	                    $(this).val(self.options.selectedText);
	                    self.collapse();
	                    self.options.justBlured = true;
	                    setTimeout(function(){
	                    	self.options.justBlured = false;
	                    }, 200);
	                }
	            })
	            .click(function(){
	                if (!self.isExpanded()) {
	                    self.expand();
	                }
	            });
            if ($.browser.msie/* && $.browser.version == '6.0'*/) {
            	this.element.input.width(20);
            	var s = 12; 
            	if ($.browser.version == '6.0') {
            		s = 28;
            	}
            	this.element.input.width(this.element.input.parent().width() - s);
            }
	        this.element.button = $('<a/>')
	            .insertAfter(this.element.input)
	            .addClass('jNiceSelectOpen')
	            .click(function(){
	                self.onButtonClick();       
	            });
	        this.element.wrapper = select.parent();
	        var w = this.element.wrapper.width();
	        if ($.browser.msie && $.browser.version == '7.0') {
	        	w -= 6;
	        }
	        this.element.wrapper.width(w); // round element width
	        this.element.options = [];
	        select.children('option').each(function(index) {
	        	self.element.options.push({value: this.value, text: this.innerHTML});
	        });
	        select.remove();
	        this.element.each(function(){
	            this.combobox = self;
	            this.clear = function() {
	            	self.select(0);
	            }
	            this.clearOptions = function() {
	            	self.element.options = [];
	            }
	            this.addOption = function(value, text) {
	            	self.element.options.push({value: value, text: text});
	            }
	            this.updateOptions = function(options) {
	            	self.element.options = options;
	                self.select(0);
	            }
	            this.expand = function() {
	            	self.expand();
	            }
            });
            this._initKeyboardEvents();
        },
        
        onButtonClick: function() {
            this.focus();
            if (!this.options.justBlured) {
            	this.options.justBlured = false; 
            	this.expand();
            }
        },
        
        _initKeyboardEvents: function() {
            var input = this.element.input,
                self = this;
            input.keydown(function(event){
            	var keyCode = $.ui.keyCode;
                switch( event.keyCode ) {
                    case keyCode.UP:
                        if (!self.isExpanded()){
                            self.onButtonClick();
                        } else {
                            self.options.inKeyMode = true;
                            self.highlightPrev();
                        }
                        event.preventDefault();
                        break;
                    case keyCode.DOWN:
                        if (!self.isExpanded()){
                            self.onButtonClick();
                        } else {
                            self.options.inKeyMode = true;
                            self.highlightNext();
                        }
                        event.preventDefault();
                        break;
                    case keyCode.PAGE_DOWN:
                        if (self.isExpanded()){
                            self.options.inKeyMode = true;
                            self.highlightOnNextPage();
                            event.preventDefault();
                        }
                        break;
                    case keyCode.PAGE_UP:
                        if (self.isExpanded()){
                            self.options.inKeyMode = true;
                            self.highlightOnPrevPage();
                            event.preventDefault();
                        }
                        break;
                    case keyCode.ENTER:
                        if (self.isExpanded()) {
                            self.selectHighlighted();
                            event.preventDefault();
                        }
                        break;
                    case keyCode.ESCAPE:
                    	self.element.input.blur();
                    	self.focus();
                        event.preventDefault();
                        break;
                    case keyCode.TAB:
                        if (self.isExpanded() && self.selectHighlighted(true)) {
                            
                        } else {
                        	self.element.input.blur();
                        }
                        break;
                    default:
                        self.options.inKeyMode = false;
                }
            });
            input.keyup(function(event){
            	if (self.options.inKeyMode) {
                    event.preventDefault();
                    return;
                }
            	if (event.keyCode >= 48 || 
            		event.keyCode == jQuery.ui.keyCode.BACKSPACE || 
            		event.keyCode == jQuery.ui.keyCode.SPACE ) {
	                if (self.isExpanded()) {
	                    self.collapse();
	                }
	                var val = self.element.input.val();
	                self.expand(val);
	                a = self._getHighlightedItem();
            	}
/*                if (a.length) {
                    var t = a.text();
                    var p = val.length;
                    val = val + t.substring(p);
                    self.element.input.val(val);
                    self.selectText(p);
                }*/
            });
        },
        
        selectText : function(start, end){
            var input = this.element.input;
            var v = input.val();
            var doFocus = false;
            if(v.length > 0){
                start = start === undefined ? 0 : start;
                end = end === undefined ? v.length : end;
                var d = input.get(0);
                if(d.setSelectionRange){
                    d.setSelectionRange(start, end);
                }else if(d.createTextRange){
                    var range = d.createTextRange();
                    range.moveStart('character', start);
                    range.moveEnd('character', end-v.length);
                    range.select();
                }
                doFocus = $.browser.mozilla || $.browser.webkit;
            }else{
                doFocus = true;
            }
            if(doFocus){
                input.focus();
            }
        },
        
        _onItemClick: function(event) {
            event.data.combo.select($(this).attr('index'));
        },
        
        _onItemMouseMove: function(event) {
        	event.data.combo.options.inKeyMode = false;
        },
        
        _onItemMouseOver: function(event) {
        	if (event.data.combo.options.inKeyMode) {
                return;
            }
        	event.data.combo.highlight(this);
        },
        
        _initList: function(q) {
        	var start = new Date();
            var self = this,
                listWrapper,
                html = '<div class="jNiceSelectList"><ul>',
                opt;
            var ucText,
                value = this.element.val(),
                selected = false,
                alreadySelected = false;
            if (q) {
            	q = q.toUpperCase();
            }
            for (var i = 0; i < this.element.options.length; i++) {
            	opt = this.element.options[i];
            	ucText = opt.text.toUpperCase();
            	selected = false;
            	selected = (q && !alreadySelected) || (!q && opt.value == value);
            	if (!q || ucText.substring(0, q.length) == q) {
            		if (selected) {
                		alreadySelected = true;
                	}
                	html += '<li><a value="' + opt.value + '" index="' + i + '"' + (selected ? ' class="selected"' : '') + '>' + opt.text + '</a></li>';
            	}
            }
            html += '</ul><i class="sll"></i><i class="srr"></i></div>';
            listWrapper = $(html).appendTo(document.body);
            this.element.list = listWrapper.children('ul:first');
            this.element.list.wrapper = listWrapper;
            this.element.list.find('li > a')
            	.bind('click', {combo: this}, this._onItemClick)
            	.bind('mousemove', {combo: this}, this._onItemMouseMove)
            	.bind('mouseover', {combo: this}, this._onItemMouseOver);
            if ($.browser.msie && $.browser.version == '6.0') {
            	if (this.element.list.height() >= 233) {
            		this.element.list.height(233);
            		this.element.list.width('auto');
            	}
            }
	        
            $(document).bind('mousewheel', function(e){
                if (self.isExpanded()) {
                    self.collapseIf(e, true);    
                }
            });
            $(document).bind('mousedown', function(e){
            	if (self.element.list && (e.target == self.element.list.get(0) || jQuery.contains(self.element.list.get(0), e.target))) {
            		self.options.noBlur = true;
            	} else if (self.isExpanded()) {
                    self.collapseIf(e, true);    
                }
            });
            return this.element.list;
        },
        
        select: function(index, doNotFocus) {
            this.options.inSelect = true;
            this.element.val(this.element.options[index].value);
            this.element.input.val(this.element.options[index].text);
            this.options.selectedText = this.element.options[index].text;
            if (this.isExpanded()) {
            	this.collapse();
        	};	
            if (!doNotFocus) {
            	this.focus();
            }
            var self = this; 
            setTimeout(function(){
                self.element.trigger('change');
            }, 200);
            this.options.inSelect = false;
        },
        
        selectHighlighted: function(doNotFocus) {
            var index = this.element.list.find('li > a.selected').attr('index');
            if (index !== undefined) {
                this.select(index, doNotFocus);
                return true;
            } else {
                return false;
            }
        },
        
        _getHighlightedItem: function() {
            var a = this.element.list.find('li > a.selected');
            return a;
        },
        
        highlight: function(a) {
        	if (this.isExpanded()) {
                this.element.list.find('li > a').removeClass('selected');
                var ja = $(a); 
                ja.addClass('selected');
                if (this.hasScroll()) {
                    var offset = ja.parent().offset().top - this.element.list.offset().top,
                        scroll = this.element.list.attr("scrollTop"),
                        elementHeight = this.element.list.height();
                    if (offset < 0) {
                        this.element.list.attr("scrollTop", scroll + offset);
                    } else if (offset > elementHeight - ja.parent().height()) {
                        this.element.list.attr("scrollTop", scroll + offset - elementHeight + ja.parent().height());
                    }
                }                 
            }
        },
        
        hasScroll: function() {
            return this.element.list.height() < this.element.list.attr("scrollHeight");
        },
        
        expand: function(q) {
        	var start = new Date();
            var list = this._initList(q);
            list.wrapper.position({
                my: "left top",
                at: "left bottom",
                of: this.element.wrapper,
                offset: "0 -4",
                collision: "none"
            }).width("");
            var w = Math.max(this.element.wrapper.width(), list.width());
            list.wrapper.width(w > 400 && w > this.element.wrapper.width() ? 400 : w);
            if (list.wrapper.width() > this.element.wrapper.width()) {
                this.element.extender = $('<div/>')
                    .appendTo(document.body)
                    .addClass('jNiceSelectWrapper')
                    .append('<div class="jNiceSelectCurrent" style="background-position: -5px 0"/>')
                    .position({
                        my: "left top",
                        at: "right top",
                        of: this.element.wrapper.find('.jNiceSelectCurrent'),
                        offset: "0 0",
                        collision: "none" 
                    });
                w = list.wrapper.width() - this.element.extender.offset().left + list.wrapper.offset().left - 19;
                if ($.browser.msie && $.browser.version == '6.0') {
                	w += 19;
                }
                this.element.extender.width(w);
            }
            if (this.hasScroll()) {
            	list.scrollTop(this.element.list.find('li:has(a.selected)').position().top);
            }
        },
        
        collapse: function() {
            this.element.list.wrapper.remove();
            if (this.element.extender) {
                this.element.extender.remove();      
            }
        },
        
        collapseIf: function(e, blur) {
            var top, left, bottom, right, p,
                lw = this.element.list.wrapper;
            p = lw.offset();
            top = p.top;
            left = p.left;
            bottom = top + lw.height();
            right = left + lw.width();
            if (e.pageX < left || e.pageX > right || e.pageY < this.element.input.offset().top || e.pageY > bottom) {
                this.element.input.blur();
            }
        },
        
        isExpanded: function() {
            return this.element.list && this.element.list.wrapper && this.element.list.wrapper.is(':visible');  
        },
        
        focus: function() {
            this.element.input.focus();
        },
        
        highlightNext: function() {
            var ct = this.element.list.find('li > a').length;
            if (ct > 0){
                var a = this._getHighlightedItem();
                if (!a.length){
                    this.highlight(this.element.list.find('li > a:first').get(0));
                } else {
                	a = a.parent().next(':visible');
                	if (a.length) {
                        this.highlight(a.get(0).firstChild);
                	}
                }
            } 
        }, 
        
        highlightPrev: function() {
            var ct = this.element.list.find('li > a').length;
            if (ct > 0) {
                var a = this._getHighlightedItem();
                if (!a.length){
                    this.highlight(this.element.list.find('li > a:first').get(0));
                } else if (a.parent().get(0).previousSibling) {
                	a = a.parent().prev(':visible');
                	if (a.length) {
                        this.highlight(a.get(0).firstChild);
                	}
                }
            }
        },
        
        highlightOnNextPage: function() {
            if (this.hasScroll()) {
                var base = this._getHighlightedItem().offset().top,
                    height = this.element.list.height(),
                    result = this.element.list.children("li").filter(function() {
                        var close = $(this).offset().top - base - height + $(this).height();
                        return $(this).is(':visible') && close < 15 && close > -15;
                    });

                if (!result.length) {
                    result = this.element.list.children(":visible:last");
                }
                this.highlight(result.get(0).firstChild);
            } else {
                this.highlight(this.element.list.children(":visible:last").get(0).firstChild);
            }        	
        },
        
        highlightOnPrevPage: function(event) {
            if (this.hasScroll()) {
                var base = this._getHighlightedItem().offset().top,
                    height = this.element.list.height(),
                    result = this.element.list.children("li").filter(function() {
                        var close = $(this).offset().top - base + height - $(this).height();
                        return $(this).is(':visible') && close < 15 && close > -15;
                    });

                if (!result.length) {
                    result = this.element.list.children(":visible:first");
                }
                this.highlight(result.get(0).firstChild);
            } else {
            	this.highlight(this.element.list.children(":visible:first").get(0).firstChild);
            }
        }
    });
})(jQuery);