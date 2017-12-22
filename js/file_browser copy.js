Ext.BLANK_IMAGE_URL = '../js/ext/resources/images/default/s.gif';
FileBrowser = function(view, config){
    FileBrowser.superclass.constructor.call(this, view, config);
    this.init();
};
var currentNode;

Ext.extend(FileBrowser, Ext.ContentPanel, {

    perpage: 100,

    init: function() {
        if (!this.backendUrl) {
            alert(this.lang.msg_set_backend_url);
            return false;
        }
        if (this.backendUrl.indexOf('?') == -1) {
            this.backendUrl += '?';
        } else {
            this.backendUrl += '&';
        }
        // init data store
        this.ds = new Ext.data.Store({
            proxy: new Ext.data.HttpProxy({url: this.backendUrl + 'do=get_files'}),
            reader: new Ext.data.JsonReader(
                {
                    root: "rows",
                    totalProperty: 'total'
                },
                Ext.data.Record.create([
                    {name: 'content'},
                    {name: 'removed'},
                    {name: 'disabled'},
                    {name: 'icon'},
                    {name: 'owner'},
                    {name: 'icon_big'},
                    {name: 'view_url'},
                    {name: 'thumb_url'},
                    {name: 'filename'},
                    {name: 'size', type: 'int'},
                    {name: 'last_modified', type: 'date', dateFormat: 'd.m.y h:i'},
                    {name: 'description', type:'string'},
                    {name: 'id'},
                    {name: 'obj'},
                    {name: 'type'},
                    {name: 'folder'},
                    {name: 'url_delete'},
                    {name: 'masked', type: 'int'},

                ])
            )
        });


        if (this.preselectedFiles) {
            this.ds.on('load',function(ds, r, o){
                var s = this.filesGrid.getSelectionModel(), pf = this.preselectedFiles, len, ulen;
                s.selections.clear();
                for(var i = 0, len = ds.getCount(); i < len; i++) {
                    for(var u = 0, ulen = pf.length; u < ulen; u++) {
                        if (r[i].data.filename == pf[u]) {
                            s.selectRow(i, true);
                            break;
                        }
                    }
                }
            }, this, {single:true});
        }


        this.layout = new Ext.BorderLayout(this.getEl(), {
            west: {
                initialSize:220,
                minSize:220,
                maxSize:220,
                split:true,
                titlebar:false,
                hidetabs:true,
                autoScroll:true
            },
            center: {
                split:true,
                autoScroll:false,
                collapsible:false,
                titlebar: false
            }
        });
        var nestedWLayout = document.createElement('DIV');
        this.getEl().appendChild(nestedWLayout);
        this.nestedWestLayout = new Ext.BorderLayout(nestedWLayout,{
            center: {
                initialSize:200,
                minSize:200,
                split:true,
                titlebar:false,
                hidetabs:true,
                autoScroll:true
            },
            south: {
                initialSize:250,
                minSize:200,
                split:true,
                titlebar:false,
                hidetabs:true,
                hideWhenEmpty:true,
                autoScroll:true
            }
        });

        var nestedCLayout = document.createElement('DIV');
        this.getEl().appendChild(nestedCLayout);
        this.nestedCenterLayout = new Ext.BorderLayout(nestedCLayout,{
            center: {
                initialSize:300,
                minSize:300,
                maxSize:300,
                alwaysShowTabs:true,
                tabPosition:'top',
                titlebar:false,
                hidetabs:false,
                autoScroll:true
            },
            south: {
                split:true,
                initialSize: 28,
                minSize:28,
                maxSize:28,
                autoScroll:true,
                collapsible:false,
                titlebar: false
            }
        });

        var nestedCLayout1 = document.createElement('DIV');
        this.getEl().appendChild(nestedCLayout1);

        this.nestedCenterLayout1 = new Ext.BorderLayout(nestedCLayout1,{
            north: {
                initialSize:28,
                minSize:28,
                maxSize:28,
                split:true,
                titlebar:false,
                hidetabs:true,
                alwaysShowTabs:false
            },
            center: {
                //initialSize:20,
                minSize:300,
                split:true,
                titlebar:false,
                hidetabs:false,
                autoScroll:true
            }
        });

        var nestedCLayout2 = document.createElement('DIV');
        this.getEl().appendChild(nestedCLayout2);
        this.nestedCenterLayout2 = new Ext.BorderLayout(nestedCLayout2,{
            north: {
                initialSize:28,
                minSize:28,
                maxSize:28,
                split:true,
                titlebar:false,
                hidetabs:true,
                alwaysShowTabs:false
            },
            center: {
                //initialSize:20,
                minSize:300,
                split:true,
                titlebar:false,
                hidetabs:false,
                autoScroll:true
            }
        });

        this.layout.beginUpdate();
        this.layout.add('west', new Ext.NestedLayoutPanel(this.nestedWestLayout));
        this.nestedCenterLayout.add('center', new Ext.NestedLayoutPanel(this.nestedCenterLayout2,{title:this.lang.detailView}));
        this.nestedCenterLayout.add('center', new Ext.NestedLayoutPanel(this.nestedCenterLayout1,{title:this.lang.pictureView}));
        this.layout.add('center', new Ext.NestedLayoutPanel(this.nestedCenterLayout));
        this.layout.endUpdate();
        this.nestedCenterLayout.getRegion("center").getPanel(0).on('activate', this.savePanelGridState, this);
        this.nestedCenterLayout.getRegion("center").getPanel(1).on('activate', this.savePanelViewState, this);
        this.nestedCenterLayout.getRegion("center").showPanel(0);
        if (this.viewstate != 0) {
            this.nestedCenterLayout.getRegion("center").showPanel(this.viewstate);
        }

        var paging = Ext.DomHelper.insertFirst(document.body, {tag: "div"}, true);
        this.nestedCenterLayout.add('south', new Ext.ContentPanel(paging.id));
        this.pagingToolbar = new Ext.PagingToolbar(paging, this.ds, {
            pageSize: this.perpage,
            displayInfo: true
        });

        this.nestedCenterLayout.getRegion("south").hide();
        this.ds.on('load', function(s){
            if (s.getTotalCount() > this.perpage) {
                this.nestedCenterLayout.getRegion("south").show();
            } else {
                this.nestedCenterLayout.getRegion("south").hide();
            }
        }, this);
        
        this.foldersPanel = this.nestedWestLayout.add('center', new Ext.ContentPanel('foldersPanel', {
            title: this.lang.foldersTitle,
            fitToFrame: true,
            autoScroll: true,
            autoCreate: true
        }));

        var fW = document.createElement('DIV');
        this.nestedWestLayout.getEl().appendChild(fW);
        this.fileView = this.nestedWestLayout.add('south', new Ext.ContentPanel(fW, {
            title: this.lang.foldersTitle,
            fitToFrame: true,
            autoScroll: true,
            autoCreate: true
         }));
       var detailFile = document.createElement('DIV');
       this.fileView.getEl().appendChild(detailFile);
       this.fileDetail = Ext.get(detailFile);
       this.ds.on('beforeload', function(s){
            this.fileDetail.update('');
        }, this);
       // make folders tree
       this.foldersTree = new Ext.tree.TreePanel(this.foldersPanel.getEl(), {
            animate:true,
            enableDD:true,
            loader: new Ext.tree.TreeLoader({dataUrl:this.backendUrl + 'do=get_nodes'}),
            containerScroll: true,
            ddGroup: 'filesDD',
            rootVisible: false,
            enableDrop: true,
            ddAppendOnly: true,
            fileBrowser: this
        });

        //this.foldersTree.getEl().dom.onkeydown = this.onNodeKeyPress;
        this.foldersTree.el.on(Ext.isIE? "keydown" : "keypress", this.onNodeKeyPress, this, true);

        this.treeEditor = new Ext.tree.TreeEditor(this.foldersTree, {ignoreNoChange: false});
        this.treeEditor.editDelay = 1000000;
        this.treeEditor.on('beforestartedit', this.beforeNodeEditClickStartEdit, this);
        this.treeEditor.on('beforecomplete', this.beforeNodeEditClickComplete, this);
        this.treeEditor.on('complete', this.onNodeEditClick, this);
        this.rootFolder = new Ext.tree.AsyncTreeNode({
            text:'root',
            id:'root',
            allowDrag:false,
            allowDrop:false
        });
        this.foldersTree.setRootNode(this.rootFolder);
        this.foldersTree.on('click', this.onTreeClick, this);
        this.foldersTree.on('load', this.onTreeLoad, this);
        this.foldersTree.on('beforemove', this.onNodeMoove, this);
        this.foldersTree.on('beforenodedrop', this.onTreeBeforeNodeDrop, this);
        this.foldersTree.on('nodedragover', this.onDragOver, this);
        this.foldersTree.on('contextmenu', this.onTreeRightClick, this);
        this.foldersTree.on('append', function(tree, ln, n,i){
             n.loaded = (n.attributes.childs)?false:true;
        });

        this.ds.on('beforeload',function(obj, options){

             if (!options.params) {
                  options.params = {};
             }

             if (options.params.start == undefined) {
                  options.params.start = 0;
             }

             options.params.count = this.perpage;
             options.params.action = 'get_files';
             options.params.showdisabled = this.checkbox.getValue()?1:0;

             if (this.selectedNode) {
                 options.params.folder = this.selectedNode.id;
             }

             if (this.combo1.store.data.length) {
                options.params.filter = this.combo1.getValue();
             } else {
                 options.params.filter = this.combo1.getRawValue();
             }

        }, this);

        // make files detail view
         this.picturesPanel = this.nestedCenterLayout1.add('center', new Ext.ContentPanel('pictures', {
            title: this.lang.viewIconTitle,
            fitToFrame: true,
            autoScroll: true,
            autoCreate: true
        }));
        this.filesPanel = this.nestedCenterLayout2.add('center', new Ext.ContentPanel('files', {
            title: this.lang.viewDetailTitle,
            fitToFrame: true,
            autoScroll: true,
            autoCreate: true
        }));
        //Filter
        var extensions = new Ext.data.SimpleStore({
            fields: ['name', 'state'],
            data : [['', this.lang.all_files], ['images', this.lang.only_images], ['notimages', this.lang.not_images], ['.doc', this.lang.doc_files], ['.xls', this.lang.xsl_files], ['.zip',this.lang.zip_files]]
        });

        this.filterPanel1 = this.nestedCenterLayout1.add('north', new Ext.ContentPanel('filter1', {
            title: 'filter',
            fitToFrame: true,
            autoScroll: false,
            autoCreate: true
        }));

        var tbd1 = document.createElement('DIV');
        this.filterPanel1.getEl().appendChild(tbd1);
        this.tb1 = new Ext.Toolbar(tbd1);
        this.btAdd1 = this.tb1.addButton({text: this.lang.addNew, cls: 'x-btn-text-icon', icon: '../img/upload.gif'});
        this.btAdd1.on('click', this.onAddClick, this);
        this.tb1.addSeparator();
        this.combo1 = new Ext.form.ComboBox({
            store: extensions,
            displayField:'state',
            typeAhead: true,
            mode: 'local',
            valueField: 'name',
            editable:true,
            triggerAction: 'all',
            emptyText:this.lang.filter,
            selectOnFocus:true,
            width:250
        });

        this.combo1.on('select', this.onComboClick1, this);
        this.tb1.add(this.combo1);
        this.btFilter1 = this.tb1.addButton({text: this.lang.filter, cls: 'x-btn-text-icon', icon: '../img/filter.gif'});
        this.btFilter1.on('click', this.onComboClick1, this);

        this.tb1.addSeparator();

        this.checkbox = new Ext.form.Checkbox({
            boxLabel:this.lang.labelShowDeleted,
            width:'auto',
            checked: this.disabledstate?true:false
        });

        this.checkbox.on('check', function(el, checked){
            if(this.checkbox2 && this.checkbox2.getValue() != checked) {
                this.checkbox2.setValue(checked);
                this.ds.load();
            }
        }, this);

        this.tb1.add(this.checkbox);

        this.filterPanel2 = this.nestedCenterLayout2.add('north', new Ext.ContentPanel('filter2', {
            title: 'filter',
            fitToFrame: true,
            autoScroll: false,
            autoCreate: true
        }));
        var tbd2 = document.createElement('DIV');
        this.filterPanel2.getEl().appendChild(tbd2);
        this.tb2 = new Ext.Toolbar(tbd2);
        this.btAdd2 = this.tb2.addButton({text: this.lang.addNew, cls: 'x-btn-text-icon', icon: '../img/upload.gif'});
        this.btAdd2.on('click', this.onAddClick, this);
        this.tb2.addSeparator();
        this.combo2 = new Ext.form.ComboBox({
            store: extensions,
            displayField:'state',
            typeAhead: true,
            mode: 'local',
            valueField: 'name',
            editable:true,
            triggerAction: 'all',
            emptyText:this.lang.filter,
            selectOnFocus:true,
            width:250
        });
        this.combo2.on('select', this.onComboClick2, this);
        this.tb2.add(this.combo2);
        this.btFilter2 = this.tb2.addButton({text: this.lang.filter, cls: 'x-btn-text-icon', icon: '../img/filter.gif'});
        this.btFilter2.on('click', this.onComboClick2, this);


        this.tb2.addSeparator();
        this.checkbox2 = new Ext.form.Checkbox({
            boxLabel:this.lang.labelShowDeleted,
            width:'auto',
            checked: this.disabledstate?true:false
        });
        this.checkbox2.on('check', function(el, checked){
            if(this.checkbox && this.checkbox.getValue() !== checked){
                this.checkbox.setValue(checked);
                this.ds.load();
            }
        }, this);
        this.tb2.add(this.checkbox2);
        delete this.disabledstate;

        //end filter
        this.gridColModel = new Ext.grid.ColumnModel([
            {header: this.lang.gridFileName, renderer: this.gridRenderFilename, sortable: true, dataIndex: 'obj',
                 editor: new Ext.grid.GridEditor(new Ext.form.TextField({allowBlank:false}))
            },
            {header: this.lang.gridFileSize, renderer: this.gridRenderFileSize, sortable: true, dataIndex: 'size'},
            {header: this.lang.gridFileDate, renderer: this.gridRenderFileDate, sortable: true, dataIndex: 'last_modified'},
            {header: this.lang.gridFileDescription, renderer: this.gridRenderFileDesc, sortable: true, dataIndex: 'description'}
        ]);


        this.editor = new Ext.EditorFieldAjax(
             new Ext.form.TextArea({allowBlank: true, alignment: 'l', grow: true, growMax:250}),
             false, {autosize:true,allowBlur:false});

        this.editor.setUrl(this.backendUrl + 'do=change_description')

        this.editor.addListener('complete', function(editor, value, startValue){
            if(value==false || value=='false')value='';
            editor.record.data.description = value;
            this.filesGrid.getView().refresh();
            return false;
        }, this);


        // initialize the files grid
        this.filesGrid = new GridInlineEditor(this.filesPanel.getEl(), {
            autoSizeColumns: true,
            ds: this.ds,
            cm: this.gridColModel,
            selModel: new Ext.grid.RowSelectionModel(),
            loadMask: true,
            enableDragDrop: true,
            ddGroup: 'filesDD'
        });


        this.filesGrid.on('rowdblclick', this.onGridRowDblClick, this);
        this.filesGrid.on('rowcontextmenu', this.onGridRowRightClick, this);
        this.filesGrid.on('afteredit', this.afterEditGridCell, this);
        this.filesGrid.on('cellclick', function(g, r, c, e){
            if (c == 3) {
                var el = Ext.get(e.target), rec = this.ds.getAt(r);
                this.editor.params.dest = (rec.data.folder + rec.data.filename);
                this.editor.startEditing(el, rec.data.description, rec);
            }
        }, this);
        this.filesGrid.getSelectionModel().on('rowselect', this.onRowSelect, this);
        this.filesGrid.getSelectionModel().on('selectionchange', this.onGridSelectionChange, this);
        this.filesGrid.getSelectionModel().on('startdrag', this.onGridStartDrag, this);
        Ext.KeyNav.prototype.f2 = false;
        Ext.KeyNav.prototype.keyToHandler[113] = 'f2';
        this.filesKeyNav = new Ext.KeyNav(this.filesGrid.getGridEl(), {
            "del" : function(e){
                this.deleteFiles(this.filesGrid.getSelectionModel().getSelections());
            },
            "f2": function(e){
                this.showFileEditor();
            },
            scope : this
        });

        // make files pictures view

        //this.picturesBody = this.picturesPanel.getEl();

        var tpl = new Ext.Template(
            '<div class="item" id="{filename}" style="overflow:hidden;"><div style="display:block; height:100%;  BACKGROUND: url(/img/thumb_disabled_{masked}.gif)"><div style="display:block; height:100;  BACKGROUND: url({icon_big}) no-repeat top left;"><div style="display:block; height:100%;  BACKGROUND: url(/img/thumb_disabled_{masked}.gif)"></div></div><p>{filename}</p></div></div>'
        );
        tpl.compile();

        //this.picturesView = new Ext.View(this.picturesBody, tpl, {
        this.picturesView = new Ext.View(this.picturesPanel.getEl(), tpl, {
             multiSelect: true,
             singleSelect: true,
             selectedClass: 'item-selected',
             store: this.ds
        });

        this.picturesView.getEl().addClass('thumblist');
        this.picturesView.on('click', this.onViewItemClick, this);
        this.picturesView.on('dblclick', this.onViewItemDblClick, this);
        this.ViewRowNav = new Ext.KeyNav(this.picturesView.getEl(), {
            "up" : function(e){
                if(!e.shiftKey){
                    this.selectPrevious(e.shiftKey);
                }else if(this.last !== false && this.lastActive !== false){
                    var last = this.last;
                    this.selectRange(this.last,  this.lastActive-1);
                    this.grid.getView().focusRow(this.lastActive);
                    if(last !== false){
                        this.last = last;
                    }
                }else{
                    this.selectFirstRow();
                }
            },
            "down" : function(e){
                if(!e.shiftKey){
                    this.selectNext(e.shiftKey);
                }else if(this.last !== false && this.lastActive !== false){
                    var last = this.last;
                    this.selectRange(this.last,  this.lastActive+1);
                    this.grid.getView().focusRow(this.lastActive);
                    if(last !== false){
                        this.last = last;
                    }
                }else{
                    this.selectFirstRow();
                }
            },
            "del" : function(e){
                this.deleteFiles(this.filesGrid.getSelectionModel().getSelections());
            },
            scope: this.filesGrid.getSelectionModel()
        });

        var picViewDrag = new ImageDragZone(this.picturesView, {
            containerScroll: true,
            ddGroup: 'filesDD',
            selDisplayMode: 'grid'
        }, this.filesGrid.dd);

        // create context menus
        this.fileMenu = new Ext.menu.Menu({
            id: 'fileMenu',
            items: [
                new Ext.menu.Item({
                    text: this.lang.labelView,
                    handler: this.onFileMenuViewClick,
                    scope: this
                }),
                new Ext.menu.Item({
                    text: this.lang.labelModify,
                    handler: this.onFileMenuModifyClick,
                    scope: this
                }),
                new Ext.menu.Item({
                    text: this.lang.labelDelete,
                    handler: this.onFileMenuDeleteClick,
                    scope: this
                })
            ]
        });

        if (this.permaccess) {

            this.privsdialog = new FileBrowserPrivsEditor(this.backendUrl, this.lang);
            this.privsdialog.init();

            this.fileMenu.add(
                new Ext.menu.Item({
                    text: this.lang.labelRefresh,
                    handler: this.refreshFolder,
                    scope: this
                })
            );
            this.fileMenu.add(
                new Ext.menu.Item({
                    text: this.lang.labelPrivs,
                    handler: this.showPrivsDialog,
                    scope: this
                })
            );
        }

        this.fileMenu.add(new Ext.menu.Item({
                    text: this.lang.labelRename,
                    handler: this.showFileEditor,
                    scope: this
        }));

         this.fileRecycleMenu = new Ext.menu.Menu({
            id: 'fileMenu',
            items: [
                new Ext.menu.Item({
                    text: this.lang.labelRestore,
                    handler: this.onFileMenuRestoreClick,
                    scope: this
                }),
                new Ext.menu.Item({
                    text: 'Properties',
                    handler: this.onFileRestorePropClick,
                    scope: this
                })
            ]
        });

        this.copyMoveMenu = new Ext.menu.Menu({
            id: 'copyMoveMenu',
            items: [
                new Ext.menu.Item({
                    text: this.lang.labelCopy,
                    handler: this.onCopyMoveMenuCopyClick,
                    scope: this
                }),
                new Ext.menu.Item({
                    text: this.lang.labelMove,
                    handler: this.onCopyMoveMenuMoveClick,
                    scope: this
                }),
                new Ext.menu.Item({
                    text: this.lang.labelCancel,
                    handler: this.onCancelFolderClick,
                    scope: this
                })
            ]
        });


        this.foldersMenu = new Ext.menu.Menu({
            id: 'foldersMenu',
            items: [
                new Ext.menu.Item({
                    id:'fn_crfolder',
                    text: this.lang.labelCreate,
                    handler: this.onFoldersMenuCreateClick,
                    scope: this
                }),
                new Ext.menu.Item({
                    text: this.lang.labelEmpty,
                    handler: this.onFoldersMenuEmptyClick,
                    scope: this
                }),
                new Ext.menu.Item({
                    text: this.lang.labelDelete,
                    handler: this.onFoldersMenuDeleteClick,
                    scope: this
                }),
                new Ext.menu.Item({
                    text: this.lang.labelCreateThumbnails,
                    handler: this.onFoldersMenuCreateThumbnailsClick,
                    scope: this
                })
            ]
        });

        if (this.permaccess) {
            this.foldersMenu.add(
                new Ext.menu.Item({
                    text: this.lang.labelRefresh,
                    handler: this.refreshFolder,
                    scope: this
                })
            );
            this.foldersMenu.add(
                new Ext.menu.Item({
                    text: this.lang.labelPrivs,
                    handler: this.showFolderPrivsDialog,
                    scope: this
                })
            );
        }

        this.foldersMenu.add(new Ext.menu.Item({
                    text: this.lang.labelRename,
                    handler: this.showFolderEditor,
                    scope: this
                })
        );

        this.foldersRecycleMenu = new Ext.menu.Menu({
            id: 'foldersMenu',
            items: [
                new Ext.menu.Item({
                    text: this.lang.labelRestore,
                    handler: this.onFoldersMenuRestoreClick,
                    scope: this
                }),
                new Ext.menu.Item({
                    text: 'Properties',
                    handler: this.onFoldersRestorePropClick,
                    scope: this
                })
            ]
        });

        this.RecycleMenu = new Ext.menu.Menu({
            id: 'foldersMenu',
            items: [
                new Ext.menu.Item({
                    text: this.lang.labelEmpty,
                    handler: this.emptyRecycle,
                    scope: this
                })
            ]
        });
    },


    afterEditGridCell: function (e) {
        this.filesGrid.stopEditing();
        if (e.value != e.originalValue) {
            var conn = new Ext.data.Connection();
            var params = {value: e.value, dest:(e.record.data.folder + e.record.data.filename)};
            this.showProgressDialog(this.lang.titlePleaseWait);

            conn.request({
                e: e,
                url: this.backendUrl + 'do=rename_file',
                params: params,
                action: 'rename_file',
                callback: function(options, success, response) {
                    this.dialog.hide();
                    response = Ext.util.JSON.decode(response.responseText);
                    if (response && response.error != '') {
                        Ext.MessageBox.alert(this.lang.lbl_alert, 'error: ' + response.error);
                        e.record.data.obj = e.originalValue;
                    } else {
                        if (response.obj) e.record.data.obj = response.obj;
                        if (response.thumb_url) e.record.data.thumb_url = response.thumb_url;
                        if (response.icon_big) e.record.data.icon_big = response.icon_big;
                        if (response.view_url) e.record.data.view_url = response.view_url;
                    }
                    e.record.data.filename = e.record.data.obj + '.' + e.record.data.type;
                    delete e.record.modified.obj;
                    this.filesGrid.getView().refresh();
                    this.picturesView.refresh();
                    this.viewLinks(this.ds.indexOf(e.record));
                },
                scope: this
            });
        }

    },

    showFolderEditor: function(){
        var node = this.selectedNode, ed = this.treeEditor;
        ed.completeEdit();
        ed.editNode = node;
        ed.startEdit(node.ui.textNode, node.text);
    },

    showFileEditor: function(){
        var g = this.filesGrid, rec = g.getSelectionModel().getSelected();
        if(rec.data.disabled != 1) {
            var i = this.ds.indexOf(rec);
            g.startEditing(i, 0);
            (function(){
                var ed = g.activeEditor;
                var sz = ed.boundEl.getSize();
                ed.setSize(sz.width - 23,  sz.height);
                ed.el.alignTo(ed.boundEl, 'tr-tr');
            }).defer(100);
        }
    },

    onFoldersRestorePropClick: function () {
        this.showRestoreProperties('/admin/pic/ico_folder-closed.gif', 'folder', this.selectedNode.text, this.selectedNode.id.substr(4, (this.selectedNode.id.lastIndexOf('/')-3)));
    },

    onFileRestorePropClick: function () {
        var sel = this.filesGrid.getSelectionModel().getSelected().data, ico;
        if (sel.content == 'image') {
            ico = '/admin/pic/ico_image.gif';
        } else {
            ico = '/admin/pic/ico_other.gif';
        }
        this.showRestoreProperties(ico, sel.content, sel.filename, '/upload' + sel.folder);
    },

    showRestoreProperties: function (icon, type, name, folder) {
        var msg = '<div style="font-size:11px; line-height:20px"><span style="display:block; height:34px; line-height:24px; font-size:11px; padding-top:2px; padding-left:40px; background:transparent url(' + icon + ') no-repeat scroll 0%;">' + name + '</span>'
        msg += '<hr/>' + this.lang.systemType + ': ' + this.lang.systemTypes[type] + '<br />' + this.lang.systemSource + ': ' + folder + '<br /></div>'

        Ext.Msg.show({
           minWidth:250,
           msg: msg,
           buttons: Ext.Msg.OK
        });
    },

    onGridSelectionChange: function(sm){
        this.picturesView.clearSelections();
        var ds = sm.grid.dataSource;
        var records  = sm.getSelections();
        if (records.length == 0) {
            return;
        }
        for(var i = 0, len = records.length; i < len; i++){
            this.picturesView.select(ds.indexOf(records[i]), true);
        }
    },

    showProgressDialog: function(message) {
        this.dialog = Ext.MessageBox.show({
            title: this.lang.titlePleaseWait,
            width: 240,
            msg: '<img src="../js/ext/resources/images/default/grid/loading.gif" /> ' + message,
            closable: false
           });
    },

    render: function() {
        this.foldersTree.render();
        this.foldersTree.dragZone.onBeforeDrag = this.onBeforeDrag;
//        this.uploadFolder.fireEvent('click', this.rootFolder);
//        this.uploadFolder.expand();
        this.filesGrid.render();
        this.nestedCenterLayout.showPanel('files');
    },

    viewPicture: function(imageURL){
        var imageTitle = imageURL;
        var autoclose = true;
        var PositionX = 10;
        var PositionY = 10;
        var defaultWidth  = 600;
        var defaultHeight = 400;
        var imgWin = window.open('','_blank','scrollbars=no,resizable=1,width='+defaultWidth+',height='+defaultHeight+',left='+PositionX+',top='+PositionY);
        if( !imgWin ) { return true; } //popup blockers should not cause errors
        imgWin.document.write('<html><head><title>'+imageTitle+'<\/title><script type="text\/javascript">\n'+
            'function resizeWinTo() {\n'+
            'if( !document.images.length ) { document.images[0] = document.layers[0].images[0]; }'+
            'var oH = document.images[0].height, oW = document.images[0].width;\n'+
            'if( !oH || window.doneAlready ) { return; }\n'+ //in case images are disabled
            'window.doneAlready = true;\n'+ //for Safari and Opera
            'var x = window; x.resizeTo( oW + 200, oH + 200 );\n'+
            'var myW = 0, myH = 0, d = x.document.documentElement, b = x.document.body;\n'+
            'if( x.innerWidth ) { myW = x.innerWidth; myH = x.innerHeight; }\n'+
            'else if( d && d.clientWidth ) { myW = d.clientWidth; myH = d.clientHeight; }\n'+
            'else if( b && b.clientWidth ) { myW = b.clientWidth; myH = b.clientHeight; }\n'+
            'if( window.opera && !document.childNodes ) { myW += 16; }\n'+
            'x.resizeTo( oW = oW + ( ( oW + 200 ) - myW ), oH = oH + ( (oH + 200 ) - myH ) );\n'+
            'var scW = screen.availWidth ? screen.availWidth : screen.width;\n'+
            'var scH = screen.availHeight ? screen.availHeight : screen.height;\n'+
            'if( !window.opera ) { x.moveTo(Math.round((scW-oW)/2),Math.round((scH-oH)/2)); }\n'+
            '}\n'+
            '<\/script>'+
            '<\/head><body onload="resizeWinTo();"'+(autoclose?' onblur="self.close();"':'')+'>'+
            (document.layers?('<layer left="0" top="0">'):('<div style="position:absolute;left:0px;top:0px;">'))+
            '<img src='+imageURL+' alt="Loading image ..." title="" onload="resizeWinTo();">'+
            (document.layers?'<\/layer>':'<\/div>')+'<\/body><\/html>');
        imgWin.document.close();
        if( imgWin.focus ) { imgWin.focus(); }
        //return false;
    },

// files actions
    deleteFiles: function(files) {
        this.actionOptions = {
            files: files
        };
        Ext.MessageBox.confirm('', this.lang.confirmDelete, this.deleteFilesConfirmation, this);
    },

    deleteFilesConfirmation: function(btn) {
        if (btn != 'yes') {
            return false;
        }
        var conn = new Ext.data.Connection();
        var params = new Array();
        var i;
        var file;
        this.showProgressDialog(this.lang.processDelete);
        for (i = 0; i < this.actionOptions.files.length; i++) {
            file = this.actionOptions.files[i];
            params['filenames[' + i + ']'] = file.data.folder + file.data.filename;
        }
        conn.request({
            url: this.backendUrl + 'do=delete',
            params: params,
            action: 'delete',
            callback: this.onFinishAction,
            scope: this
        });
    },

    moveFiles: function(files, source_path, destination_path) {
        this.actionOptions = {
            files: files,
            sourcePath: source_path,
            destinationPath: destination_path
        }
        Ext.MessageBox.confirm('', this.lang.confirmMove, this.moveFilesConfirmation, this);
    },

    savePanelGridState: function() {
        //if(this.editor)this.editor.cancelEdit();
        Ext.lib.Ajax.request('GET', this.backendUrl + 'do=save_panel_state&state=0',{timeout: 30000},null);
    },

    savePanelViewState: function() {
        //if(this.editor)this.editor.cancelEdit();
        Ext.lib.Ajax.request('GET', this.backendUrl + 'do=save_panel_state&state=1',{timeout: 30000},null);
    },

    moveFilesConfirmation: function(btn) {
        if (btn != 'yes') {
            return false;
        }
        var conn = new Ext.data.Connection();
        var params = new Array();
        var i;
        this.showProgressDialog(this.lang.processMove);
        params['source_path'] = this.actionOptions.sourcePath;
        params['destination_path'] = this.actionOptions.destinationPath;
        for (i = 0; i < this.actionOptions.files.length; i++) {
            params['files[' + i + ']'] = this.actionOptions.files[i].data.filename;
        }
        conn.request({
            url: this.backendUrl + 'do=move_files',
            params: params,
            action: 'move',
            callback: this.onFinishAction,
            scope: this
        });
    },

    copyFiles: function(files, source_path, destination_path) {
        this.actionOptions = {
            files: files,
            sourcePath: source_path,
            destinationPath: destination_path
        }
        Ext.MessageBox.confirm('', this.lang.confirmCopy, this.copyFilesConfirmation, this);
    },

    copyFilesConfirmation: function(btn) {
        if (btn != 'yes') {
            return false;
        }
        var conn = new Ext.data.Connection();
        var params = new Array();
        var i;
        this.showProgressDialog(this.lang.processCopy);
        params['source_path'] = this.actionOptions.sourcePath;
        params['destination_path'] = this.actionOptions.destinationPath;
        for (i = 0; i < this.actionOptions.files.length; i++) {
            params['files[' + i + ']'] = this.actionOptions.files[i].data.filename;
        }
        conn.request({
            url: this.backendUrl + 'do=copy_files',
            params: params,
            action: 'copy',
            callback: this.onFinishAction,
            scope: this
        });
    },

    viewFile: function(file) {
        if (file.data.content == 'image') {
            this.viewPicture(file.data.view_url);
        } else {
            var win = window.open(file.data.view_url,'','width=600,height=400,menu=yes,status=yes,scrollbars=no');
        }
    },

    modifyFile: function(file) {
        if (file.data.id) {
            document.location = 'files_admin.php?show=modify&id=' + file.data.id + '&file=' + file.data.filename + "&folder=" + file.data.folder;
        } else {
            document.location = 'files_admin.php?show=modify&id=&file=' + file.data.filename + "&folder=" + file.data.folder;
        }
    },

    createFolder: function(parent) {
        this.actionOptions = {
            parentFolder: parent
        }
        Ext.MessageBox.prompt('', this.lang.promptFolderName, this.createFolderConfirmation, this);
    },

    createFolderConfirmation: function(btn, text) {
        if (btn != 'ok') {
            return false;
        }
        this.actionOptions.name = text;
        var conn = new Ext.data.Connection();
        var params = {
            parent_folder: this.actionOptions.parentFolder.id,
            name: text
        }
        this.showProgressDialog(this.lang.processCreatingFolder);
        conn.request({
            url: this.backendUrl + 'do=add_folder',
            params: params,
            action: 'add_folder',
            callback: this.onFinishAction,
            scope: this
        });
    },

    emptyFolder: function(node) {
        this.actionOptions = {
            folder: node
        }
        Ext.MessageBox.confirm('', this.lang.confirmEmpty, this.emptyFolderConfirmation, this);
    },

    emptyRecycle: function(node) {
        this.actionOptions = {
            folder: node
        }
        Ext.MessageBox.confirm('', this.lang.confirmEmpty, this.emptyRecycleConfirmation, this);
    },

    emptyRecycleConfirmation: function(btn) {
        if (btn != 'yes') {
            return false;
        }
        var conn = new Ext.data.Connection();
        this.showProgressDialog(this.lang.processEmptyingFolder);
        conn.request({
            url: this.backendUrl + 'do=empty_recycle',
            action: 'empty_folder',
            callback: this.onFinishAction,
            scope: this
        });
    },

    emptyFolderConfirmation: function(btn) {
        if (btn != 'yes') {
            return false;
        }
        var conn = new Ext.data.Connection();
        var params = {
            folder: this.actionOptions.folder.id
        }
        this.showProgressDialog(this.lang.processEmptyingFolder);
        conn.request({
            url: this.backendUrl + 'do=empty_folder',
            params: params,
            action: 'empty_folder',
            callback: this.onFinishAction,
            scope: this
        });
    },

    deleteFolder: function(node) {
        //if (node.id!= 'root/upload/Recycle' && node.id!= 'root/upload') {
        if (node.id!= 'root/upload') {
            this.actionOptions = {
                folder: node,
                parentFolder: node.parentNode
            }
            Ext.MessageBox.confirm('', this.lang.confirmDeleteFolder, this.deleteFolderConfirmation, this);
         } else {
             Ext.MessageBox.alert(this.lang.lbl_alert, this.lang.msg_you_cant_delete_folder);
         }
    },

    deleteFolderConfirmation: function(btn) {
        if (btn != 'yes') {
            return false;
        }
        var conn = new Ext.data.Connection();
        var params = {
            folder: this.actionOptions.folder.id
        }
        this.showProgressDialog(this.lang.processDeletingFolder);
        conn.request({
            url: this.backendUrl + 'do=delete_folder',
            params: params,
            action: 'delete_folder',
            callback: this.onFinishAction,
            scope: this
        });
    },

    createThumbnails: function(node) {
        this.actionOptions = {
            folder: node
        }
        Ext.MessageBox.confirm('', this.lang.confirmCreateThumbnails, this.createThumbnailsConfirmation, this);
    },

    createThumbnailsConfirmation: function(btn) {
        if (btn != 'yes') {
            return false;
        }
        var conn = new Ext.data.Connection();
        var params = {
            folder: this.actionOptions.folder.id
        }
        this.showProgressDialog(this.lang.processCreatingThumbnails);
        conn.request({
            url: this.backendUrl + 'do=create_thumbnails',
            params: params,
            action: 'create_thumbnails',
            callback: this.onFinishAction,
            scope: this
        });
    },

    copyFolder: function(folder, destination) {
        var conn = new Ext.data.Connection();
        var params = {
            source: this.actionOptions.folder.id,
            destination: this.actionOptions.destination.id
            //destination: this.actionOptions.destination
        }
        this.showProgressDialog(this.lang.processCopyingFolder);
        conn.request({
            url: this.backendUrl + 'do=copy_folder',
            params: params,
            action: 'copy_folder',
            callback: this.onFinishAction,
            scope: this
        });
    },

    moveFolder: function(folder, destination) {
        var conn = new Ext.data.Connection();
        var params = {
            source: this.actionOptions.folder.id,
            destination: this.actionOptions.destination.id
        }
        this.showProgressDialog(this.lang.processMovingFolder);
        conn.request({
            url: this.backendUrl + 'do=move_folder',
            params: params,
            action: 'move_folder',
            callback: this.onFinishAction,
            scope: this
        });
    },

    moveToRecycleFolder: function(folder) {
        if (folder!= 'root/upload/Recycle' && folder!= 'root/upload') {
            var conn = new Ext.data.Connection();
            var params = {
                source: folder
            }
            this.showProgressDialog(this.lang.processDeletingFolder);
            conn.request({
                url: this.backendUrl + 'do=folder_recycle',
                params: params,
                action: 'move_folder',
                callback: this.onFinishAction,
                scope: this
            });
           } else {
               Ext.MessageBox.alert(this.lang.lbl_alert, this.lang.msg_you_cant_delete_recycle);
           }
    },

    restoreSelectedFolder: function(folder) {
        var conn = new Ext.data.Connection();
        var params = {
             source: folder,
             strict: 0
        }
        this.showProgressDialog(this.lang.processRestoring);
        conn.request({
                url: this.backendUrl + 'do=restore_folder',
                params: params,
                action: 'restore_folder',
                callback: this.onFinishAction,
                scope: this
        });
    },


    showConfirmDialog: function(options, overwrite) {

        if (!this.confirmdialog) {
            var div = Ext.DomHelper.insertFirst(document.body, {tag: "div"}, true);
            this.confirmdialog = new Ext.BasicDialog(div, {
                modal:true,
                width:360,
                height:290,
                shadow:true,
                minWidth:300,
                minHeight:200,
                modal:true
            });
            this.confirmdialog.addButton(Ext.MessageBox.buttonText.cancel, this.confirmdialog.hide, this.confirmdialog);
            this.confirmdialog.addButton(Ext.MessageBox.buttonText.yes, function(){
                var options = this.confirmdialog.conopt;
                options.params.overwrite = 1;
                var i =0;
                this.confirmdialogform.items.each(function(f){
                    if (f.isFormField && f.getValue()) {
                        options.params['overwritedata[' + i + ']'] = f.getName();
                        i++;
                    }
                });

                if (i == 0) {
                    this.confirmdialog.hide();
                    return;
                }

                this.showProgressDialog(this.lang.processCopy);
                var conn = new Ext.data.Connection();
                conn.request(options);
                this.confirmdialog.hide();
            }, this);
            this.confirmdialog.body.createChild({tag:"div",html:'<span class="ext-mb-text" style="font-size:11px; display:block; padding-left:65px; height:37px; padding-top:3px; background:transparent url(/admin/pic/ico_folder-open.gif) no-repeat scroll 5%;"></span><div></div>'});
        }

        if (this.confirmdialogform) {
            this.confirmdialogform.items.each( Ext.destroy, Ext ); // Destroy all the fields - Jack's tip
        }

        var form = new Ext.form.Form({
             labelAlign: 'right',
             labelWidth: 300
        });

        var height = 115;

        form.column({hideLabels:true,width: 300});
        //form.fieldset({legend:'Please select', hideLabels:true});
        for (var i=0; i<overwrite.length; i++) {
            form.add(new Ext.form.Checkbox({
                          boxLabel:'<span style="line-height:24px; font-size:11px; padding-top:2px; padding-left:25px; background:transparent url(/admin/' + overwrite[i].sicon + ') no-repeat scroll 0%;">' + overwrite[i].name + '</span>',
                          name:overwrite[i].name,
                          checked:true,
                          width:'auto'
                    }));
            height += 28;
        }

        //form.end();
        this.confirmdialog.conopt = options;
        var message='';
        if (overwrite.length == 1) {
            if (overwrite[0].type == 'folder'){
                //message = '<div style="font-size:11px; display:block; height:50px; padding-left:50px; background:transparent url(/admin/' + overwrite[0].icon + ') no-repeat scroll 0%;">' + overwrite[0].name + '</div>';
                height = 160;
            } else {
                message = '<span style="font-size:11px; display:block; padding-left:10px; padding-top:8px;">'+this.lang.ovwc_rplfolowed+'<br/><div style="display:block; height:40px; padding-left:50px; background:transparent url(/admin/' + overwrite[0].icon + ') no-repeat scroll 0%;">';
                message += (overwrite[0].dsize/1000) + ' Kb<br />' + this.lang.ovwc_lastupdated + overwrite[0].ddate + '</div>';
                message += this.lang.ovwc_rplby+'<br /><div style="display:block; font-size:11px; height:50px; padding-left:50px; background:transparent url(/admin/' + overwrite[0].icon + ') no-repeat scroll 0%;">&nbsp;<br />' + (overwrite[0].ssize/1000) + ' Kb<br />' + this.lang.ovwc_lastupdated + overwrite[0].sdate + '</div></span>';
                height = 290;
            }
        }

        if(height > 310) height = 310;

        this.confirmdialog.resizeTo(360, height);
        this.confirmdialog.show();


        var div = this.confirmdialog.body.dom.firstChild;
        var title;
        if (overwrite.length == 1) {
            if (overwrite[0].type == 'folder') {
                title = String.format(this.lang.ovwc_folder, overwrite[0].name);
            } else {
                title = String.format(this.lang.ovwc_file, overwrite[0].name);
            }
        } else {
            if (overwrite[0].type == 'file') {
                title = this.lang.ovwc_files
            }
        }

        Ext.get(div.firstChild).update(title);
        Ext.get(div.childNodes[1]).update('<span>' + message +'</span><div></div>');
        form.render(div.childNodes[1].childNodes[1]);
        if (overwrite.length == 1) {
            Ext.get(div.childNodes[1].childNodes[1]).setVisible(false);
        }
        this.confirmdialogform = form;
    },

    showPrivsDialog: function() {
        var folder = this.selectedNode;
        if(folder.attributes.owner == 0) {
            Ext.Msg.alert(this.lang.labelErrorTitle, this.lang.errorParentPriv);
            return;
        }

        var file = this.filesGrid.getSelectionModel().getSelected();
        this.privsdialog.show(this.ds, {
            file:  file.get('filename'),
            owner: file.get('owner'),
            folder: file.get('folder')
        });
    },

    showFolderPrivsDialog: function() {
        var folder = this.selectedNode;

        if(folder.parentNode.attributes.owner == 0) {
            Ext.Msg.alert(this.lang.labelErrorTitle, this.lang.errorParentPriv);
            return;
        }

        this.startFolder = folder.id;
        this.privsdialog.show(this.rootFolder, {
            owner: folder.attributes.owner,
            folder: folder.id,
            type: 'folder'
        });
    },


    restoreFiles: function (files, source_path) {
        var conn = new Ext.data.Connection();
        var params = [];
        var i;
        this.showProgressDialog(this.lang.processRestoring);
        for (i = 0; i < files.length; i++) {
            params['files[' + i + ']'] = files[i].data.folder + files[i].data.filename;
        }
        conn.request({
            url: this.backendUrl + 'do=restore_files',
            params: params,
            action: 'restore_files',
            callback: this.onFinishAction,
            scope: this
        });
    },

// public service methods
    viewSelectedFile: function() {
        this.viewFile(this.filesGrid.getSelectionModel().getSelected());
    },

    modifySelectedFile: function() {
        this.modifyFile(this.filesGrid.getSelectionModel().getSelected());
    },

    deleteSelectedFile: function() {
        this.deleteFiles([this.filesGrid.getSelectionModel().getSelected()]);
    },

    emptySelectedFolder: function() {
        this.emptyFolder(this.selectedNode);
    },

    deleteSelectedFolder: function() {
        this.deleteFolder(this.selectedNode);
    },

    createThumbnailsInSelectedFolder: function() {
        this.createThumbnails(this.selectedNode);
    },

// grid rendering functions
    gridRenderFilename: function(data, cellMeta, record, rowIndex, colIndex, store) {
        var style;
        if (1 == record.get('disabled') || 0 == record.get('owner')) {
            style = ' class="';
            if (0 == record.get('owner')){
                style += 'ext-file-noowner ';
            }
            if (record.get('masked')) {
                style += ' ext-file-disabled';
            }
            style += '"';
        }

        return '<a target="_blank" href="' + record.get('view_url') + '"><img src="' + record.get('icon') + '" width="16px" height="16px" align="absmiddle" border="0" alt="' + record.get('description') + '" /></a>&nbsp;<span' + style + '>' + data + '.' + record.data.type + '</span>';
    },


    gridRenderFileSize: function(data, cellMeta, record, rowIndex, colIndex, store) {
        var size;
        if (data > 1048576) {
            size = Math.round(data / 1048576) + '&nbsp;Mb';
        } else if (data > 1024) {
            size = Math.round(data / 1024) + '&nbsp;Kb';
        } else {
            size = data + '&nbsp;b';
        }
        return '<div align="right"' + ((1 == record.get('masked'))?' class="ext-file-disabled"':'') + '>' + size + '</div>';
    },

    gridRenderFileDate: function(data, cellMeta, record, rowIndex, colIndex, store) {
        return '<div align="right"' + ((1 == record.get('masked'))?' class="ext-file-disabled"':'') + '>' + Ext.util.Format.date(data, 'd.m.y h:i') + '</div>';
    },

    gridRenderFileDesc: function(data, cellMeta, record, rowIndex, colIndex, store) {
        return '<div' + ((1 == record.get('masked'))?' class="ext-file-disabled"':'') + '>' + data + '</div>';
    },


// Browser events
    onFinishAction: function(options, success, response) {
        this.dialog.hide();
        response = Ext.util.JSON.decode(response.responseText);

        if (response && response.error != '') {
            //alert('error: ' + response.error);
            Ext.MessageBox.alert(this.lang.lbl_alert, 'error: ' + response.error);
            switch (options.action) {
                case 'rename_folder':
                    this.rootFolder.reload();
                break;
            }

        } else if (response && response.overwrite && response.overwrite.length>0) {
             this.showConfirmDialog(options, response.overwrite);
             return;
        } else {
            switch (options.action) {
                case 'delete':
                    this.ds.reload();
                break;
                case 'move':
                    this.ds.reload();
                break;
                case 'add_folder':
                    this.actionOptions.parentFolder.newFolderName = this.actionOptions.name;
                    this.actionOptions.parentFolder.reload(this.onReloadNode);
                break;
                case 'refresh':
                case 'empty_folder':
                    this.rootFolder.reload();
                    this.ds.reload();
                case 'create_thumbnails':
                    this.ds.reload();
                break;
                case 'delete_folder':
                    this.actionOptions.parentFolder.newFolderName = false;
                    this.actionOptions.parentFolder.reload(this.onReloadNode);
                break;
                case 'move_folder':
                    this.rootFolder.reload();
                    this.ds.reload();
                break;
                case 'restore_folder':
                    if (response && response.parents && response.parents == 1) {
                        this.actionOptions = options;
                        this.actionOptions.params.strict = 1;
                        Ext.MessageBox.confirm('', this.lang.restoreParentsConfirmation, this.mainConfirmation, this);
                        return;
                    }
                    this.rootFolder.reload();
                    this.ds.reload();
                break;
                case 'copy_folder':
                    this.actionOptions.source.reload();
                    this.startFolder = this.actionOptions.destination.id.substr(11) + '/' + this.actionOptions.folder.text;
                    this.rootFolder.reload();
                break;
                case 'get_files':
                break;
                case 'rename_folder':
                    this.rootFolder.reload();
                break;
                case 'restore_files':
                    if (response && response.parents && response.parents == 1) {
                        this.actionOptions = options;
                        this.actionOptions.params.strict = 1;
                        Ext.MessageBox.confirm('', this.lang.restoreParentsConfirmation, this.mainConfirmation, this);
                        return;
                    }
                    this.ds.reload();
                    var a = this.actionOptions;
                    if (a.params && a.params.strict && a.params.strict == 1) {
                        this.rootFolder.reload();
                    }
            }
        }
        this.actionOptions = {};
    },

    mainConfirmation: function(btn) {
        if (btn != 'yes') {
            return false;
        }
        this.showProgressDialog(this.lang.titlePleaseWait);
        var conn = new Ext.data.Connection();
        conn.request(this.actionOptions);
    },

    onReloadNode: function(node) {
        var folder;
        if (node.newFolderName) {
            // after creating folder
            folder = node.getOwnerTree().getNodeById(node.id + '/' + node.newFolderName);
        } else {
            // after deleting folder
            folder = node;
        }
        if (folder) {
            folder.fireEvent('click', folder);
        }
        this.actionOptions = {};
    },

    onDragOver: function(e) {

        if(e.target == this.selectedNode) {
            return false;
        }

        var el;
        if (!e.dropNode && (e.data.grid || e.data.record || e.data.ddel)) {
            if (e.data.grid == this.filesGrid) {
                for (i = 0; i < e.data.selections.length; i++) {
                    el = e.data.selections[i].data;
                    if (el.disabled == 1 || el.owner == 0)return false;
                }
            } else if (e.data.record) {
                el = e.data.record.data;
                if (el.disabled == 1 || el.owner == 0)return false;
            } else if(e.data.ddel) {
                 for (i = 0; i < e.data.indexes.length; i++) {
                     el = this.ds.getAt(e.data.indexes[i]).data;
                     if (el.disabled == 1 || el.owner == 0)return false;
                 }
            }
        }

        var t = e.target.attributes;
        if (t.owner == 0 || t.disabled == 1 || (e.dropNode && e.dropNode.parentNode.id == e.target.id)) {
                return false;
        }

        return true;
    },

    onBeforeDrag: function(data, e){
        var a = data.node.attributes;
        if(data.node.id == 'root/upload' || a.owner==0 || a.disabled == 1){
            return false;
        }
        return true;
    },

    onGridStartDrag: function(grid, dd, e){


        var a = data.node.attributes;
        if(data.node.id == 'root/upload' || a.owner==0 || a.disabled == 1){
            return false;
        }
        return true;
    },

    onTreeLoad: function(node) {

        if (node.id == 'root') {
            var n = this.foldersTree.getNodeById('root/upload');
            if (n) {
                if (this.startFolder != '') {
                    n.startFolder = this.startFolder;
                    document.onExpandStartFolder = this.onExpandStartFolder;
                    n.expand(false, true, this.onExpandStartFolder);
                } else {
                    n.fireEvent('click', n);
                }
            }
            //n = this.foldersTree.getNodeById('root/upload/Recycle');
            //if (n) {
            //    n.expand();
            //}
        }

    },

    onExpandStartFolder: function(node) {
        var startFolder = node.startFolder.substr(1);
        if (startFolder == '' || !startFolder) {
            node.fireEvent('click', node);
            return;
        }
        startFolder += '/';
        var nextNode = node.id + '/' + startFolder.substr(0, startFolder.indexOf('/'));
        startFolder = startFolder.substr(startFolder.indexOf('/'));
        for (var i = 0; i < node.childNodes.length; i++) {
            if (node.childNodes[i].id == nextNode) {
                node.childNodes[i].startFolder = startFolder.substr(0, startFolder.length - 1);
                node.childNodes[i].expand(false, true, document.onExpandStartFolder);
                return;
            }
        }
    },
    onGetUserAction: function (options, success, response) {
        var node = this.foldersTree.getSelectionModel().getSelectedNode();
        response = Ext.util.JSON.decode(response.responseText);
        if (response && response.error != '') {
            Ext.MessageBox.alert(this.lang.lbl_alert, 'error: ' + response.error);
            //this.rootFolder.reload();
        } else {
            currentNode = node;
            this.selectedNode = node;
            this.clearFilter();
            this.ds.load();
        }

    },
    onTreeClick: function(node, e) {

            if(node.attributes.disabled){
                this.btAdd1.disable();
                this.btAdd2.disable();
                this.checkbox.disable();
                this.checkbox2.disable();
            } else {
                this.btAdd1.enable();
                this.btAdd2.enable();
                this.checkbox.enable();
                this.checkbox2.enable();
            }

            currentNode = node;
            this.selectedNode = node;
            this.clearFilter();
            this.ds.load();
        //}
    },

    onTreeBeforeNodeDrop: function(dropEvent) {
        //var conn = new Ext.data.Connection();
        var i;
        if (!dropEvent.dropNode && (dropEvent.data.grid || dropEvent.data.record || dropEvent.data.ddel)) {
            // drop file
            var files;
            if (dropEvent.data.grid == this.filesGrid) {
                if (this.selectedNode.id == dropEvent.target.id) {
                    return;
                }
                files = dropEvent.data.selections;
            } else if (dropEvent.data.record) {
                //if (dropEvent.data.record.data.disabled == 1)return false;
                files = [dropEvent.data.record];
            } else if(dropEvent.data.ddel ) {
                 files = [];
                 for (i = 0; i < dropEvent.data.nodes.length; i++) {
                     files[i] = {data: {filename: dropEvent.data.nodes[i].id}};
                 }
            }
            this.actionOptions = {
                    files: files,
                    sourcePath: this.selectedNode.id,
                    destinationPath: dropEvent.target.id
                }
                this.copyMoveMenu.showAt(dropEvent.rawEvent.getXY());
                return true;

            /*if (dropEvent.target.id == 'root/trash/Recycle') {

                this.moveFiles(files);

            } else {
                this.actionOptions = {
                    files: files,
                    sourcePath: this.selectedNode.id,
                    destinationPath: dropEvent.target.id
                }
                this.copyMoveMenu.showAt(dropEvent.rawEvent.getXY());
                return true;
            }*/
        } else {

            //if (dropEvent.target.id = 'root/upload/Recycle' || dropEvent.target.id.indexOf('root/upload/Recycle/') != -1) {
            //    this.moveToRecycleFolder(dropEvent.dropNode.id);
            //    return false;
            //}


            // drop folder
            this.actionOptions = {
                folder: dropEvent.dropNode,
                source: dropEvent.dropNode.parentNode,
               //destination: dropEvent.target.id
               destination: dropEvent.target
            }

            this.copyMoveMenu.showAt(dropEvent.rawEvent.getXY());
            return true;
        }
    },

    onNodeMove: function(tree, node, oldParent, newParent, index) {
        return true
    },


    viewLinks: function (index) {
        var selected;
        if (index === false ) {
            selected = this.filesGrid.getSelectionModel().getSelected();
        } else {
            this.filesGrid.getSelectionModel().selectRow(index);
            selected = this.filesGrid.getSelectionModel().getSelected();
        }
       var dt = new Date(selected.get('last_modified'));
       var bullet = "<img src='pic/tree_dot.gif' alt='' border=0>"
       var html = '<div id=\"filepreview\" class=\"filepreview\"><table><tr><td>'+
            '<label id="objname"><b>' + this.lang.gridFileName + '</b>: ' + selected.get('filename')+ '</label><br />'+
            '<label id="objdate"><b>' + this.lang.gridFileDate + '</b>: '+ dt.format('d.m.y G:i') +'</label><br />'+
            '<label id="objsize"><b>' + this.lang.gridFileSize + '</b>: '+ selected.get('size')/1000 +' kB</label><br />';
            //if (selected.get('group_modify') == 1 || selected.get('other_modify') == 1) {
            //}
            //if (selected.get('group_delete') == 1 || selected.get('other_delete') == 1 ) {
                this.actionOptions = {
                    files: [selected],
                    sourcePath: this.foldersTree.getSelectionModel().getSelectedNode().id
                }

                if (selected.get('disabled') == 0) {
                    html = html + '<label>' + bullet + ' <a href="javascript:browser.modifyFile({data:{folder:\'' + selected.get('folder') +'\', filename:\'' + selected.get('filename') + '\', id:\'' + selected.get('id') + '\'}});">' + this.lang.labelModify + '</a></label><br />';
                    html = html + '<label>' + bullet + ' <a href="javascript:browser.deleteFiles([{data:{folder:\'' + selected.get('folder') +'\', filename:\'' + selected.get('filename') + '\'}}]);">' + this.lang.labelDelete + '</a></label><br />';
                }
            //}

              html = html + '<label>' + bullet + ' <a href="javascript:browser.viewFile({data:{view_url:\'' + selected.get('view_url') +'\', content:\'' + selected.get('content') + '\'}});">' + this.lang.labelView +'</a></label><br />';
              if (selected.get('thumb_url')) {
                  html = html + '<label id="objimage"><a href="javascript:browser.viewFile({data:{view_url:\'' + selected.get('view_url') +'\', content:\'' + selected.get('content') + '\'}});"><img src="' + selected.get('thumb_url') + '" alt="" border="0"></a></label><br />'
              }
          html = html + '</td></tr></table></div>';
        this.fileDetail.update('');
        this.fileDetail.insertHtml('afterBegin',html);
    },

    onGridRowDblClick: function(grid, rowIndex, e) {
        if(!this.selectorMode) {
            this.viewSelectedFile();
        }
        var record = grid.getDataSource().getAt(rowIndex);
        return this.onSelectFile(record);
    },

    onRowSelect: function (selModel, Number,r) {
        this.viewLinks(false);
    },


    onTreeRightClick: function(node, e) {
        node.fireEvent('click', node);
        if (node.id == 'root/Recycle') {
            this.RecycleMenu.showAt(e.getXY());
            e.stopEvent();
            return false;
        } else if (node.attributes.disabled==1) {
            //this.foldersRecycleMenu.items.get(0).setDisabled(!node.attributes.removed);
            this.foldersRecycleMenu.showAt(e.getXY());
            e.stopEvent();
            return false;
        } else {
            var ds = (!node.attributes.owner && node.id!='root/upload')?true:false;
            this.foldersMenu.items.get(0).setDisabled(ds);
            this.foldersMenu.items.get(3).setDisabled(ds);
            this.foldersMenu.items.get(2).setDisabled(node.id=='root/upload');

            var pr = (node.id=='root/upload' && this.foldersMenu.items.get(5));
            this.foldersMenu.items.get(5).setDisabled(pr);


            this.foldersMenu.showAt(e.getXY());
            e.stopEvent();
            return false;
        }
    },

    onGridRowRightClick: function(grid, rowIndex, e) {
        var selected = this.filesGrid.getSelectionModel().getSelected();
        var removed  = selected.get('removed');
        var disabled = selected.get('disabled');
        if (disabled == 1) {
            //this.fileRecycleMenu.items.get(0).setDisabled(!removed);
            this.fileRecycleMenu.showAt(e.getXY());
            e.stopEvent();
            return false;
        } else {
            this.fileMenu.showAt(e.getXY());
            e.stopEvent();
            return false;
        }
    },

    onViewItemClick: function(view, rowIndex, node, e) {
        var sm = this.filesGrid.getSelectionModel();
        var view = sm.grid.getView(), rowIndex;

        if(e.shiftKey && sm.last !== false){
            var last = sm.last;
            sm.selectRange(last, rowIndex, e.ctrlKey);
            sm.last = last; // reset the last
            view.focusRow(rowIndex);
        }else{
            var isSelected = sm.isSelected(rowIndex);
            if(e.button != 0 && isSelected){
                view.focusRow(rowIndex);
            }else if(e.ctrlKey && isSelected){
                sm.deselectRow(rowIndex);
            }else{
                sm.selectRow(rowIndex, e.button == 0 && (e.ctrlKey || e.shiftKey));
                view.focusRow(rowIndex);
            }
        }
    },

    onViewItemDblClick: function (view, index, node, e) {
        if(!this.selectorMode) {
            this.viewSelectedFile();
        }
        var record = view.store.getAt(index);
        return this.onSelectFile(record);
    },

    onFileMenuViewClick: function(menuItem, e) {
        this.viewSelectedFile();
    },

    onFileMenuModifyClick: function(menuItem, e) {
        this.modifySelectedFile();
    },

    onFileMenuDeleteClick: function(menuItem, e) {
        this.deleteFiles(this.filesGrid.getSelectionModel().getSelections());
    },
    onFileMenuRestoreClick: function (menuItem, e) {
        this.restoreFiles(this.filesGrid.getSelectionModel().getSelections());
    },

    onCopyMoveMenuCopyClick: function(menuItem, e) {
        if (this.actionOptions.files) {
            this.copyFilesConfirmation('yes');
        } else {
            this.copyFolder(this.actionOptions.source, this.actionOptions.destinationPath);
        }
    },

    onCopyMoveMenuMoveClick: function(menuItem, e) {
        if (this.actionOptions.files) {
            this.moveFilesConfirmation('yes');
        } else {
            this.moveFolder(this.actionOptions.source, this.actionOptions.destinationPath);
            this.rootFolder.reload();
        }
    },
    onCancelFolderClick: function(menuItem, e) {
        this.rootFolder.reload();
    },
    onFoldersMenuCreateClick: function(menuItem, e) {
        this.createFolder(this.selectedNode);
    },

    onFoldersMenuEmptyClick: function(menuItem, e) {
        //var selected = this.foldersTree.getSelectionModel().getSelectedNode().id;
        //if (selected = 'root/upload/Recycle') {
        //    this.emptySelectedFolder();
        //} else {
            //this.moveToRecycleFolder(this.foldersTree.getSelectionModel().getSelectedNode().id);
            this.emptySelectedFolder();
        //}

    },

    onFoldersMenuDeleteClick: function(menuItem, e) {
        this.moveToRecycleFolder(this.foldersTree.getSelectionModel().getSelectedNode().id);
        //this.deleteFolder(this.foldersTree.getSelectionModel().getSelectedNode());
    },

    refreshFolder: function(menuItem, e) {
        var conn = new Ext.data.Connection();
        var params = {
            folder: this.foldersTree.getSelectionModel().getSelectedNode().id
        }
        this.showProgressDialog(this.lang.processRefreshFolder);
        conn.request({
            url: this.backendUrl + 'do=refresh',
            params: params,
            action: 'refresh',
            callback: this.onFinishAction,
            scope: this
        });
    },

    onFoldersMenuCreateThumbnailsClick: function(menuItem, e) {
        this.createThumbnailsInSelectedFolder();
    },

    onFoldersMenuRestoreClick: function(menuItem, e) {
        this.restoreSelectedFolder(this.foldersTree.getSelectionModel().getSelectedNode().id);
    },

    onSelectFile: function(record) {

    },


    onComboClick1: function() {
        if (!this.combo1.store.data.length) {
            this.combo2.setRawValue(this.combo1.getRawValue());
        } else {
            this.combo2.setValue(this.combo1.getValue());
        }
        this.ds.load();
    },


    onComboClick2: function() {
        if (!this.combo2.store.data.length) {
            this.combo1.setRawValue(this.combo2.getRawValue());
        } else {
            this.combo1.setValue(this.combo2.getValue());
        }
        this.ds.load();
    },

    clearFilter: function(){
        this.combo1.setValue('');
         this.combo2.setValue('');
    },

    onAddClick: function(btn, e) {
        document.location = "files_admin.php?show=add"
            + "&script=" + window.location.pathname
            + "&folder=" + this.foldersTree.getSelectionModel().getSelectedNode().id.replace('root/upload', '')
            + '/';

    },

    beforeNodeEditClickStartEdit: function(editor, el, value){
        var id = this.foldersTree.getSelectionModel().getSelectedNode().id;
        var attr = this.foldersTree.getSelectionModel().getSelectedNode().attributes;
        if (id == 'root/upload' || id=='root/Recycle' || attr.disabled == 1) {
            return false;
        }
        this.treeEditorSelectedEl = el;
        return true;
    },

    beforeNodeEditClickComplete: function(editor, value, startValue){
        if (value == '') {
            editor.setValue(startValue);
            editor.cancelEdit(false);
        }
    },

    onNodeEditClick: function(editor, value, startValue) {
        //var selectedNode = this.foldersTree.selModel.selNode.text;
        if (value == '') {
//            this.foldersTree.selModel.selNode.text = startValue;
//            this.foldersTree.getSelectionModel().getSelectedNode().text = startValue;
//            editor.text = startValue;
            Ext.MessageBox.alert(this.lang.lbl_alert, this.lang.msg_empty_name);
            editor.cancelEdit(false);
            this.rootFolder.reload();
        } else if (startValue == value){
        } else {
            this.renameDir(value,startValue,this.foldersTree.selModel.selNode.id);
        }
    },
    /*onNodeKeyPress: function (e,key) {
        var key = e.keyCode;
        var which = e.which;
        if (key == 46 || which == 46) {
            if (currentNode.id == 'root/upload/Recycle' || currentNode.id == 'root/upload') {
                Ext.MessageBox.alert('Alert!','You can\'t delete this folder');
            } else {
                fileBrowserInstance.actionOptions = {
                    folder: currentNode,
                    parentFolder: currentNode.parentNode
                }
                fileBrowserInstance.moveToRecycleFolder(currentNode.id);
                //fileBrowserInstance.actionsOptions
                //Ext.MessageBox.confirm('', fileBrowserInstance.lang.confirmDeleteFolder, fileBrowserInstance.deleteFolderConfirmation , fileBrowserInstance);
            }
        }
    },*/
     onNodeKeyPress: function (key) {
         var node = this.foldersTree.getSelectionModel().getSelectedNode();
        switch(key.getCharCode()){
            case 113: // f2 key
                this.showFolderEditor();
                break;
            case 46: // delete key
                //if (node.id == 'root/upload/Recycle' || node.id == 'root/upload') {
                if (node.id == 'root/upload') {
                    Ext.MessageBox.alert(this.lang.lbl_alert, this.lang.msg_you_cant_delete_folder);
                } else {
                    //this.moveToRecycleFolder(node.id);
                    this.deleteFolder(node);
                }
        }
        return true;
    },

    renameDir: function(newValue,startValue,path) {
        if (newValue == startValue || newValue == '' || newValue.length == 0) {
            return false;
        }
        var conn = new Ext.data.Connection();
        var params = {
            value: newValue,
            beginValue: startValue,
            dest:path
        }
        this.showProgressDialog(this.lang.processRenamingFolder);
        conn.request({
            url: this.backendUrl + 'do=rename_folder',
            params: params,
            action: 'rename_folder',
            callback: this.onFinishAction,
            scope: this
        });
    },
    onBrowseClick: function(btn,e) {
        fileUploader.browse(document.forms['vorm'].elements['max_size'].value);
    }
});

