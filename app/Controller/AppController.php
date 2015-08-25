<?php

/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses('Controller', 'Controller');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package		app.Controller
 * @link		http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {

    public $components = array(
        'Session',
        'RequestHandler',
        'Auth' => array(
            'ajaxLogin' => 'ajaxLogin',
            'logoutRedirect' => array('controller' => 'users', 'action' => 'login', 'ext' => 'json'),
            'autoRedirect' => false,
            'authenticate' => array(
                'Form' => array('fields' => array(
                        'username' => 'email',
                        'password' => 'password'
                    )),
                'Authenticate.JwtToken' => array(
                    'fields' => array(
                        'username' => 'email',
                        'password' => 'password',
                    ),
                    'header' => 'X-ApiToken',
                    'userModel' => 'User',
                    'scope' => array(
                        'User.active' => 1
                    )
                )
            ),
        ),
    );

    public function beforeRender() {
        parent::afterFilter();
        unset($this->viewVars['_serialize']);
        foreach ($this->viewVars as $key => $var) {
            $this->viewVars['_serialize'][] = $key;
        }
    }

    /**
     * Proxy for Controller::redirect() to handle AJAX redirects
     *
     * @param string $url 
     * @param int $status 
     * @param bool $exit 
     * @return void
     */
    public function redirect($url, $status = null, $exit = true) {
        if ((isset($controller->request->params['ext']) && $controller->request->params['ext'] === 'json')) {

            $this->autoRender = false;
            $this->response->statusCode(302);
            $this->viewPath = 'Elements';
            $this->set('redirectUrl', Router::url($url));
            $this->set('message', $this->Session->read('Message'));
            $this->set('code', 302);
            $this->set('status', 'redirect');
            $this->Session->delete('Message.flash');
            $response = $this->render('ajaxRedirect', $this->RequestHandler->ajaxLayout);
            $response->send();
            $this->_stop();
            return false;
        }
        return parent::redirect($url, $status, $exit);
    }

}
