<?php
require_once(SITE_PATH . "/" . $GLOBALS["directory"]["object"] . "/Isic/IsicDate.php");

class IsicUserStatusChecker {

    private $db;
    private $isicDbCards;
    private $limitDate;
    private $limitDays = 30;
    private $isicDbUserStatus;
    private $isicDbCardValidity;

    public function __construct($db) {
        $this->db = $db;
        $this->isicDbCards = IsicDB::factory('Cards');
        $this->isicDbUserStatus = IsicDB::factory('UserStatuses');
        $this->isicDbCardValidity = IsicDB::factory('CardValidities');
        $settings = IsicDB::factory('GlobalSettings');
        $this->limitDays = intval($settings->getRecord('manual_status_limit_days'));
        $this->limitDate = $this->getLimitDate();
    }

    private function getLimitDate() {
        $curTime = time();
        $curMon = date('n', $curTime);
        $curDay = date('j', $curTime) - $this->limitDays;
        $curYear = date('Y', $curTime);
        return date('Y-m-d', mktime(0, 0, 0, $curMon, $curDay, $curYear));
    }

    public function checkActiveManualStatuses($addType = 1) {
        $result = $this->getActiveStatuses($addType);
        $total = 0;
        $deact = 0;
        while ($row = $result->fetch_assoc()) {
            $total++;
            $activeStatus = $this->getActiveAutomaticStatus($row);
            if ($activeStatus || !$this->isCardDepndingOnStatusExists($row)) {
                echo  $total . '. ' . $row['id'] . ': U: ' . $row['user_id'] . ', Sc: ' . $row['school_id'] . ', St: ' . $row['status_id'];
                echo ': DEACT';
                echo "\n";
                $deact++;
                $this->isicDbUserStatus->deActivate($row['id']);

                if ($activeStatus) {
                    $update = array(
                        'faculty' => $row['faculty'],
                        'class' => $row['class'],
                        'course' => $row['course'],
                        'position' => $row['position'],
                        'structure_unit' => $row['structure_unit'],
                    );
                    $this->isicDbUserStatus->updateRecord($activeStatus['id'], $update);
                    $this->isicDbCardValidity->insertOrUpdateRecordByUserStatus($this->isicDbUserStatus->getRecord($activeStatus['id']));
                }
            }
        }

        echo  'total: ' . $total . ', deact count: ' . $deact . "\n";
    }

    private function isCardDepndingOnStatusExists($status) {
        if ($status['addtime'] > $this->limitDate) {
            return true;
        }
        $latestCards = $this->getLatestCard($this->getCardsForStatus($status));
        if (!$latestCards['activated']['moddate'] && $latestCards['deactivated']['moddate'] <= $this->limitDate) {
            return false;
        }
        return true;
    }

    private function shouldDeactivateStatus($status) {
        $deactivateManualStatus = false;
        $validity = $this->getCardValiditiesForStatus($status);
        $appl = $this->getApplicationForStatus($status);
        $aStatus = $this->getActiveAutomaticStatus($status);
        // there is similar ehis-status existing
        if ($aStatus) {
            $aValidity = $this->getCardValiditiesForStatus($aStatus);
            $cardDeactivated = $this->isCardDeactivated($this->getCardByValidity($validity));
            $aCardDeactivated = $this->isCardDeactivated($this->getCardByValidity($aValidity));
            echo $status['id'] . ': U: ' . $status['user_id'] . ', Sc: ' . $status['school_id'] . ', St: ' . $status['status_id'];
            $this->showValidityInfo($validity, $aValidity);
            $this->showCardInfo($cardDeactivated, $aCardDeactivated);
            echo "\n";
        } else {
            // validity exists for the given manual status
            if ($validity && $appl) {
                // should do nothing
            } else {
                $deactivateManualStatus = true;
            }
        }
        return $deactivateManualStatus;
    }

    private function showValidityInfo($validity, $aValidity) {
        echo '; validity: ';
        if ($validity && $aValidity) {
            echo ' both: ' . ($this->areValiditiesEqual($validity, $aValidity) ? 'equal' : 'diff');
        } else if ($validity) {
            echo '1';
        } else if ($aValidity) {
            echo '2';
        } else {
            echo 'none';
        }
    }

    private function showCardInfo($card, $aCard) {
        echo '; cards: ';
        if ($card && $aCard) {
            echo ' both ';
        } else if ($card) {
            echo '1';
        } else if ($aCard) {
            echo '2';
        } else {
            echo 'none';
        }
    }

    private function areValiditiesEqual($val1, $val2) {
        echo  'Val: ' . $val1['id'] . ' vs. ' . $val2['id'] . ', school: ' . ($val1['school_id'] . ' vs. ' . $val2['school_id']) . ', card: ' . ($val1['card_id'] . ' vs. ' . $val2['card_id']);
        return $val1['school_id'] == $val2['school_id'] && $val1['card_id'] == $val2['card_id'];
    }

    private function getCardByValidity($validity) {
        $sql = '
        SELECT
            module_isic_card.*
        FROM
            module_isic_card
        WHERE
            module_isic_card.id = !
        LIMIT 1
        ';

        $r = $this->db->query($sql, $validity['card_id']);
        return $r->fetch_assoc();
    }

