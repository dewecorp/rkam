<?php
require_once __DIR__.'/config/bootstrap.php';
Logger::log($pdo,'logout','auth');
Auth::logout();
header('Location: '.BASE_URL.'login');exit;
