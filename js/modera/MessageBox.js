
Ext.namespace('Modera');

Modera.MessageBox = function() {
    var f = function() {};
    f.prototype = Ext.MessageBox;
    var o = function() {};
    Ext.extend(o,f, function() {
        var progress2El, pp2;
        return {
            getDialog : function() {
                var d = o.superclass.getDialog.apply(this, arguments);
                if (!progress2El) {
                    progress2El = Ext.fly(d.body.dom.firstChild).createChild({
                        tag:"div",
                        cls:"ext-mb-progress-wrap",
                        html:'<div class="ext-mb-progress"><div class="ext-mb-progress-bar">&#160;</div></div>'
                    });

                    progress2El.enableDisplayMode();
                    var pf2 = progress2El.dom.firstChild;
                    pp2 = Ext.get(pf2.firstChild);
                    pp2.setHeight(pf2.offsetHeight);
                }

                return d;
           },

           updateProgress2 : function(value) {
               pp2.setWidth(Math.floor(value * progress2El.dom.firstChild.offsetWidth));
               return this;
           },

           show : function(options) {
               this.getDialog();
               if (options.progress2) {
                   options.progress = true;
               }
               progress2El.setDisplayed(options.progress2 === true);
               this.updateProgress2(0);
               return o.superclass.show.call(this, options);
           }
         };
    }());
    return new o();
}();
