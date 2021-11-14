<?php

return [
    'dsn' => 'mysql:host='.$_SERVER["MYSQL_HOST"].';dbname='.$_SERVER["MYSQL_DATABASE"],
    'username' => $_SERVER["MYSQL_USER"],
    'password' => $_SERVER["MYSQL_PASSWORD"],
];
