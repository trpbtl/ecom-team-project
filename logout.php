<?php
require_once __DIR__ . '/core/core.php';

$_SESSION = [];
session_destroy();
session_start();
flash('success', 'You have logged out.');
redirect('index.php');
