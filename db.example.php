<?php
/**
 * db.example.php  —  Copy this to db.php and fill in your credentials.
 *
 * NEVER commit db.php to version control (it is listed in .gitignore).
 *
 * Environment variable overrides (recommended for production):
 *   KKBANK_DB_HOST, KKBANK_DB_PORT, KKBANK_DB_NAME, KKBANK_DB_USER, KKBANK_DB_PASS
 */

$dbCfg = [
    'host'    => getenv('KKBANK_DB_HOST') ?: '127.0.0.1',
    'port'    => getenv('KKBANK_DB_PORT') ?: '3306',
    'name'    => getenv('KKBANK_DB_NAME') ?: 'kkbank_db',
    'user'    => getenv('KKBANK_DB_USER') ?: 'YOUR_DB_USER',   // ← change this
    'pass'    => getenv('KKBANK_DB_PASS') ?: 'YOUR_DB_PASS',   // ← change this
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];

// ── Copy the rest of db.php functions (db_connect, db_query, etc.) below ──
