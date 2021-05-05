<?php


class MoveTables extends Migration
{

    function up()
    {
        $statement = DBManager::get()->prepare("
            SELECT * FROM fleximport_tables
        ");
        $statement->execute();
        $tables = $statement->fetchAll(PDO::FETCH_ASSOC);

        $update_table = DBManager::get()->prepare("
            UPDATE fleximport_tables
            SET tabledata = :tabledata
            WHERE table_id = :table_id
        ");

        foreach ($tables as $table) {
            if ($this->table_exists($table['name'])) {
                DBManager::get()->exec("
                    RENAME TABLE `" . addslashes($table['name']) . "` TO `fleximport_table_" . addslashes($table['table_id']) . "`
                ");
            }
            foreach ($tables as $t) {
                $tabledata = json_decode($t['tabledata'], true);
                $changed = false;
                if (isset($tabledata['fleximport_mysql_command']) && strpos($tabledata['fleximport_mysql_command'], $table['name']) !== false) {
                    $tabledata['fleximport_mysql_command'] = str_replace($table['name'], "fleximport_table_".$table['table_id'], $tabledata['fleximport_mysql_command']);
                    $changed = true;
                }
                if (isset($tabledata['sqlview']['select']) && strpos($tabledata['sqlview']['select'], $table['name']) !== false) {
                    $tabledata['sqlview']['select'] = str_replace($table['name'], "fleximport_table_".$table['table_id'], $tabledata['sqlview']['select']);
                    $changed = true;
                }
                if ($changed) {
                    $update_table->execute([
                        'table_id' => $t['table_id'],
                        'tabledata' => json_encode($tabledata)
                    ]);
                }
            }
        }
    }

    function down()
    {
        //downgrading this migration could cause bad problems if in the future
        //someone uses "seminare" as a table name for example.
    }

    protected function table_exists($tablename)
    {
        try {
            $statememt = DBManager::get()->prepare("
                SELECT 1
                FROM `" . addslashes($tablename) . "`
                LIMIT 1
            ");
            $statememt->execute();
            $exists = (bool) $statememt->fetch(PDO::FETCH_COLUMN, 0);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}
