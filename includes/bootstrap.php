<?php
/**
 * Single bootstrap file — included by every page.
 */
define('DOGEMINE', true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mining.php';

start_session();

if (basename($_SERVER['SCRIPT_NAME']) !== 'install.php' && !file_exists(__DIR__ . '/.installed')) {
    redirect('install.php');
}

maintenance_guard();
