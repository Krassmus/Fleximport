<?php


class WebhookChanges extends Migration {

    function up()
    {
        $new_job = array(
            'filename'    => 'public/plugins_packages/data-quest/Fleximport/webhook.cronjob.php',
            'class'       => 'FleximportWebhookJob',
            'priority'    => 'normal',
            'minute'      => '-1'
        );

        $query = "INSERT IGNORE INTO `cronjobs_tasks`
                    (`task_id`, `filename`, `class`, `active`)
                  VALUES (:task_id, :filename, :class, 1)";
        $task_statement = DBManager::get()->prepare($query);

        $query = "INSERT IGNORE INTO `cronjobs_schedules`
                    (`schedule_id`, `task_id`, `parameters`, `priority`,
                     `type`, `minute`, `mkdate`, `chdate`,
                     `last_result`)
                  VALUES (:schedule_id, :task_id, '[]', :priority, 'periodic',
                          :minute, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(),
                          NULL)";
        $schedule_statement = DBManager::get()->prepare($query);


        $task_id = md5(uniqid('task', true));

        $task_statement->execute(array(
            ':task_id'  => $task_id,
            ':filename' => $new_job['filename'],
            ':class'    => $new_job['class'],
        ));

        $schedule_id = md5(uniqid('schedule', true));
        $schedule_statement->execute(array(
            ':schedule_id' => $schedule_id,
            ':task_id'     => $task_id,
            ':priority'    => $new_job['priority'],
            ':minute'      => $new_job['minute'],
        ));

        DBManager::get()->exec("
	        ALTER TABLE `fleximport_tables`
            ADD `change_hash` varchar(64) NULL AFTER `synchronization`,
            ADD `webhook_urls` TEXT NULL AFTER `change_hash`;
	    ");
        DBManager::get()->exec("
	        ALTER TABLE `fleximport_processes`
            ADD `webhookable` tinyint(4) NOT NULL DEFAULT '0' AFTER `triggered_by_cronjob`;
	    ");
        SimpleORMap::expireTableScheme();
    }

    function down()
    {
        DBManager::get()->exec("
	        DELETE FROM `cronjobs_tasks`
            WHERE `class` = 'FleximportWebhookJob'
	    ");
        DBManager::get()->exec("
	        DELETE FROM `cronjobs_schedules`
            WHERE `task_id` NOT IN (SELECT `task_id` FROM `cronjobs_tasks`)
	    ");
        SimpleORMap::expireTableScheme();
    }
}
