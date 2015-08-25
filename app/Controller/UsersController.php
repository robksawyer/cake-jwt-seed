<?php

App::uses('CakeEmail', 'Network/Email');
App::uses('AppController', 'Controller');

/**
 * Users Controller
 */
class UsersController extends AppController {

    public $uses = array('User', 'SocialAccount');

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow('connect', 'login');
    }

    /**
     * @author Hardik Sondagar <hardikmsondagar@gmail.com>
     * @return array format data to add new user
     * @depends opauth_complete
     */
    protected function _beforeUserAdd($data) {

        $data['User'] = $data['auth']['info'];

        /* Set active true because it's already validated from social account */
        $data['User']['active'] = true;
        $data['User']['image'] = $this->User->getDisplayPicUrl($data['auth']['uid'], $data['auth']['provider']);
        $data['SocialAccount'] = array(
            array(
                'uid' => $data['auth']['uid'],
                'provider' => $data['auth']['provider']
            )
        );
        /* return data to add new user */
        return $data;
    }

    /**
     * @author Hardik Sondagar <hardikmsondagar@gmail.com>
     * @abstract API url from Opauth plugin after validating return auth data
     *           This will be Dispatched from Opauth plugin's Controller's callback function
     * @usecase Login & Sign Up using social account
     * @uses _beforeUserAdd Set data from auth to add new user
     * @since v1.0
     * @return type redirect
     * @param array $this->data['auth'] Auth data returned from Opauth plugin
     */
    public function connect($provider = 'facebook', $access_token = null) {

        /* Check if user loggedIn */
        if ($this->Auth->user()) {
            $this->set('status', 'success');
            $this->set('message', 'Already logged in');
            $this->set('data', $this->User->get($this->Auth->user('id')));
            return;
        }


        if (in_array($provider, array('facebook', 'google'))) {
            $me = $provider . '_me';
            $data = $this->$me($access_token);
            if (!$data) {
                $this->set('status', 'error');
                $this->set('message', 'Failed to login, Invalid token');
                return;
            }
        } else {
            $this->set('status', 'error');
            $this->set('message', 'Invalid provider, Only Facebook supported');
            return;
        }


        /* Find social account with retunred uid with provider from database */
        $social_account = $this->SocialAccount->getAccount($data['auth']);
        /*
         * If no account found with return data then new user along with social_account 
         */
        if (!isset($social_account['SocialAccount']['user_id'])) {
            /* Set data from auth to add new user */
            $data = $this->_beforeUserAdd($data);
            try {
                if (!$this->{$this->modelClass}->add($data)) {
                    $this->set('status', 'error');
                    $this->set('message', 'Failed to add user. Please try again.');
                    if (isset($this->User->validationErrors)) {
                        $this->set('message', 'Validation Error');
                        $this->set('data', $this->User->validationErrors);
                    }
                    CakeLog::write('signup', json_encode($this->User->validationErrors));
                    return;
                }
            } catch (Exception $ex) {
                $this->set('status', 'error');
                $this->set('message', $ex->getMessage());
                CakeLog::write('signup', json_encode($ex->getMessage()));
                return;
            }
            /* $this->User->id will be set after user successfully added */
        } else {
            $this->User->id = $social_account['SocialAccount']['user_id'];
        }
        /* Find user by primaryKey */
        $user = $this->User->get($this->User->id);

        /* Check if user found and login successfully with returned data */
        if (!empty($user) && $this->Auth->login($user['User'])) {

            /* Save User Token */
            $this->User->id = $this->Auth->user('id');
            $this->User->saveField('last_login', date('Y-m-d H:i:s'));
            $this->set('status', 'success');
            $this->set('message', 'Successfully logged in');
            $this->set('data', $this->User->get($this->User->id));
            $this->set('token', $this->_setToken());
        } else {
            $this->set('status', 'error');
            $this->set('data', $this->{$this->modelClass}->validationErrors);
            $this->set('message', 'Failed to login. Please try again.');
        }

        return;
    }

    /**
     * @name logout()
     * @abstract User Logout
     * @author Hardik Sondagar <hardikmsondagar@gmail.com>
     *  
     */
    public function logout() {
        $message = __d('User', "%s, You have logged out successfully", $this->Auth->user('name'));
        if ($this->Auth->logout()) {
            $this->set('status', 'success');
            $this->set('message', $message);
        } else {

            $this->set('status', 'errro');
            $this->set('message', 'User not logged out, please try again');
        }
    }

    private function facebook_me($access_token) {
        $url = 'https://graph.facebook.com/me';
        $param = array('access_token' => $access_token);
        return $this->me('facebook', $url, $param);
    }

    /**
     * Queries Google API for user info
     *
     * @param string $access_token 
     * @return array Parsed JSON results
     */
    private function google_me($access_token) {

        $url = 'https://www.googleapis.com/oauth2/v1/userinfo';
        $param = array('access_token' => $access_token);
        return $this->me('google', $url, $param);
    }

    private function me($provider, $url, $param) {
        $full_url = $url . '?' . http_build_query($param, '', '&');
        $context = null;
        $content = @file_get_contents($full_url, false, $context);
        if (!empty($content)) {
            $content = get_object_vars(json_decode($content));
            $data = array();
            $data['auth']['info'] = $content;
            $data['auth']['provider'] = Inflector::classify($provider);
            $data['auth']['uid'] = $content['id'];
            return $data;
        }
        return false;
    }

    /**
     * @name login()
     * @abstract Login with Email address
     * @author Hardik Sondagar <hardikmsondagar@gmail.com>
     */
    public function login() {

        $this->request->allowMethod('Post');
        if ($this->Auth->login()) {
            $this->set('status', 'success');
            $this->set('message', 'Successfully logged in');
            $this->set('data', $this->User->get($this->User->id));
        } else {
            $this->set('status', 'error');
            $this->set('message', __d('users', 'Invalid e-mail / password combination.  Please try again'));
        }
    }

    /**
     * @name profile()
     * @abstract Profile View & Edit
     * @author Hardik Sondagar <hardikmsondagar@gmail.com>
     */
    public function update() {
        $this->request->allowMethod(array('Post', 'Put'));
        $this->request->data['User']['id'] = $this->Auth->user('id');
        $User = $this->User->profile($this->Auth->user('id'));
        if (isset($User['Picture']['id'])) {
            $this->request->data['Picture']['id'] = $User['Picture']['id'];
        }
        if ($this->User->update($this->request->data)) {
            $this->Session->write('Auth', $this->User->read(null, $this->Auth->User('id')));
            return $this->redirect(array('action' => 'profile'));
        } else {
            $this->Session->setFlash('Failed to update profile.');
        }
    }

    public function index() {

        $this->set(array(
            'user' => $this->User->get($this->Auth->user('id')),
            '_serialize' => array('user')
        ));
    }

    private function _setToken() {
        $issuedAt = time();
        $notBefore = $issuedAt + 0;             //Adding 10 seconds
        $expire = $notBefore + 60000;            // Adding 60 seconds
        $user = $this->Auth->user();
        $user['exp'] = $expire;
        $token = JWT::encode($user, Configure::read('Security.salt'));
        return $token;
    }

}
