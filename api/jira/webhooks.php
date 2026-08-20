<?php
/**
 * Jira automation webhook -> Discord. Jira POSTs here (with ?id=<issue key>) on
 * issue updates; when an issue moves to Done we announce the deploy in
 * #webmasters. Every nested property is read defensively: an unassigned issue
 * has a null assignee, and reading ->displayName on null raised a PHP warning on
 * every call that the error-log forwarder then spammed into #web-logs. (URW-195)
 */
require('../../template/top.php');
require('../discord/bots/admin.php');

$request = json_decode(file_get_contents('php://input'));

$issue  = isset($request->issue) ? $request->issue : null;
$fields = ($issue && isset($issue->fields)) ? $issue->fields : null;

// Only a status change on a real issue is actionable.
if (!$fields || !isset($request->changelog->items) || !is_array($request->changelog->items)) {
    http_response_code(200);
    die();
}

$ticket   = isset($_GET['id']) ? $_GET['id'] : (isset($issue->key) ? $issue->key : 'a ticket');
$summary  = isset($fields->summary) ? trim((string) $fields->summary) : '';
$assignee = (isset($fields->assignee->displayName) && $fields->assignee->displayName !== '')
    ? "`{$fields->assignee->displayName}`"
    : '_no assignee_';

$status = null;
foreach ($request->changelog->items as $item) {
    if (isset($item->field) && $item->field === 'status') {
        $status = isset($item->toString) ? $item->toString : null;
        break;
    }
}

if ($status === 'Done') {
    $title = $summary !== '' ? " — **{$summary}**" : '';
    AdminBot::send_message(
        "Ticket `{$ticket}`{$title} has been deployed to production. Assignee: {$assignee}. Woohoo! :tada:",
        755954745490800781
    );
}

http_response_code(200);
