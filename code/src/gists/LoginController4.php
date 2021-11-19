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
            if( ! $this->model->validate() ) {
                $this->render('login/fail');
            } else {
                //
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
            array( 'user, pass', 'required'),
            array('pass', 'authenticate')
        );
    }

    private function _validateAuthenticate() {

        return null;
    }
}