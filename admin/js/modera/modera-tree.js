// register namespace
Ext.namespace('Modera.tree');

Ext.tree.TreeNodeUI.prototype.updateExpandIcon = function() {
    if(this.rendered){
        var n = this.node, c1, c2;

        var cls = n.isLast() ? "x-tree-elbow-end" : "x-tree-elbow";
        var hasChild = n.hasChildNodes();
        if(hasChild){
            if(n.expanded){
                cls += "-minus";
                c1 = "x-tree-node-collapsed";
                c2 = "x-tree-node-expanded";
            }else{
                cls += "-plus";
                c1 = "x-tree-node-expanded";
                c2 = "x-tree-node-collapsed";
            }
            if(this.wasLeaf){
                this.removeClass("x-tree-node-leaf");
                this.wasLeaf = false;
            }
            if(this.c1 != c1 || this.c2 != c2){
                Ext.fly(this.elNode).replaceClass(c1, c2);
                if (this.node.attributes.cls) {
                    Ext.fly(this.elNode).replaceClass(c1 + "-" + this.node.attributes.cls
                        , c2 + "-" + this.node.attributes.cls);
                }

                this.c1 = c1; this.c2 = c2;
            }
        }else{
            if(!this.wasLeaf){
                Ext.fly(this.elNode).replaceClass("x-tree-node-expanded", "x-tree-node-leaf");
                this.wasLeaf = true;
            }
        }
        var ecc = "x-tree-ec-icon "+cls;
        if(this.ecc != ecc){
            this.ecNode.className = ecc;
            this.ecc = ecc;
        }
    }
}

/**
 * Status message object
 *
 * Singleton class for displaying small window with message (usually status report)
 */
Modera.StatusMsg = function(){
    var msgCt, refElement, alignPosition;
    function createBox(text) {
        return '<div class="msg-status"><span>&nbsp;' + text + '&nbsp;</span></div>';
    }

    return {
        init : function(el, pos) {
            refElement = el;
            alignPosition = pos;
        },

        set : function(text){
            if (msgCt) {
                msgCt.remove();
            }
            msgCt = Ext.DomHelper.append(document.body, {
                id:'msg-status',
                visibilityMode: 0
            }, true);
            msgCt.createChild({html:createBox(text)});

            if (refElement && alignPosition) {
                msgCt.alignTo(refElement, alignPosition);
            }
        },

        append : function(text){
            if (!msgCt) {
                Modera.StatusMsg.set(text);
            } else {
                msgCt.createChild({html:createBox(text)});
                if (refElement && alignPosition) {
                    msgCt.alignTo(refElement, alignPosition);
                }
            }
        },

        remove : function(){
            if (!msgCt) {
                return;
            }

            msgCt.fadeOut({remove:true});
        }
    }
}();

Modera.StatusMsg;

/**
 * Selection model
 *
 * Adds following functionality to default selection model:
 * - do not allow selection of nodes that has 'selectable' attribute set to false
 */
Modera.tree.SelectionModel = function() {
    Modera.tree.SelectionModel.superclass.constructor.call(this);
}

Ext.extend(Modera.tree.SelectionModel, Ext.tree.DefaultSelectionModel, {
    select : function(node){
        if (node.attributes.selectable === false) {
            return;
        }

        Modera.tree.SelectionModel.superclass.select.call(this, node);
    }
});

/**
 * Tree controls widget
 *
 * Tree controls are buttons for manipulating tree nodes (move up, move down, remove)
 * and information text area that renders text message when mouse is over one of
 * the buttons
 */
Modera.tree.TreeControl = function(config) {
    Ext.apply(this, config);
    this.disabled = false;
}

