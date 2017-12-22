<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicCommon.php");
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/IsicDB.php");


class UserPic {
    private $db = false;
    const U_PIC_FOLDER = "/user";
    const IMAGE_SIZE_X = '307';
    const IMAGE_SIZE_Y = '372';
    const TARGET_FOLDER = '/user_wrong_size';
        
    public function __construct() {
        $this->db = &$GLOBALS['database'];
        $this->isicDbUsers = IsicDB::factory('Users');
    }

    public function showInvalidSizePicUsers() {
        $result = $this->getUsers();
        $counter = 0;
        $total = $result->num_rows();
        while ($data = $result->fetch_assoc()) {
            $sizeCorrect = $this->isPicSizeCorrect($data['pic']); 
            if ($sizeCorrect !== true) {
                echo (++$counter) . ' / ' . $total . ': ' . $data['user_code'] . ': ' . $data['pic'] . "\n";
                print_r($sizeCorrect);
                $this->removePic($data['pic']);
                $this->isicDbUsers->updateRecord($data['user'], array('pic' => ''));
                echo "\n====\n";    
            }
        }
        echo "Done ... \n";
    }
    
    private function getUsers() {
        $r = &$this->db->query('
            SELECT 
                `module_user_users`.`user`,
                `module_user_users`.`name_first`,
                `module_user_users`.`name_last`,
                `module_user_users`.`user_code`,
                `module_user_users`.`pic`
            FROM 
                `module_user_users` 
            WHERE 
                `module_user_users`.`pic` <> ?
            ORDER BY
                `module_user_users`.`user` DESC
            LIMIT 100000
            ', 
            ''
        );
        return $r;
    }
    
    private function isPicSizeCorrect($pic) {
        $filename = SITE_PATH . $pic;
        if (!file_exists($filename)) {
            return 'file does not exist';
        }
        $picSize = getimagesize($filename);
        if ($picSize && ($picSize[0] >= self::IMAGE_SIZE_X - 10 && $picSize[1] >= self::IMAGE_SIZE_Y - 10)) {
            return true;
        }
        return $picSize;
    }
    
    private function removePic($pic) {
        $filename = SITE_PATH . $pic;
        $filenameThumb = str_replace('.jpg', '_thumb.jpg', $filename);
        $targetFile = str_replace(self::U_PIC_FOLDER, self::TARGET_FOLDER, $filename);
        $targetFileThumb = str_replace(self::U_PIC_FOLDER, self::TARGET_FOLDER, $filenameThumb);
        $this->movePic($filename, $targetFile);
        $this->movePic($filenameThumb, $targetFileThumb);
    }
    
    private function movePic($src, $tar) {
        if (file_exists($src)) {
            @copy($src, $tar);
            unlink($src);
        }
    }
}
