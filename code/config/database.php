<?php

//return [
//    'dsn' => 'mysql:host=127.0.0.1;dbname=app_db',
//    'username' => 'db_user',
//    'password' => 'db_user_pass',
//];

return [
    'dsn' => 'mysql:host='.$_SERVER["MYSQL_HOST"].';dbname='.$_SERVER["MYSQL_DATABASE"],
    'username' => $_SERVER["MYSQL_USER"],
    'password' => $_SERVER["MYSQL_PASSWORD"],
];
