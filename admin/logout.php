<?php
require_once '../includes/config.php';
startSession();
session_destroy();
header('Location: login.php');
exit;
