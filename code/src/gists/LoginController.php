<?php
/**
 * Created by JetBrains PhpStorm.
 * User: marinnelson
 * Date: 6/26/13
 * Time: 1:02 PM
 * To change this template use File | Settings | File Templates.
 */

class LoginController extends Page_ControllerAbstract {

    public $model;

    public function actionIndex() {

        $this->model = new Login_Form();
        if( isset($_POST) && $this->model->set_post_vars() ) {

            // Attempt to validate our login request. If valid redirect to the account page.
            if( $this->model->validate() ) {
                $this->redirect('account/index');
            }
        } else {
            $this->render('login/index');
        }
    }

    public function actionLogin() {

    }

    public function actionLogout() {

    }
}

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