Ext.extend(Modera.tree.TreeControl, Ext.util.Observable, {

    init : function(tree) {
        this.tree = tree;
        tree.getSelectionModel().on('selectionchange', this.treeOnSelectionChange, this);
        return this;
    },

    getEl : function() {
        return this.el;
    },

    treeOnSelectionChange : function(selectionModel, node) {
        if (node) {
            if (node.attributes.removable === true) {
                this.enableButtons('remove');
            } else {
                this.disableButtons('remove');
            }

            if (node.attributes.sortable === true) {
                if (node.previousSibling) {
                    this.enableButtons('up');
                } else {
                    this.disableButtons('up');
                }

                if (node.nextSibling && node.nextSibling.attributes.sortable === true) {
                    this.enableButtons('down');
                } else {
                    this.disableButtons('down');
                }

            } else {
                this.disableButtons(['up', 'down']);
            }
        } else {
            this.disableButtons();
        }
    },

    render : function() {
        if (!this.tree) {
            return;
        }

        this.el = Ext.DomHelper.append(this.tree.getEl(), {
            tag : 'div',
            cls : 'x-tree-control-box',
            display: 'none'
        }, true);

        this.textEl = Ext.DomHelper.append(this.el, {
            tag : 'div',
            cls : 'x-tree-control-box-text'
        }, true);

        var btnTemplate = new Ext.Template(
            '<input type="button" class="x-tree-control-box-btn {cls}" />'
        );

        this.buttons = {
            up : btnTemplate.append(this.el, {cls: 'moveup'}),
            down : btnTemplate.append(this.el, {cls: 'movedown'}),
            remove : btnTemplate.append(this.el, {cls: 'remove'})
        };

        for (var n in this.buttons) {
            if (this.buttons.hasOwnProperty(n)) {
                var key = 'btn_' + n + '_msg';
                if (this.languageData && this.languageData[key]) {
                    var msg = this.languageData[key];
                } else {
                    var msg = n;
                }

                var btn = Ext.fly(this.buttons[n]);

                btn.on('click', function(e, scope, action){
                    switch (action) {
                        case 'up':
                            this.tree.moveNodeUp(this.tree.getSelectedNode());
                            break;
                        case 'down':
                            this.tree.moveNodeDown(this.tree.getSelectedNode());
                            break;
                        case 'remove':
                            this.tree.removeNode(this.tree.getSelectedNode());
                            break;
                    }
                }, this, n);

                btn.on('mouseover', function(e, scope, msg){
                    this.setText(msg);
                }, this, msg);

                btn.on('mouseout', function(){this.clear();}, this);
            }
        }

        this.disableButtons();
        this.el.show();
    },

    setText : function(text) {
        if (this.textEl) {
            this.textEl.update(text);
        }
    },

    clear : function() {
        if (this.textEl) {
            this.textEl.update('');
        }
    },

    disableButtons : function(btn) {
        if (this.disabled) {
            return this;
        }

        if (!btn) {
            btn = [];
            for (var n in this.buttons) {
                if (this.buttons.hasOwnProperty(n)) {
                    btn.push(n);
                }
            }
        } else if (typeof btn == 'string') {
            btn = [btn];
        }

        if (btn.length != undefined) {
            this.clear();
            for (var i = 0, len = btn.length; i < len; i++) {
                if (this.buttons[btn[i]]) {
                    Ext.fly(this.buttons[btn[i]]).setOpacity(.4).dom.disabled = true;
                }
            }
        }

        return this;
    },

    enableButtons : function(btn) {
        if (this.disabled) {
            return this;
        }

        if (!btn) {
            btn = [];
            for (var n in this.buttons) {
                if (this.buttons.hasOwnProperty(n)) {
                    btn.push(n);
                }
            }
        } else if (typeof btn == 'string') {
            btn = [btn];
        }

        if (btn.length != undefined) {
            for (var i = 0, len = btn.length; i < len; i++) {
                if (this.buttons[btn[i]]) {
                    Ext.fly(this.buttons[btn[i]]).clearOpacity().dom.disabled = false;
                }
            }
        }

        return this;
    },

    disable : function() {
        this.disableButtons();
        this.disabled = true;
    },

    enable : function() {
        this.disabled = false;
    }
});

/**
 * Tree loader for modera content structure panel
 */
Modera.tree.TreeLoader = function(config) {
    Modera.tree.TreeLoader.superclass.constructor.call(this, config);
    this.applyLoader = false;
}

