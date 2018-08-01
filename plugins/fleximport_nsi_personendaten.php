<?php


class fleximport_nsi_personendaten extends FleximportPlugin
{
    private function getUserIdByDatafield($dbConnection = null, $fieldName = 'fp_idnr', $column = '')
    {
        if(!$dbConnection or !$fieldName or !$column) {
            return false;
        }
        
        $userId = $dbConnection->query(
            "SELECT auth_user_md5.user_id FROM auth_user_md5 "
            . "INNER JOIN datafields_entries "
            . "ON auth_user_md5.user_id = datafields_entries.range_id "
            . "INNER JOIN datafields "
            . "ON datafields.datafield_id = datafields_entries.datafield_id "
            . "WHERE "
            . "datafields_entries.content = " . $dbConnection->quote($column) . " "
            . "AND datafields.name = " . $dbConnection->quote($fieldName) . " AND datafields.object_type = 'user';"
            )->fetch(PDO::FETCH_BOTH);
        if(!$userId) {
            return false;
        } else {
            return $userId[0];
        }
    }
    
    
    public function fieldsToBeMapped()
    {
        return array(
            //'user_id',
            'username',
            'geschlecht'
        );
    }
    
    public function mapField($field, $line)
    {
        if($field === 'user_id') {
            $db = DBManager::get();
            if($line['fp_idnr']) {
                //get user-ID by checking fp_idnr:
                $userId = $this->getUserIdByDatafield($db, 'fp_idnr', $line['fp_idnr']);
                if(!$userId) {
                    //datafield not found: check for safo_key:
                    $userId = $this->getUserIdByDatafield($db, 'safo_key', $line['safo_key']);
                    if(!$userId) {
                        return false;
                    } else {
                        return $userId;
                    }
                } else {
                    return $userId;
                }
            } elseif ($line['safo_key']) {
                $userId = $this->getUserIdByDatafield($db, 'safo_key', $line['safo_key']);
                if(!$userId) {
                    return false;
                } else {
                    return $userId;
                }
            }
            
        } elseif($field === 'username') {
            $db = DBManager::get();
            $userName = strtolower(
                str_replace(" ", ".", FleximportTable::reduceDiakritikaFromIso88591($line['vorname']))
                . "." .
                str_replace(" ", "", FleximportTable::reduceDiakritikaFromIso88591($line['nachname']))
            );


            $user_id = $this->getUserIdByDatafield($db, 'fp_idnr', $line['fp_idnr']);
                
            if(!$user_id) {
                //datafield not found: check for safo key:
                $userId = $this->getUserIdByDatafield($db, 'safo_key', $line['safo_key']);
            }
            
            $pureUsername = $userName;
            if(!$user_id) {
                
                $i = 1;
                while ($db->query(
                    "SELECT 1 FROM auth_user_md5 WHERE username = ".$db->quote($userName)
                    /*
                    "SELECT 1 FROM auth_user_md5 "
                    . "LEFT JOIN datafields_entries "
                    . "ON auth_user_md5.user_id = datafields_entries.range_id "
                    . "LEFT JOIN datafields "
                    . "ON datafields.datafield_id = datafields_entries.datafield_id "
                    . "WHERE auth_user_md5.username = ". $db->quote($userName) . " "
                    . "AND (datafields_entries.content IS NULL "
                    . "OR datafields_entries.content <> " . $db->quote($line['fp_idnr']) . ") "
                    . "AND datafields.name = 'fp_idnr' AND datafields.object_type = 'user';"
                    */
                )->fetch()) {
                    $i++;
                    $userName = $pureUsername . $i;
                }
                //todo: add number for user name, if user name is already taken
                //(see original code in NSI_PersonenTable)
                
                return preg_replace('/[^\.a-zA-Z0-9_@\-]/', '', $userName);
            } else {
                //user-ID was found: return the existing username from the database:
                $userName = $db->query(
                    "SELECT username FROM auth_user_md5 WHERE user_id = " . $db->quote($user_id)
                    )->fetch(PDO::FETCH_BOTH);
                return $userName[0];
            }
            
        } elseif($field === 'geschlecht') {
            if(strtolower($line['geschlecht']) == 'm') {
                return 1;
            } elseif(strtolower($line['geschlecht']) == 'w') {
                return 2;
            } else {
                return 0; //undefined
            }
        }
        
        return false;
    }
}

