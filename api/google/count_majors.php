<?php
require_once(__DIR__ . '/../../template/config.php');
require_once(__DIR__ . '/client/vendor/autoload.php');

function get_majors($count = false) {
//    $client = new Google_Client();
//    $client->setApplicationName(GOOGLE_CLIENT_APP_NAME);
//    $client->setDeveloperKey(GOOGLE_CLIENT_API_KEY);
//
//    $service = new Google_Service_Sheets($client);
//    $spreadsheet_id = GOOGLE_INTEREST_SPREADSHEET_ID;
//
//    $range = GOOGLE_MAJORS_SPREADSHEET_RANGE;
//    $response = $service->spreadsheets_values->get($spreadsheet_id, $range);
//    $values = $response->getValues();
//
//    $majors = array();
//
//    if (empty($values)) {
//        return 0;
//    } else {
//        foreach ($values as $row) {
//            $major = $row[5];
//
//            if (!isset($majors[$major])) {
//                    $majors[$major] = 1;
//            } else {
//                    $majors[$major]++;
//            }
//        }
//    }
//
//    if ($count) {
//        return count($majors);
//    }
//    return $majors;
    return 12;
}
