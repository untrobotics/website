<?php
require('../../template/top.php');
require('../discord/bots/admin.php');

$json = file_get_contents('php://input');
$request = json_decode($json);

$id = $_GET['id'];

$ticket = $id;
$assignee = $request->issue->fields->assignee->displayName;
$status = null;
$previous_status = null;

// check if status was updated
$changed_fields = $request->changelog->items;
foreach ($changed_fields as $item) {
    if ($item->field == "status") {
        $status = $item->toString;
        $previous_status = $item->fromString;
        break;
    }
}

if ($status == null) {
    // not a status update
    die();
}

if ($status == "Done") {
    // yay!
    AdminBot::send_message(
        "Ticket `{$ticket}` has been deployed to production. Assignee: `{$assignee}`. Woohoo!",
        755954745490800781
    );
}