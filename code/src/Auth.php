<?php

namespace App;

class Auth{

    private ?Database $database = null;

    public function __constructor(Database $database){
        $this->database = $database;
    }

    public function regstarion(array $data): bool {
        if(empty($data['username'])){
            throw new AuthEx ('The username shold not be emty');
        }
        if(empty($data['email'])){
            throw new AuthEx ('The email shold not be emty');
        }
        if(empty($data['password'])){
            throw new AuthEx ('The password shold not be emty');
        }
        if($data['password'] !== $data['confirm_password']){
            throw new AuthEx ('Password and confirm paswword shold match');
        }

        $statment = $this->database->getConnection()->prepare('INSERT INTO user (email, password, username) VALUES (:email, :password, :username)');
        $statment->execute([
            'email' => $data['email'],
            'password' => $data['password'],
            'username' => password_hash($data['password'], PASSWORD_BCRYPT)
        ]);
        return true;
    }
}