<?php

class IsicUserAgeChecker {
    private $db;
    /**
     * @var IsicDB_Users
     */
    private $isicDbUsers;

    public function __construct($db) {
        $this->db = $db;
        $this->isicDbUsers = IsicDB::factory('Users');
    }

    public function checkAndRemoveGrownUpChildren() {
        $usersWithChildren = $this->isicDbUsers->getUsersWithChildren();
        $rowCount = 0;
        foreach ($usersWithChildren as $parent) {
            $rowCount++;
            echo $rowCount . '. Parent: ' . $parent['user'] . ', UserCode: ' . $parent['user_code'];
            $childrenUserCodes = explode(',', $parent['children_list']);
            $removedChildrenUserCodes = array();
            foreach ($childrenUserCodes as $childUserCode) {
                $child = $this->isicDbUsers->getRecordByCode($childUserCode);
                if ($child) {
                    $age = IsicDate::getAgeInYears($child['birthday']);
                    if ($age >= CHILD_MAX_AGE) {
                        $removedChildrenUserCodes[] = $childUserCode;
                    }
                } else {
                    echo "\nCould not find child with usercode: " . $childUserCode . "\n";
                    continue;
                }
            }

            if (count($removedChildrenUserCodes) > 0) {
                echo '. Removed children: ' . implode(',', $removedChildrenUserCodes);
                $diff = implode(',', array_diff($childrenUserCodes, $removedChildrenUserCodes));
                $this->isicDbUsers->updateRecord($parent['user'], array('children_list' => $diff));
            }
            echo "\n";
        }
    }
}