<?php

$db_name = 'db_teste';
$db_host = 'localhost';
$db_user = 'postgres';
$db_pass = 'admin';

$driver = new PDO("pgsql:dbname=".$db_name.";host=".$db_host, $db_user , $db_pass );
