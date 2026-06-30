<?php 
require_once __DIR__ .'/../connection.php';

session_start();
session_destroy();
header("Location: /index");
exit;