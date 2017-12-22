function FileUploader() {
    if (fileUploader) return fileUploader;

    var uploader = this;
    var isLoaded = false;

    document.write('<object id="fileUploaderSWF_IE" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0" width="0px" height="0px">' +
        '<param name="movie" value="img/file_uploader.swf" />' +
        '<embed ' +
        'type="application/x-shockwave-flash" ' +
        'pluginspage="http://www.macromedia.com/go/getflashplayer" ' +
        'src="img/file_uploader.swf" ' +
        'width="0px" ' +
        'height="0px" ' +
        'name="fileUploaderSWF" ' +
        'id="fileUploaderSWF" ' +
        'allowScriptAccess="sameDomain" ' +
        'FlashVars=""/>' +
        '</object>');

    if (navigator.appName.indexOf("Microsoft") != -1) {
        this.flash = window["fileUploaderSWF_IE"];
    } else {
        this.flash = document["fileUploaderSWF"];
    }

    this.browse = function(max_size) {
        this.flash.browse(max_size);
    }

    this.onAddFile = function(filename) {
        window.alert("AddFile: " + filename);
    }

    this.onCancel = function() {
    }

    this.onOpen = function(file) {
    }

    this.onTrace = function(message) {
        //window.alert("Trace: " + message);
    }

    this.deleteFile = function(id) {
        this.flash.deleteFile(id);
    }

    this.onDelete = function(id) {
        window.alert("Delete: " + id);
    }

    this.startUpload = function(url) {
        this.flash.startUpload(url);
    }

    this.onProgress = function(currentIndex, count, currentPos, totalPos) {
    }

    this.onComplete = function() {
    }

    this.onLoad = function() {
    }

    this.onError = function(type, file, error) {
    }
}

var fileUploader = new FileUploader();

function fileUploaderAddFile(filename) {
    fileUploader.onAddFile(filename);
}

function fileUploaderTrace(message) {
    fileUploader.onTrace(message);
}

function fileUploaderDelete(id) {
    fileUploader.onDelete(id);
}

function fileUploaderProgress(currentIndex, count, currentPos, totalPos) {
    fileUploader.onProgress(currentIndex, count, currentPos, totalPos);
}

function fileUploaderComplete(url) {
    fileUploader.onComplete(url);
}

function fileUploaderLoad() {
    fileUploader.isLoaded = true;
    fileUploader.onLoad();
}

function fileUploaderCancel() {
    fileUploader.onCancel();
}

function fileUploaderOpen(file) {
    fileUploader.onOpen(file);
}

function fileUploaderError(type, file, error) {
    fileUploader.onError(type, file, error);
}