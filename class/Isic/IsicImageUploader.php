<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/FileUploader.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicError.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/MobileDetect/Mobile_Detect.php");

class IsicImageUploader {
    /**
     * image sizes
     *
     * @var string
     * @access protected
     */
    const IMAGE_SIZE = '307x372';
    const IMAGE_SIZE_X = '307';
    const IMAGE_SIZE_Y = '372';

    /**
     * Image size - thumbnail
     *
     * @var string
     * @access protected
     */

    const IMAGE_SIZE_THUMB = '83x100';

    /**
     * Folder for application pictures
     *
     * @var string
     * @access protected
     */
    const A_PIC_FOLDER = "/appl";

    /**
     * Tmp-folder for application pictures
     *
     * @var string
     * @access protected
     */
    const A_PIC_FOLDER_TMP = "/appl_tmp";

    /**
     * Picture prefix for application pics
     *
     * @var string
     * @access protected
     */
    const A_PIC_PREFIX = "APPL";

    /**
     * Folder for user pictures
     *
     * @var string
     * @access protected
     */
    const U_PIC_FOLDER = "/user";

    /**
     * Tmp-folder for user pictures
     *
     * @var string
     * @access protected
     */
    const U_PIC_FOLDER_TMP = "/user_tmp";

    /**
     * Picture prefix for user pics
     *
     * @var string
     * @access protected
     */
    const U_PIC_PREFIX = "USER";

    protected $picType = false;
    protected $picPrefix = false;
    protected $picFolder = false;
    protected $picFolderTmp = false;

    protected $error = false;
    protected $vars = false;
    protected $picResizeRequired = false;
    protected $picUrlTmp = false;
    protected $picFilename = false;
    protected $picWidth = 0;
    protected $picHeight = 0;

    public function __construct($picType = '') {
        $this->setPicType($picType);
        $this->error = new IsicError();
        $this->vars = $_POST;
    }

    /**
     * @param $picHeight the $picHeight to set
     */
    public function setPicHeight($picHeight) {
        $this->picHeight = $picHeight;
    }

    /**
     * @param $picWidth the $picWidth to set
     */
    public function setPicWidth($picWidth) {
        $this->picWidth = $picWidth;
    }

    /**
     * @return the $picHeight
     */
    public function getPicHeight() {
        return $this->picHeight;
    }

    /**
     * @return the $picWidth
     */
    public function getPicWidth() {
        return $this->picWidth;
    }


    /**
     * @param $picType the $picType to set
     */
    public function setPicType($picType) {
        $this->picType = $picType;
        switch ($this->picType) {
            case "application";
                $this->picPrefix = self::A_PIC_PREFIX;
                $this->picFolder = self::A_PIC_FOLDER;
                $this->picFolderTmp = self::A_PIC_FOLDER_TMP;
            break;
            case "user":
                $this->picPrefix = self::U_PIC_PREFIX;
                $this->picFolder = self::U_PIC_FOLDER;
                $this->picFolderTmp = self::U_PIC_FOLDER_TMP;
            break;
        }
    }

    /**
     * @return the $picType
     */
    public function getPicType() {
        return $this->picType;
    }

    public function getAspectRatio() {
        return self::IMAGE_SIZE_X / self::IMAGE_SIZE_Y;
    }

    /**
     * Handles uploaded image processing / resizing
     *
     * @param int $record_id id of a record (user, application, etc.)
     * @return array with errors (if any) and picture filename
    */
    public function handlePictureUpload($recordId = 0) {
        $this->getUploadedImage();
        $this->resizeAndCopyImage($recordId);

        $returnValue = array(
            "pic_vars" => $this->vars["pic"],
            "pic_filename" => $this->picFilename,
            "pic_resize_required" => $this->picResizeRequired,
            "tmp_pic" => $this->picUrlTmp,
            "width" => $this->getPicWidth(),
            "height" => $this->getPicHeight(),
            "error" => $this->error->isError(),
            "error_pic" => $this->error->isError(),
            "error_pic_save" => $this->error->get('save'),
            "error_pic_size" => $this->error->get('size'),
            "error_pic_resize" => $this->error->get('resize'),
            "error_pic_format" => $this->error->get('format'),
        );
        return $returnValue;
    }

