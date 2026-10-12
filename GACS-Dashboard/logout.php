<?php
require __DIR__ . '/config/config.php';
$_SESSION = []; session_destroy();
header('Location: login.php');