Ext.extend(Modera.tree.TreeLoader, Ext.tree.TreeLoader, {

    processResponse : function(response, node, callback){
        var json = response.responseText;
        try {
            var o = eval("("+json+")");
            var nodes = this.processNodes(o);
            delete o;
            for(var i = 0, len = nodes.length; i < len; i++){
                node.appendChild(this.createNode(nodes[i]));
            }
            if(typeof callback == "function"){
                callback(this, node);
            }
        }catch(e){
            this.handleFailure(response);
        }
    },

    processNodes : function(nodes) {
        return [
            this.processTemplateNodes(nodes.templates),
            this.processContentNodes(nodes.content),
            this.processTrashNodes(nodes.trash)
        ];
    },

    processTemplateNodes : function(nodes) {
        for (var i = 0, len = nodes.length; i < len; i++) {
            Ext.apply(nodes[i], {
                leaf : true,
                cls : "template-node",
                removable : true
            });
        }

        nodes.push({
            text : this.languageData.new_template,
            cls : "new-template",
            selectable : false,
            leaf : true,
            treeAction : 'new-template',
            draggable : false
        });

        return {
            text : this.languageData.templates_hdr,
            id : "templates",
            cls : "templates-hdr",
            draggable : false,
            children : nodes
        };
    },

    processContentNodes : function(nodes) {
        this.processContentNodesRecursive(nodes);

        nodes.push({
            text : this.languageData.new_page,
            leaf : true,
            cls : "new-page",
            selectable : false,
            draggable : false,
            treeAction : 'new-page',
            newPageItem : true
        });

        return {
            text : this.languageData.structure_hdr,
            id : "content",
            cls : "content-hdr",
            draggable : false,
            children : nodes
        };
    },

    processContentNodesRecursive : function(nodes)
    {
        for (var i = 0, len = nodes.length; i < len; i++) {
            Ext.apply(nodes[i], {
                leaf : false,
                cls : "content-node",
                sortable : true,
                removable : true
            });

            if (nodes[i].children) {
                this.processContentNodesRecursive(nodes[i].children)
            } else {
                nodes[i].children = [];
            }

            nodes[i].children.push({
                text : this.languageData.new_page,
                leaf : true,
                cls : "new-page",
                selectable : false,
                draggable : false,
                treeAction : 'new-page',
                newPageItem : true
            });
        }
    },

    processTrashNodes : function(nodes) {
        for (var i = 0, len = nodes.length; i < len; i++) {
            Ext.apply(nodes[i], {
                leaf : true,
                cls : "trash-node",
                removable : true
            });
        }

        nodes.push({
            text : this.languageData.empty_trash,
            cls : "empty-trash",
            selectable : false,
            leaf : true,
            treeAction : 'empty-trash',
            draggable : false
        });

        return {
            text : this.languageData.trash_hdr,
            id : "trash",
            cls : "trash-hdr",
            draggable : false,
            children : nodes
        };
    }
});

/**
 * Modera's content structure control
 */
Modera.tree.ContentStructurePanel = function(el, config) {

    Modera.tree.ContentStructurePanel.superclass.constructor.call(this, el, config);
    Ext.apply(this, {
        loader : new Modera.tree.TreeLoader({
            languageData : config.languageData,
            requestMethod : 'GET',
            dataUrl : config.backendUri
        }),
        pathSeparator : '.',
        enableDD : true,
        rootVisible : false,
        backendModel : new Modera.tree.BackendModel(this, config.backendUri),
        treeControl : new Modera.tree.TreeControl({languageData:config.languageData}),
        selModel : new Modera.tree.SelectionModel()
    });

    Ext.apply(this.events, {
        'beforesave' : true,
        'save' : true,
        'contentnodeactivated' : true,
        'trashnodeactivated' : true,
        'templatenodeactivated' : true,
        'newcontentnode' : true,       // occurs when new page item is clicked
        'newtemplatenode' : true,    // occurs when new template clicked
        'beforeajaxsuccess' : true,
        'ajaxsuccess' : true,
        'emptytrash' : true
    });

    this.on({
        nodedragover : this.onNodeDragOver,
        beforemove : this.onBeforeMove,
        expand : this.saveState,
        collapse : this.saveState,
        move : this.saveState,
        click : this.onNodeClick,
        // before tree data is loaded
        beforeload : function(node) {
            if (node === this.root) {
                Modera.StatusMsg.set(this.languageData['loading_msg']);
            }
        },
        // after tree data is loaded
        load : function(node) {
            if (node === this.root) {
                Modera.StatusMsg.remove();
            }
        },
        // before XHR request for saving some change is made
        beforesave : function(tree) {
            tree.lock();
            Modera.StatusMsg.set(tree.languageData['saving_msg']);
        },
        // after saving data XHR request is done
        save : function(tree) {
            Modera.StatusMsg.remove();
        }
    });
}

