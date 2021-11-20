<?php

namespace App;
use PDO;

class Auth{

    private PDO $database;
    private Session $session;

    public function __construct(PDO $database, Session $session)
    {
        $this->database = $database;
        $this->session =$session;
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
        /*** Check email*/
        $statment = $this->database->prepare('SELECT * FROM user WHERE email = :email');
        $statment->execute(['email' => $data['email']]);
        $user = $statment->fetch(); // Возвращает 1 строчку
        if(!empty($user)){throw new AuthEx('User with such email exist');}
        /*** Check usename */
        $statment = $this->database->prepare(
            'SELECT * FROM user WHERE username = :username'
        );
        $statment->execute([
            'username' => $data['username']
        ]);
        $user = $statment->fetch(); // Возвращает 1 строчку
        if(!empty($user)){
            throw new AuthEx('User with such usename exist');
        }
        /*** */

        /*** Add user*/
        $statment = $this->database->prepare('INSERT INTO user (email, password, username) VALUES (:email, :password, :username)');
        $statment->execute([
            'email' => $data['email'],
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT)
        ]);
        return true;
    }

    public function login(string $email, $password): bool {
        if(empty($email)){
            throw new AuthEx ('The email shold not be emty');
        }
        if(empty($password)){
            throw new AuthEx ('The password shold not be emty');
        }
        /*** Check email*/
        $statment = $this->database->prepare('SELECT * FROM user WHERE email = :email');
        $statment->execute(['email' => $email]);
        $user = $statment->fetch(); // Возвращает 1 строчку
        if(empty($user)){throw new AuthEx('No such user');}

        if(password_verify($password, $user['password'])){
            $this->session->setData('user',[
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email']
            ]);
            return true;
        }
        throw new AuthEx('Incorrect email or password');
    }
}