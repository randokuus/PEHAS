function FileUploader(butparent,max) {
    if (fileUploader) return fileUploader;

    var uploader = this;
    var isLoaded = false;
    this.movieId = "fileUploaderSWF";
    this.moviePath = "img/file_uploader.swf?rand="+new Date().getTime();
    var label = butparent.innerHTML;
    butparent.innerHTML = '';

    // Append the container and load the flash
    var tempParent = document.createElement("div");
    tempParent.innerHTML = ['<object type="application/x-shockwave-flash" id="',this.movieId ,'" ',
        'width="91px" height="20px" data="',this.moviePath,'" style="position:absolute">',
        '<param name="movie" value="',this.moviePath,'" />',
        '<param name="flashVars" value="maxUpload=',encodeURIComponent(max),'&buttonLabel=',encodeURIComponent(label),'" />',
        '<param name="allowScriptAccess" value="always" />',
        '<param name="quality" value="high" />',
        '<param name="menu" value="false" />',
        '</object>'].join("");

    butparent.appendChild(tempParent);

    this.browse = function(max_size) {
        this.getFlash().browse(max_size);
    }


    this.getFlash = function(){

        if(this.flash){
           return this.flash;
        }

        this.flash = document.getElementById(this.movieId);
        window[this.movieId] = this.flash;

        return this.flash
    }

    this.onAddFile = function(filename) {
        //window.alert("AddFile: " + filename);
    }

    this.onCancel = function() {
    }

    this.onOpen = function(file) {
    }

    this.onTrace = function(message) {
        //window.alert("Trace: " + message);
    }

    this.deleteFile = function(id) {
        this.getFlash().deleteFile(id);
    }

    this.onDelete = function(id) {
        //window.alert("Delete: " + id);
    }

    this.startUpload = function(url) {
        this.getFlash().startUpload(url);
    }

    this.onProgress = function(currentIndex, count, currentPos, totalPos) {
    }

    this.onComplete = function() {
    }

    this.onLoad = function() {
        this.isLoaded = true;
        this.getFlash();
    }

    this.onError = function(type, file, error) {
    }
}

FileUploader.prototype.destroy =function(){

        try {


            try {
                this.getFlash();
            } catch (ex) {
            }

            if (this.flash != undefined && this.flash.parentNode != undefined && typeof this.flash.parentNode.removeChild === "function") {
                var container = this.flash.parentNode;
                if (container != undefined) {
                    container.removeChild(this.flash);
                    if (container.parentNode != undefined && typeof container.parentNode.removeChild === "function") {
                        container.parentNode.removeChild(container);
                    }
                }
            }


            this.flash = null;
            delete this.flash;
            delete window[this.movieId];
            return true;
        } catch (ex1) {
            return false;
        }
}