Ext.extend(Modera.tree.ContentStructurePanel, Ext.tree.TreePanel, {

    render : function(){
        this.treeControl.init(this).render();
        Modera.tree.ContentStructurePanel.superclass.render.call(this);
        Modera.StatusMsg.init(this.treeControl.getEl(), 'tr-br');
        Modera.StatusMsg.set(this.languageData['loading_msg']);
    },

    addContentNode : function(parentNode) {
        if (parentNode.id == 'content') {
            var parentNodeId = 0;
        } else {
            var parentNodeId = parentNode.id;
        }
        this.fireEvent('newContentNode', parentNodeId);
    },

    addTemplateNode : function() {
        this.fireEvent('newTemplateNode');
    },

    emptyTrash : function() {
        var trashNode = this.getNodeById('trash');
        if (trashNode.childNodes.length > 1
            && confirm(this.languageData['confirm_empty_trash']))
        {
            this.fireEvent('emptyTrash');
            if (trashNode) {
                var node;
                while (node = trashNode.firstChild) {
                    if (node.attributes.removable === true) {
                        trashNode.removeChild(node);
                    } else {
                        break;
                    }
                }
            }

            this.forceReload = true;
            this.backendModel.emptyTrash();
        }
    },

    activateNode : function(node) {
        var eventName;
        if (this.getNodeById('content').contains(node)) {
            eventName = 'contentNodeActivated';

        } else if (this.getNodeById('trash').contains(node)) {
            eventName = 'trashNodeActivated';

        } else if (this.getNodeById('templates').contains(node)) {
            eventName = 'templateNodeActivated';
        } else {
            return;
        }

        if (this.activeNode) {
            Ext.fly(this.activeNode.getUI().elNode).removeClass('active-node');
        }

        this.activeNode = node;
        Ext.fly(node.getUI().elNode).addClass('active-node');

        this.fireEvent(eventName, node.id);
    },

    onNodeClick : function(node, e) {
        if (typeof node.attributes.treeAction == 'string') {
            switch (node.attributes.treeAction) {
                case 'new-page':
                    this.addContentNode(node.parentNode);
                    break;
                case 'new-template':
                    this.addTemplateNode();
                    break;
                case 'empty-trash':
                    this.emptyTrash();
                    break;
            }
        } else {
            this.activateNode(node);
        }
    },

    onNodeDragOver : function(e) {
        // disable possibility to drop above next sibling and below previous sibling
        if (e.target.parentNode === e.dropNode.parentNode) {
            if (e.target.previousSibling === e.dropNode && e.point == 'above') {
                return false;
            }
            if (e.target.nextSibling === e.dropNode && e.point === 'below') {
                return false;
            }
        }

        // disable possiblity for dropping nodes above or below trash and
        // templates children
        if (e.target.parentNode && (e.target.parentNode.id == 'templates'
           || e.target.parentNode.id == 'trash'))
        {
            return false;
        }

        // disable possibility to drop child nodes to it's parent
        if (e.target === e.dropNode.parentNode && e.point == 'append') {
            return false;
        }

        // disable possibility to drop on level 1 (level where templates,
        // content and trash containers reside)
        if (e.target.getDepth() == 1 && e.point != 'append') {
            return false;
        }

        // inserting below new page items is disabled
        if (e.target.attributes.newPageItem && e.point == 'below') {
            // hide drop indicator
            this.dropZone.removeDropIndicators({ddel:e.target.getUI().getEl().firstChild});
            return false;
        }

        // if node is expanded and has no children than inserting below new node item
        // is disabled
        if (e.target.attributes.newPageItem && e.target.parentNode.childNodes.length < 2) {
            return false;
        }

    },

    onBeforeMove : function(tree,  node, oldParent, newParent, index) {

        // on append appending node will be inserted before last child
        // which is control item always in our case
        if (newParent.childNodes.length == index) {
            // perform insert action instead of append
            newParent.insertBefore(node, newParent.lastChild);
            return false;
        }

        var contentNode = this.getNodeById('content');

        // move node under templates collection
        if (newParent.id == 'templates') {
            if (oldParent.id == 'trash') {
                this.restoreTemplateNode(node);
                return true;
            }

            if (tree.getNodeById('content').contains(node)) {
                this.newTemplateFromContent(node);
                return false;
            }
        }

        // move node to trash
        if (newParent.id == 'trash') {
            if (oldParent.id == 'templates') {
                return this.removeTemplateNode(node);
            }

            if (tree.getNodeById('content').contains(node)) {
                return this.removeContentNode(node);
            }
        }

        if (contentNode) {

            // move node out from trash
            if ((oldParent.id == 'trash' || oldParent.id == 'templates')
                && (contentNode === newParent || contentNode.contains(newParent)))
            {
                if (index == 0) {
                    if (newParent.childNodes.length > 1) {
                        // insert above
                        var refNode = newParent.childNodes[0];
                        var point = 'above';
                    } else {
                        // append to parent node
                        var refNode = newParent;
                        var point = 'append';
                    }
                } else {
                    // insert below
                    var refNode = newParent.childNodes[index-1];
                    var point = 'below';
                }

                switch (oldParent.id) {
                    case 'trash':
                        this.restoreContentNode(node, refNode, point);
                        break;
                    case 'templates':
                        this.newContentFromTemplate(node, refNode, point);
                        break;
                }
            }

            // move content nodes
            if (contentNode) {
                if ((oldParent === contentNode || contentNode.contains(oldParent))
                    && (newParent === contentNode || contentNode.contains(newParent)))
                {
                    var bm = this.backendModel;

                    if (newParent.childNodes.length < 2) {
                        bm.moveNodeUnder(node.id, (newParent === contentNode ? '0' : newParent.id));
                    } else if (0 == index) {
                        bm.moveNodeAbove(node.id, newParent.firstChild.id);
                    } else {
                        bm.moveNodeBelow(node.id, newParent.childNodes[index-1].id);
                    }
                }
            }
        }
    },

    ajaxCb : function() {
        return {
            success : this.ajaxSuccess,
            failure : this.ajaxFailure,
            scope : this
        }
    },

    ajaxSuccess : function(o) {
        if (this.fireEvent('beforeajaxsuccess', o) !== false) {
            try {
                var response = Ext.util.JSON.decode(o.responseText);
            } catch(e) {
                var response = {result:false};
            }

            this.fireEvent('ajaxsuccess', response);
            this.unlock();

            if (response.status && !this.forceReload) {
                this.fireEvent('save', this);
            } else {
                this.reload();
            }
        }
    },

    ajaxFailure : function(o) {
        this.unlock();
        this.reload();
    },

    getTreeState : function() {
        var state = [];

        this.root.cascade(function(){
            if (!this.isRoot && this.isExpanded()) {
                // do not save state of current node as expanded
                // if one of the parent nodes is closed
                var node = this;
                while (node = node.parentNode) {
                    if (!node.isExpanded()) {
                        return;
                    }
                }

                // remove parent nodes from expanded array, because
                // they will be expanded automatically by expandPath()
                for (var i = 0, len = state.length; i < len; i++) {
                    if (0 == this.getPath().indexOf(state[i] + '.')) {
                        state.splice(i, 1);
                        break;
                    }
                }
                state.push(this.getPath());
            }
        });
        return state;
    },

    lock : function() {
        this.dropZone.isTarget = false;
        this.treeControl.disable();
        this.locked = true;
    },

    unlock : function() {
        this.dropZone.isTarget = true;
        this.treeControl.enable();
        this.treeControl.treeOnSelectionChange(this.selModel
            , this.selModel.getSelectedNode());
        this.locked = false;
    },

    isLocked : function() {
        return (this.locked === true);
    },

    restoreState : function() {
        var tree = Ext.state.Manager.get(this.stateId || (this.el.id + "-state"))
        if (tree instanceof Array) {
            // restore state
            for (var i = 0, len = tree.length; i < len; i++) {
                if (typeof tree[i] == "string") {
                    this.expandPath(tree[i]);
                }
            }
        } else {
            var content = this.getNodeById('content');
            if (content) {
                content.expand();
            }
        }

        return this;
    },

    saveState : function() {
        if (this.root.loaded) {
            Ext.state.Manager.set(this.stateId || this.el.id + "-state", this.getTreeState());
        }
    },

    reload : function() {
        this.forceReload = false;
        if (!this.isLocked() && this.root && this.root.isLoaded()) {
            this.treeControl.disableButtons();
            this.root.reload();
            this.root.renderChildren();
        }
    },

    getSelectedNode : function() {
        return this.getSelectionModel().getSelectedNode();
    },

    moveNodeUp : function(node) {
        if (node.previousSibling
            && node.previousSibling.attributes.sortable === true
            && node.attributes.sortable === true)
        {
            node.parentNode.insertBefore(node, node.previousSibling);
            node.select();
        }
    },

    moveNodeDown : function(node) {
        if (node.nextSibling
            && node.nextSibling.attributes.sortable === true
            && node.attributes.sortable === true)
        {
            node.parentNode.insertBefore(node.nextSibling, node);
            node.select();
        }
    },

    // detects what kind of node it is and calles appropriate method
    removeNode : function(node) {
        if (this.getNodeById('templates').contains(node)) {
            this.removeTemplateNode(node);
        } else if (this.getNodeById('content').contains(node)) {
            this.removeContentNode(node);
        } else if (this.getNodeById('trash').contains(node)) {
            this.removeTrashNode(node);
        }
    },

    removeTemplateNode : function(node) {
        if (confirm(this.languageData['remove_template'] + '"' + node.text + '"?')) {
            this.forceReload = true;
            node.parentNode.removeChild(node);
            this.backendModel.removeTemplateNode(node.id);
        } else {
            return false;
        }
    },

    removeTrashNode : function(node) {
        if (confirm(this.languageData['remove_from_trash'] + '"' + node.text + '"?')) {
            node.parentNode.removeChild(node);
            this.backendModel.removeTrashNode(node.id);
        } else {
            return false;
        }
    },

    removeContentNode : function(node) {
        if (confirm(this.languageData['remove_page'] + '"'+ node.text + '"?'
            + "\n" + this.languageData['all_children_will_be_removed']))
        {
            // force tree reloading after successful removing
            this.forceReload = true;
            node.parentNode.removeChild(node);
            this.backendModel.removeContentNode(node.id);
        } else {
            return false;
        }
    },

    restoreContentNode : function(node, refNode, point) {
        // force tree reloading after successfull node restoring
        if ('content' == refNode.id) {
            var refNodeId = 0;
        } else {
            var refNodeId = refNode.id;
        }
        this.forceReload = true;
        this.backendModel.restoreContentNode(node.id, refNodeId, point);
    },

    newContentFromTemplate : function(node, refNode, point) {
        if ('content' == refNode.id) {
            var refNodeId = 0;
        } else {
            var refNodeId = refNode.id;
        }
        this.forceReload = true;
        this.backendModel.newContentFromTemplate(node.id, refNodeId, point);
    },

    restoreTemplateNode : function(node) {
        this.forceReload = true;
        this.backendModel.restoreTemplateNode(node.id);
    },

    newTemplateFromContent: function(node) {
        this.forceReload = true;
        this.backendModel.newTemplateFromContent(node.id);
    }
});

