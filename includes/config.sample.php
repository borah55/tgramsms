<?php
/**
 * DogeMine - Configuration File
 * Copy this file to config.php and edit your database credentials.
 */

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'dogemine');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Site
define('SITE_URL', 'http://localhost/dogemine'); // No trailing slash
define('TIMEZONE', 'UTC');

// Security
define('APP_KEY',          'change-me-to-a-long-random-string-32+chars');
define('SESSION_NAME',     'DOGEMINE_SID');
define('REMEMBER_COOKIE',  'dogemine_remember');
define('CSRF_TOKEN_NAME',  '_csrf');

// Anti-bruteforce
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_MINUTES',    15);

// Misc
define('DEBUG_MODE', false);
