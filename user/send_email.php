<?php
// user/send_email.php

/**
 * Simulates sending an email by writing it to email_log.txt in the project root.
 */
function send_email_alert($to, $subject, $body)
{
    // Project root is two levels up from user/ directory
    $logFile = __DIR__ . '/../email_log.txt';

    $timestamp = date('Y-m-d H:i:s');
    $entry = "================================================================\n";
    $entry .= "TIME:    $timestamp\n";
    $entry .= "TO:      $to\n";
    $entry .= "SUBJECT: $subject\n";
    $entry .= "BODY:\n$body\n";
    $entry .= "================================================================\n\n";

    // Append to file
    file_put_contents($logFile, $entry, FILE_APPEND);

    return true; // Always success for simulation
}
