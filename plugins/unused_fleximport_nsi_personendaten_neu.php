<?php

require_once 'vendor/email_message/blackhole_message.php';

class unused_fleximport_nsi_personendaten_neu extends FleximportPlugin
{
    /**
     * Just a callback do do some additional work before the plain import happens.
     * @param SimpleORMap $object : the not yet stored object.
     * @param array $line : the current dataline that is going to be processed
     * @param array $mappeddata : the mapped values
     */
    public function beforeUpdate(SimpleORMap $object, $line, $mappeddata)
    {
        $statement = DBManager::get()->prepare("
            SELECT auth_user_md5.user_id AS old_id, fpidnr_user.user_id AS new_id
            FROM fleximport_nsi_personendaten_neu
                LEFT JOIN auth_user_md5 ON (auth_user_md5.username = LOWER(fleximport_nsi_personendaten_neu.nutzer))
                LEFT JOIN datafields_entries ON (datafield_id = 'e157967d03627474365d7e1b8d54c662' AND content = fleximport_nsi_personendaten_neu.fp_idnr)
                INNER JOIN auth_user_md5 AS fpidnr_user ON (datafields_entries.range_id = fpidnr_user.user_id)
            WHERE fpidnr_user.user_id != auth_user_md5.user_id

        ");

        $statement->execute();

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {

            $old_id = $row['old_id'];
            $new_id = $row['new_id'];

            //check existing users
            if (User::exists($old_id) && User::exists($new_id)) {
                $identity = true;
                $details  = User::convert($old_id, $new_id, $identity);

                //delete old user
                //no messaging
                $dev_null       = new blackhole_message_class();
                $default_mailer = StudipMail::getDefaultTransporter();
                StudipMail::setDefaultTransporter($dev_null);

                //preparing delete
                $umanager = new UserManagement();
                $umanager->getFromDatabase($old_id);

                //delete
                $umanager->deleteUser();

                //reactivate messaging
                StudipMail::setDefaultTransporter($default_mailer);

            }
        }
    }

    /**
     * Returns a description of what this plugin is doing.
     * @return null\string
     */
    public function getDescription() {
        return "Konvertiert alte Nutzer, wenn sie noch in der Datenbank stecken.";
    }
}