    private function isCardDeactivated($card) {
        return $card['state_id'] == 4;
    }

    public function getActiveStatuses($addType = 1) {
        $sql = '
       SELECT
            module_user_status_user.*,
            module_user_users.user_code
        FROM
            module_user_status_user,
            module_user_users
        WHERE
            module_user_status_user.active = 1 AND
            module_user_status_user.addtype = ! AND
            module_user_status_user.user_id = module_user_users.user
        ORDER BY
            module_user_status_user.id ASC
        LIMIT 100000
        ';
        $r = $this->db->query($sql, $addType);
        return $r;
    }

    public function getActiveAutomaticStatus($status) {
        $sql = '
        SELECT
            module_user_status_user.*
        FROM
            module_user_status_user
        WHERE
            module_user_status_user.active = 1 AND
            module_user_status_user.addtype = 2 AND
            module_user_status_user.status_id = ! AND
            module_user_status_user.user_id = ! AND
            module_user_status_user.school_id = !
        ORDER BY
            module_user_status_user.id DESC
        LIMIT 1
        ';

        $r = $this->db->query($sql, $status['status_id'], $status['user_id'], $status['school_id']);
        return $r->fetch_assoc();
    }

    public function getCardValiditiesForStatus($status) {
        $sql = '
        SELECT
            module_isic_card_validities.*
        FROM
            module_isic_card_validities
        WHERE
            module_isic_card_validities.user_status_id = !
        ORDER BY
            module_isic_card_validities.id DESC
        LIMIT 1
        ';
        $r = $this->db->query($sql, $status['id']);
        return $r->fetch_assoc();
    }

    private function getApplicationForStatus($status) {
        $sql = '
        SELECT
            module_isic_application.*
        FROM
            module_isic_application,
            module_user_users
        WHERE
            module_isic_application.person_number = module_user_users.user_code AND
            module_user_users.user = !
        ORDER BY
            module_isic_application.id DESC
        LIMIT 1
        ';
        $r = $this->db->query($sql, $status['user_id']);
        return $r->fetch_assoc();
    }

    private function getCardsForStatus($status) {
        return $this->isicDbCards->findRecordsByStatusPersonNumber($status['status_id'], $status['user_code']);
    }

    private function getLatestCard($cards) {
        $latestCards = array(
            'activated' => array('record' => null, 'moddate' => null),
            'deactivated' => array('record' => null, 'moddate' => null)
        );
        $state = '';
        foreach ($cards as $card) {
            if ($card['state_id'] == 4) {
                $state = 'deactivated';
            } else {
                $state = 'activated';
            }
            if ($card['moddate'] > $latestCards[$state]['moddate']) {
                $latestCards[$state]['moddate'] = IsicDate::getAsDate($card['moddate']);
                $latestCards[$state]['record'] = $card;
            }
        }

        return $latestCards;
    }

    public function checkActiveAgeRestrictedStatuses() {
        $result = $this->getActiveStatusesForAgeRestrictedCards();
        $total = 0;
        $deact = 0;
        while ($row = $result->fetch_assoc()) {
            $total++;
            echo  $total . '. ' . $row['id'] . ': U: ' . $row['user_id'] . ', Sc: ' . $row['school_id'] . ', St: ' . $row['status_id'] . ', Age: ' . $row['age_in_years'];
            echo ': DEACT';
            echo "\n";
            $deact++;
            $this->isicDbUserStatus->deActivate($row['id']);
        }

        echo  'total: ' . $total . ', deact count: ' . $deact . "\n";
    }

    private function getActiveStatusesForAgeRestrictedCards() {
        $sql = '
            SELECT
                `usu`.*,
                (YEAR(CURDATE()) - YEAR(`u`.`birthday`) - (RIGHT(CURDATE(), 5) < RIGHT(`u`.`birthday`, 5))) AS `age_in_years`
            FROM
                `module_isic_card_type` AS `ct`,
                `module_user_status_user` AS `usu`,
                `module_user_status` AS `us`,
                `module_user_users` AS `u`,
                `module_isic_card` AS `c`
            WHERE
                `ct`.`age_restricted` = 1 AND
                `ct`.`id` = `c`.`type_id` AND
                `c`.`person_number` = `u`.`user_code` AND
                `c`.`active` = 1 AND
                FIND_IN_SET(`ct`.`id`, `us`.`card_types`) AND
                `us`.`id` = `usu`.`status_id` AND
                `u`.`user` = `usu`.`user_id` AND
                `usu`.`active` = 1 AND
                (
                    (YEAR(CURDATE()) - YEAR(`u`.`birthday`) - (RIGHT(CURDATE(), 5) < RIGHT(`u`.`birthday`, 5))) <
                        `ct`.`age_lower_bound`
                    OR
                    (YEAR(CURDATE()) - YEAR(`u`.`birthday`) - (RIGHT(CURDATE(), 5) < RIGHT(`u`.`birthday`, 5))) >
                        `ct`.`age_upper_bound_deact`)
            GROUP BY
                `usu`.`id`
            LIMIT 100000
        ';
        $r = $this->db->query($sql);
        return $r;
    }
}