/**
 * Create a DragZone instance for our View
 */
ImageDragZone = function(view, config){
    this.view = view;
    ImageDragZone.superclass.constructor.call(this, view.getEl(), config);
};
Ext.extend(ImageDragZone, Ext.dd.DragZone, {


    // We don't want to register our image elements, so let's
    // override the default registry lookup to fetch the image
    // from the event instead
    getDragData : function(e){
        e = Ext.EventObject.setEvent(e);
        var target = e.getTarget('.item');
        if(target){

            var view = this.view;
            if(!view.isSelected(target)){
                view.select(target, e.ctrlKey);
            }
            var selNodes = view.getSelectedNodes();
            var selIndexes = view.getSelectedIndexes();
            var dragData = {
                nodes: selNodes
            };
            if(selNodes.length == 1){
                if (this.selDisplayMode == 'grid') {
                    var div = document.createElement('div'); // create the multi element drag "ghost"
                    div.className = 'multi-proxy';
                    div.innerHTML = String.format(Ext.grid.Grid.prototype.ddText, selNodes.length, '');
                    dragData.ddel = div;
                } else {
                    dragData.ddel = target; // the img element
                }

                dragData.grid = view;
                dragData.record = view.store.getAt(selIndexes[0]);
                dragData.single = true;
            }else{
                var div = document.createElement('div'); // create the multi element drag "ghost"
                div.className = 'multi-proxy';

                if (this.selDisplayMode == 'grid') {
                    div.innerHTML = String.format(Ext.grid.Grid.prototype.ddText, selNodes.length, 's');
                } else {
                    for(var i = 0, len = selNodes.length; i < len; i++){
                        div.appendChild(selNodes[i].firstChild.cloneNode(true));
                        if((i+1) % 3 == 0){
                            div.appendChild(document.createElement('br'));
                        }
                    }
                }

                dragData.indexes =  selIndexes;
                dragData.ddel = div;
                dragData.multi = true;
            }
            return dragData;
        }
        return false;
    },

    // the default action is to "highlight" after a bad drop
    // but since an image can't be highlighted, let's frame it
    afterRepair:function(){
        //for(var i = 0, len = this.dragData.nodes.length; i < len; i++){
        //    Ext.fly(this.dragData.nodes[i]).frame('#8db2e3', 1);
        //}
        this.dragging = false;
    },

    // override the default repairXY with one offset for the margins and padding
    getRepairXY : function(e){
        //if(!this.dragData.multi){
        //    var xy = Ext.Element.fly(this.dragData.ddel).getXY();
        //    xy[0]+=3;xy[1]+=3;
        //    return xy;
        //}
        return false;
    }
});