    private function getUploadedImage() {
        if (!$_FILES['pic']['tmp_name'] || !$_FILES['pic']['size']) {
            return;
        }

        $picPathAndUrl = $this->getPicPathAndUrl();
        if (!$picPathAndUrl) {
            return;
        }
        $picPath = $picPathAndUrl['path'];
        $picUrl = $picPathAndUrl['url'];

        // trying to convert image into jpg no matter what was the extension of the file
        if ($this->isConvertWithErrors(IMAGE_CONVERT . " $picPath $picPath")) {
            $this->error->add('format');
            return;
        }

        $pic_size = $this->getImageSize($picPath);
        if (!$pic_size) {
            $this->error->add('size');
            return;
        }

        if (image_type_to_mime_type($pic_size[2]) != "image/jpeg") {
            $this->error->add('format');
            return;
        }

        if ($this->isPicResizeRequired($pic_size)) {
            if ($this->isMobileOrTablet()) {
                $this->vars['pic_name'] = $this->picFilename;
                $this->vars['pic_resize'] = 1;
            } else {
                $this->picResizeRequired = true;
                $this->picUrlTmp = $picUrl;
            }
        } else {
            $this->error->add('size');
        }
    }

    public function getImageSize($picPath) {
        $pic_size = getimagesize($picPath);
        if (is_array($pic_size)) {
            $this->setPicWidth($pic_size[0]);
            $this->setPicHeight($pic_size[1]);
            return $pic_size;
        }
        return false;
    }

    private function getPicPathAndUrl() {
        $fileExtension = $this->getFileNameExtension($_FILES['pic']['name']);

        // if uploaded file type is valid process with saveing this photo.
        if (!in_array($fileExtension, array('jpg'))) {
            $this->error->add('format');
            return false;
        }

        // create destination path string.
        $this->picFilename = md5(rand(0, time()));
        $destinationPath = Filenames::constructPath($this->picFilename, $fileExtension, SITE_PATH . '/' . $GLOBALS["directory"]["upload"] . $this->picFolderTmp);

        // process with pic saving.
        $file_uploader = new FileUploader();
        $picPath = $file_uploader->processUploadedFile($_FILES['pic']['tmp_name'], $destinationPath, 'replace', false);
        if ($picPath === false) {
            $this->error->add('save');
            return false;
        }
        $picUrl = Filenames::constructPath($this->picFilename, $fileExtension, SITE_URL . '/' . $GLOBALS["directory"]["upload"] . $this->picFolderTmp);
        return array('path' => $picPath, 'url' => $picUrl);
    }

    private function getFileNameExtension($fileName) {
        $file_info = Filenames::pathinfo($fileName);
        $file_info['extension'] = strtolower($file_info['extension']);
        if ($file_info['extension'] == 'jpeg') {
            $file_info['extension'] = 'jpg';
        }
        return $file_info['extension'];
    }

    private function isPicResizeRequired($pic_size) {
        return ($pic_size[0] >= self::IMAGE_SIZE_X && $pic_size[1] >= self::IMAGE_SIZE_Y);
    }

    private function isConvertWithErrors($command) {
        exec($command, $_dummy, $return_val);
        return $return_val;
    }

    private function resizeAndCopyImage($recordId) {
        if ($this->error->isError() || $this->picResizeRequired || !$this->vars["pic_name"]) {
            return;
        }

        if ($recordId) {
            $this->picFilename = $this->picPrefix . str_pad($recordId, 10, '0', STR_PAD_LEFT);
        } else {
            $this->picFilename = $this->vars["pic_name"];
        }
        $this->vars["pic"] = Filenames::constructPath($this->picFilename, 'jpg', "/" . $GLOBALS["directory"]["upload"] . $this->picFolder);

        if ($this->vars["pic_resize"]) {
            $t_pic_filename = SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . $this->picFolderTmp . '/' . $this->vars["pic_name"];
            $t_pic_filename_orig = $t_pic_filename . "_orig.jpg";
            $t_pic_filename_thumb = $t_pic_filename . "_thumb.jpg";
            $t_pic_filename .= ".jpg";

            if (file_exists($t_pic_filename)) {
                // creating copy of the original picture before starting resizing
                @copy($t_pic_filename, $t_pic_filename_orig);
                $this->resizeImage($t_pic_filename, $t_pic_filename_thumb);

                if (!$this->error->isError()) {
                    @copy($t_pic_filename, SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . $this->picFolder . '/' . $this->picFilename . '.jpg');
                    @copy($t_pic_filename_thumb, SITE_PATH . "/" . $GLOBALS["directory"]["upload"] . $this->picFolder . '/' . $this->picFilename . '_thumb.jpg');
                    @unlink($t_pic_filename);
                    @unlink($t_pic_filename_thumb);
                } else {
                    $this->vars['pic'] = '';
                }
                // and after all is done moving original pic back to it's original name and removing temp-file
                @copy($t_pic_filename_orig, $t_pic_filename);
                @unlink($t_pic_filename_orig);
            }
        }
    }

