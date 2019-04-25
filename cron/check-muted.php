<?php
if (php_sapi_name() === 'cli') {
	require_once('../template/top.php');
	require_once('groupme-funcs.php');

	$officers = get_all_members(GROUPME_OFFICER_CHANNEL_ID);

	foreach ($officers as $officer) {
		$q = $db->query('SELECT * FROM officers_groupme_muted WHERE uid = "' . $db->real_escape_string($officer->user_id) . '" LIMIT 1');
		if ($officer->muted) {
			if ($q->num_rows == 0) {
				$db->query('INSERT INTO officers_groupme_muted (uid) VALUES ("' . $db->real_escape_string($officer->user_id) . '")');
				post_message($officer->nickname . ' has muted this channel!', GROUPME_OFFICER_CHANNEL_ID);
			}
		} else {
			if ($q->num_rows == 1) {
				$db->query('DELETE FROM officers_groupme_muted WHERE uid = "' . $db->real_escape_string($officer->user_id) . '" LIMIT 1');
				post_message($officer->nickname . ' has un-muted this channel!', GROUPME_OFFICER_CHANNEL_ID);
			}
		}
	}
}