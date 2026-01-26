<?php
session_start();
$userid = $_SESSION['Member_ID'];
if (empty($_SESSION['Member_ID'])){
    header('Location: ../index.php');
    exit;
}
// echo '<pre>'; print_r($_SESSION); echo '</pre>';