    private function resizeImage($t_pic_filename, $t_pic_filename_thumb) {
        $pic_size = getimagesize($t_pic_filename);
        if (!is_array($pic_size)) {
            $this->error->add('size');
            return;
        }

        if ($this->isMobileOrTablet()) {
            $this->resizeImageMobile($t_pic_filename, $t_pic_filename_thumb, $pic_size);
        } else {
            $this->resizeImageRegular($t_pic_filename, $t_pic_filename_thumb, $pic_size);
        }
    }

    /**
     * @param $t_pic_filename
     * @param $t_pic_filename_thumb
     * @param $pic_size
     */
    private function resizeImageRegular($t_pic_filename, $t_pic_filename_thumb, $pic_size)
    {
        $t_x = $pic_size[0];
        $t_y = $pic_size[1];

        $ratio = $t_x / self::IMAGE_SIZE_X;
        $crop = array();
        $crop["x1"] = round($this->vars["x1"] * $ratio);
        $crop["x2"] = round($this->vars["x2"] * $ratio);
        $crop["y1"] = round($this->vars["y1"] * $ratio);
        $crop["y2"] = round($this->vars["y2"] * $ratio);

        // crop
        $command = IMAGE_CONVERT . " -crop '" . ($crop["x2"] - $crop["x1"]) . "x" . ($crop["y2"] - $crop["y1"]) . "+" .
            $crop["x1"] . "+" . $crop["y1"] . "' $t_pic_filename $t_pic_filename";
        $this->error->add('resize', $this->isConvertWithErrors($command));
        // resize
        $command = IMAGE_CONVERT . " -resize '" . self::IMAGE_SIZE . "' $t_pic_filename $t_pic_filename";
        $this->error->add('resize', $this->isConvertWithErrors($command));
        // creating a thumbnail image
        $command = IMAGE_CONVERT . " -resize '" . self::IMAGE_SIZE_THUMB . "' $t_pic_filename $t_pic_filename_thumb";
        $this->error->add('resize', $this->isConvertWithErrors($command));
    }

    /**
     * @param $t_pic_filename
     * @param $t_pic_filename_thumb
     * @param $pic_size
     */
    private function resizeImageMobile($t_pic_filename, $t_pic_filename_thumb, $pic_size)
    {
        $command = IMAGE_CONVERT . " -auto-orient {$t_pic_filename} {$t_pic_filename}";
        $this->error->add('resize', $this->isConvertWithErrors($command));
        $command = IMAGE_CONVERT . " -resize \" ". self::IMAGE_SIZE . "^\" -gravity center -extent " . self::IMAGE_SIZE
            . " {$t_pic_filename} {$t_pic_filename}";
        $this->error->add('resize', $this->isConvertWithErrors($command));
        $command = IMAGE_CONVERT . " -geometry \"" . self::IMAGE_SIZE_THUMB . ">\" {$t_pic_filename} {$t_pic_filename_thumb}";
        $this->error->add('resize', $this->isConvertWithErrors($command));
    }

    public function getMaxCoordinates($pictureData) {
        $coordinates = array();
        $sizeRatio = $pictureData['width'] / self::IMAGE_SIZE_X;
        $coordinates['minWidth'] = self::IMAGE_SIZE_X / $sizeRatio;
        $coordinates['minHeight'] = self::IMAGE_SIZE_Y / $sizeRatio;

        $realAR = $pictureData['width'] / $pictureData['height'];
        if ($realAR > self::getAspectRatio()) { // landscape
            $resizeRatio = $pictureData['height'] / self::IMAGE_SIZE_Y;
            $coordinates['x1'] = round(($pictureData['width'] - $resizeRatio * self::IMAGE_SIZE_X) / $sizeRatio / 2);
            $coordinates['x2'] = $coordinates['x1'] + self::IMAGE_SIZE_X;
            $coordinates['y1'] = 1;
            $coordinates['y2'] = self::IMAGE_SIZE_Y;
        } else { // portrait
            $coordinates['x1'] = 1;
            $coordinates['x2'] = self::IMAGE_SIZE_X;
            $resizeRatio = $pictureData['width'] / self::IMAGE_SIZE_X;
            $coordinates['y1'] = round(($pictureData['height'] - $resizeRatio * self::IMAGE_SIZE_Y) / $sizeRatio / 2);
            $coordinates['y2'] = $coordinates['y1'] + self::IMAGE_SIZE_Y;
        }
        return $coordinates;
    }

    public static function isMobileOrTablet() {
        $mobile = new Mobile_Detect();
        return $mobile->isMobile() || $mobile->isTablet();
    }
}
