Ext.EditorFieldAjax = function(field, dataStore, config){
    Ext.EditorFieldAjax.superclass.constructor.call(this, field, config);

    if (!dataStore) {
        this.getStore();
    } else {
        this.dataStore = dataStore;
    }

    this.dataStore.on('loadexception', this.onStoreLoadExeption, this);
    this.dataStore.on('load', this.onStoreLoad, this);
    this.dataStore.editor = this;
    this.addEvents({
        "beforeedit" : true,
        "beforecomplete" : true,
        "complete" : true,
        "beforrequest": true
    });
    this.addListener('beforeedit', this.checkValue, this);

};

Ext.extend(Ext.EditorFieldAjax, Ext.Editor, {
    alignment: "tl-tl",
    autoSize: "width",
    hideEl : true,
    cls: "x-small-editor",
    shadow:"frame",
    trackMouseOver: false, // causes very odd FF errors
    allowBlur: true,
    revertInvalid: false,
    ignoreNoChange: true,
    updateEl: true,
    dirtyShow: true,
    emptyText:'',
    params:{},
    baseUrl:null,
    emptyClass:'untr',
    editedValue: null,
    buttons: {
        accept: 'Update',
        cancel: 'Cancel'
    },

    setUrl: function(url) {
        this.baseUrl = url;
    },


    getStore: function(){
        if (!this.dataStore) {
             this.dataStore = new Ext.data.Store({
                proxy: new Ext.data.HttpProxy(),
                reader: new Ext.data.JsonReader({root: "result"},
                    Ext.data.Record.create([{name: 'edited', type: 'bool'},{name: 'value'}])
                )
             });
        }
        return this.dataStore;
    },


    // In this place we add buttons to editor
    onRender : function(ct){
        Ext.EditorFieldAjax.superclass.onRender.call(this, ct);
        var div = Ext.DomHelper.insertAfter(this.field.el, '<div class="x-dlg-ft"><div class="x-dlg-btns x-dlg-btns-right"><table cellspacing="0"><tbody><tr></tr></tbody></table><div class="x-clear"></div></div></div>',true);
        //div.setWidth(this.field.el.getWidth() - 25);
        var btnContainer = div.dom.firstChild.firstChild.firstChild.firstChild;
        var btn = new Ext.Button(
            btnContainer.appendChild(document.createElement("td")), {
                icon: 'pic/button_accept.gif',
                cls: 'x-btn-text-icon',
                minWidth: 50,
                text: this.buttons.accept
            }
        );
        btn.on('click', this.completeEdit, this);
        btn.on('mouseover', function(){this.preventBlur = true;}, this);
        btn.on('mouseout', function(){this.preventBlur = false;}, this);
        var btn2 = new Ext.Button(
            btnContainer.appendChild(document.createElement("td")),{
                icon: 'pic/button_decline.gif',
                cls: 'x-btn-text-icon',
                minWidth: 50,
                text: this.buttons.cancel
            }
        );
        btn2.on('click', this.cancelEdit, this);
        btn2.on('mouseover', function(){this.preventBlur = true;}, this);
        btn2.on('mouseout', function(){this.preventBlur = false;}, this);
    },

    // private
    onBlur : function(){
        if(this.allowBlur !== true && this.editing && !this.preventBlur){
            this.cancelEdit();
        }
    },

    getUrl: function() {
        return this.baseUrl;
    },

    startEditing : function(bound, val, record){
        //this.loading.hide();
        if (this.editing) {
            return;
        }
        this.editing = false;
        this.record = record;
        this.on("specialkey", this.onEditorKey, this);
        var e = {
            editor: this,
            field: this.field,
            originalValue: val,
            cancel:false
        };
        if(this.fireEvent("beforeedit", e) !== false && !this.cancel){
            (function(){
                this.startEdit(bound, e.originalValue);
                this.el.enableShadow(true);
            }).defer(50, this);
        }

    },

    onEditorKey: function(el, e){
        k = e.getKey();
        switch(k){
            case e.ENTER:
                if (e.ctrlKey) {
                    if(this.editing){
                        var v = this.getValue();
                        if (String(v) == String(this.startValue)) {
                            this.completeEdit();
                        }else {
                            this.fireEvent("beforrequest", this, v, this.startValue);
                            this.SendRequest(v);
                        }
                    }
                }
                break;
            case e.ESC:
                this.cancelEdit();
                break;
        }
    },

    completeEdit : function(){
        if(!this.editing){
            return;
        }
        var v = this.getValue();
        if(this.revertInvalid !== false && !this.field.isValid()){
            v = this.startValue;
            this.cancelEdit(true);
        }
        if(String(v) == String(this.startValue) && this.ignoreNoChange){
            this.editing = false;
            this.hide();
            return;
        }
        if(this.fireEvent("beforecomplete", this, v, this.startValue) !== false){
            this.editing = false;
            if(this.updateEl && this.boundEl){
                if (this.dirtyShow) {
                    //this.boundEl.addClass('x-grid-dirty-cell');
                }
                this.fireEvent("beforrequest", this, v, this.startValue);
                this.SendRequest(v);
            }
        }
    },

    checkValue: function(editor){
        if (editor.originalValue == this.emptyText) {
            editor.originalValue = '';
        }
    },

    onStoreLoad: function(store, records, options) {
        if (records[0].data.edited) {
            var value;
            if (records[0].data.value) {
                value = records[0].data.value;
            } else {
                value = options.params.value;
            }
            if (false !== this.fireEvent("complete", this, value, this.startValue)) {
                if (value=='') {
                    this.boundEl.update(this.emptyText);
                    if (this.emptyClass) {
                        Ext.fly(this.boundEl.dom.parentNode).addClass(this.emptyClass);
                    }
                } else {
                    this.boundEl.update(value.replace(/&/g, '&amp;').replace(/</g, '&lt;'));
                    if (this.emptyClass) {
                        Ext.fly(this.boundEl.dom.parentNode).removeClass(this.emptyClass);
                    }
                }
            }
        }
        this.editing = false;
    },


    SendRequest : function(v){
        this.params.value = v;
        this.hide();
        this.editing = true;
        this.boundEl.update('<img src="../js/ext/resources/images/default/grid/loading.gif" style="width:16px;height:16px;"/>');
        this.dataStore.proxy.getConnection().url = this.baseUrl;
        this.dataStore.load({params:this.params});
    }

});