<?php
class Login_Form extends Form_ModelAbstract {

    public $user;
    public $pass;

    public function rules() {
        return array(
            array('user, pass', 'required'),
            array('user, pass', 'length', array(5, 12)),
            array('pass', 'authenticate')
        );
    }

    private function _validateAuthenticate() {

        return null;
    }
}