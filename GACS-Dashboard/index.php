<?php
require __DIR__ . '/config/config.php';
header('Location: ' . (empty($_SESSION['uid']) ? 'login.php' : 'dashboard.php'));