/**
 *
 *
 */
Modera.tree.BackendModel = function(tree, url) {
    this.tree = tree;
    this.url = url;
    this.events = {
        "beforesave" : true
    };
}

Ext.extend(Modera.tree.BackendModel, Ext.util.Observable, {

    newTemplateFromContent : function(nodeId) {
        this.makeRequest({action:'template-from-content', node:nodeId});
    },

    newContentFromTemplate : function(templateNodeId, refNodeId, point) {
        this.makeRequest({action:'content-from-template', template_node:templateNodeId
            , ref_node:refNodeId, point:point});
    },

    restoreContentNode : function(trashNodeId, refNodeId, point) {
        this.makeRequest({action:'restore-content', trash_node:trashNodeId
            , ref_node:refNodeId, point:point});
    },

    restoreTemplateNode : function(trashNodeId) {
        this.makeRequest({action:'restore-template', trash_node:trashNodeId});
    },

    emptyTrash : function() {
        this.makeRequest({action:'empty-trash'});
    },

    removeTemplateNode : function(nodeId) {
        this.makeRequest({action:'move-to-trash', node:nodeId, node_type:'template'});
    },

    removeTrashNode : function(nodeId) {
        this.makeRequest({action:'remove', node:nodeId, node_type:'trash'});
    },

    removeContentNode : function(nodeId) {
        this.makeRequest({action:'move-to-trash', node:nodeId, node_type:'content'});
    },

    moveNodeDown : function(nodeId) {
        this.makeRequest({action:'move-down', node:nodeId});
    },

    moveNodeUp : function(nodeId) {
        this.makeRequest({action:'move-up', node:nodeId});
    },

    moveNodeUnder : function(nodeId, parentNodeId) {
        this.makeRequest({action:'move-under', node:nodeId, parentNode:parentNodeId});
    },

    moveNodeAbove : function(nodeId, refNodeId) {
        this.makeRequest({action:'move-above', node:nodeId, refNode:refNodeId});
    },

    moveNodeBelow : function(nodeId, refNodeId) {
        this.makeRequest({action:'move-below', node:nodeId, refNode:refNodeId});
    },

    makeRequest : function(params) {
        var data = [];
        for (var key in params) {
            data.push(encodeURIComponent(key) + '=' + params[key]);
        }

        this.tree.fireEvent('beforesave', this.tree);
        Ext.lib.Ajax.request('POST', this.url, this.tree.ajaxCb(), data.join('&'));
    }
});