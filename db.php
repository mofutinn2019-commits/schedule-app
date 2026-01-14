<?php
$pdo = new PDO(
'mysql:host=localhost;dbname=schedule_app;charset=utf8',
'root','',
[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
?>