var FileBrowserPrivsEditor = function(url, lang){
    this.backendUrl = url;
    this.lang = lang;
};

FileBrowserPrivsEditor.prototype = {

    dialog: null,
    combo: null,

    show: function (store, params) {
        this.init();
        this.params = params;
        this.reloadstore = store;
        this.dialog.show();
        this.combo.reset();
        if (params.owner) {
            this.combo.setValue(params.owner);
        }
    },

    init: function() {
        if(this.dialog) {
            return;
        }

       var div = Ext.DomHelper.insertFirst(document.body, {tag: "div"}, true);
       this.dialog = new Ext.BasicDialog(div, {
           modal:true,
           width:360,
           height:150,
           shadow:true,
           minWidth:300,
           minHeight:200,
           modal:true
       });
       this.dialog.addButton(Ext.MessageBox.buttonText.cancel, this.dialog.hide, this.dialog);
       this.dialog.addButton(this.lang.titleSave, this.submit, this);

       this.dialog.body.createChild({tag:"div",html:'<span class="ext-mb-text"></span><div></div>'});

        this.form = new Ext.form.Form({
             labelAlign: 'right',
             labelWidth: 70
        });

        var ds = new Ext.data.Store({
                      proxy: new Ext.data.HttpProxy({
                         url: this.backendUrl + 'do=users_store',
                         method:'GET',
                         nocache:true
                      }),
                      reader: new Ext.data.JsonReader({
                             root: 'results',
                             totalProperty: 'total'
                          }, Ext.data.Record.create([{name: 'user'},{name: 'username'}]))
        });

        this.combo = new Ext.form.ComboBox({
            fieldLabel: '<span style="font-size:11px">' + this.lang.labelOwner + '</span>',
            hiddenName:'owner',
            store: ds,
            displayField:'username',
            typeAhead: true,
            valueField: 'user',
            editable:false,
            triggerAction: 'all',
            selectOnFocus:true,
            width:250,
            emptyText:'...',
            allowBlank:false
       });

       this.form.add(this.combo);
       this.form.render(this.dialog.body.dom.firstChild);
       ds.load();
    },

    submit: function() {

        this.params.newowner = this.combo.getValue();

        this.form.submit({
            waitMsg: this.lang.titlePleaseWait,
            url: this.backendUrl + 'do=save_privs',
            params:this.params,
            success: function(form, e) {
                this.reloadstore.reload();
                this.dialog.hide();
            },
            failure: function(form, e) {
                if (e.failureType == 'server') {

                } else {
                    if (typeof e.result.error == 'string') {
                        Ext.Msg.alert(this.lang.labelErrorTitle, e.result.error);
                    }
                }
            },
            scope:this
       });
   }

}
GridInlineEditor = function(container, config){
    GridInlineEditor.superclass.constructor.call(this, container, config);
};

Ext.extend(GridInlineEditor, Ext.grid.EditorGrid, {
    isEditor : true,
    clicksToEdit: 1,
    onCellDblClick : function(g, row, col){
        if (col == 0) return false;
        this.startEditing(row, col);
    }
});