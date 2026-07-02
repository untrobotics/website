<?php
// SAMPLE / reference only. The real twilio/phone-numbers-config.php is now
// committed and reads officer numbers from the PHONE_NUMBERS_JSON environment
// secret — set that in the k8s web-secrets (prod). It is a JSON object of
// role => E.164 number; keys MUST match the PHONE_NUMBERS['...'] references in
// twilio/process-incoming-call.php:
//
//   PHONE_NUMBERS_JSON={"President":"+15555550001","VicePresident":"+15555550002","FinancialDirector":"+15555550003","DirectorOfOperations":"+15555550004"}
