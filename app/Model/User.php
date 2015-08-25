<?php

App::uses('AppModel', 'Model');

/**
 * User Model
 *
 * @property UserInfo $UserInfo
 */
class User extends AppModel {

    /**
     * Validation rules
     *
     * @var array
     */
    public $validate = array(
        'password' => array(
            'notEmpty' => array(
                'rule' => array('notEmpty'),
            ),
        ),
        'first_name' => array(
            'rule' => array('between', 2, 36),
            'message' => 'First name contain characters between 3 than 35',
            'required' => true,
            'on' => 'create'
        ),
        'last_name' => array(
            'rule1' => array(
                'rule' => array('between', 2, 36),
                'on' => 'update',
                'message' => 'Last name contain characters between 3 than 35'
            ),
        ),
        'email' => array(
            'isValid' => array(
                'rule' => 'email',
                'required' => false,
                'allowEmpty' => true,
                'on' => 'create',
                'message' => 'Please enter a valid email address.'),
            'isUnique' => array(
                'rule' => array('isUnique', 'email'),
                'message' => 'This email is already in use.')
        ),
        'last_login' => array(
            'datetime' => array(
                'rule' => array('datetime'),
            ),
        ),
    );
    public $fieldList = array('email', 'password', 'first_name', 'last_name', 'gender', 'dob', 'role', 'location', 'active', 'last_login', 'token', 'image', 'wedding_credit');

    //The Associations below have been created with all possible keys, those that are not needed can be removed

    /**
     * hasMany associations
     *
     * @var array
     */
    public $hasMany = array(
        'SocialAccount' => array(
            'className' => 'SocialAccount',
            'foreignKey' => 'user_id',
            'dependent' => true,
            'conditions' => '',
            'fields' => '',
            'order' => '',
            'limit' => '',
            'offset' => '',
            'exclusive' => '',
            'finderQuery' => '',
            'counterQuery' => ''
        ),
    );
    public $hasOne = array(
        'Picture' => array(
            'className' => 'Attachment',
            'foreignKey' => 'foreign_key',
            'conditions' => array(
                'Picture.model' => 'User',
            ),
        )
    );

    public function beforeSave($options = array()) {
        if (isset($this->data['User']['password'])) {
            $hash = $this->hash($this->data['User']['password'], null, true);
            $this->data['User']['password'] = $hash;
        }

        if (!empty($this->data['User']['email'])) {
            $parts = explode("@", $this->data['User']['email']);
            if (strtolower($parts[1]) === "gmail.com") {
                $username = str_replace('.', '', $parts[0]);
                $this->data['User']['email'] = $username . '@' . $parts[1];
            }
        }
        return true;
    }

    public function afterFind($results, $primary = false) {
        foreach ($results as $key => $val) {
            if (isset($val[$this->alias]['image'])) {
                if (preg_match('*graph.facebook.com*', $val[$this->alias]['image'])) {
                    $results[$key][$this->alias]['xvga_image'] = $val[$this->alias]['image'] . '?type=square';
                    $results[$key][$this->alias]['vga_image'] = $val[$this->alias]['image'] . '?type=normal';
                    $results[$key][$this->alias]['thumb_image'] = $val[$this->alias]['image'] . '?type=square';
                } else if (preg_match('*googleusercontent*', $val[$this->alias]['image'])) {
                    $results[$key][$this->alias]['xvga_image'] = $val[$this->alias]['image'] . '?sz=160';
                    $results[$key][$this->alias]['vga_image'] = $val[$this->alias]['image'] . '?sz=64';
                    $results[$key][$this->alias]['thumb_image'] = $val[$this->alias]['image'] . '?sz=32';
                }
            }
        }
        return $results;
    }

    public function __construct($id = false, $table = null, $ds = null) {
        parent::__construct($id, $table, $ds);
        $this->virtualFields = array(
            'name' => 'CONCAT(' . $this->alias . '.first_name, " ", ' . $this->alias . '.last_name)'
        );

        if (isset($_SERVER['HTTP_HOST']) && ( preg_match('/test.gridle/', $_SERVER['HTTP_HOST']))) {
            Configure::write('server_mode', 'development');
        }
//            'image_square' => 'CONCAT("https://graph.facebook.com/",User.facebook_uid,"/picture?type=square")',
//            'image_normal' => 'CONCAT("https://graph.facebook.com/",User.facebook_uid,"/picture?type=normal")',
//            'image_large' => 'CONCAT("https://graph.facebook.com/",User.facebook_uid,"/picture?type=square")'
    }

    /**
     * Create a hash from string using given method.
     * Fallback on next available method.
     *
     * Override this method to use a different hashing method
     *
     * @param string $string String to hash
     * @param string $type Method to use (sha1/sha256/md5)
     * @param boolean $salt If true, automatically appends the application's salt
     * 	 value to $string (Security.salt)
     * @return string Hash
     */
    public function hash($string, $type = null, $salt = false) {
        return Security::hash($string, $type, $salt);
    }

    /**
     * Changes the password for a user
     *
     * @param array $postData Post data from controller
     * @return boolean True on success
     */
    public function changePassword($postData = array()) {
        $postData[$this->alias]['password'] = $this->hash($postData[$this->alias]['new_password'], null, true);
        return $this->save($postData, array(
                    'validate' => false,
                    'callbacks' => false));
    }

    public function get($id) {
        $conditions = array(
            $this->alias . '.' . $this->primaryKey => $id
        );
        $contain = array('SocialAccount');
        return $this->find('first', array(
                    'conditions' => $conditions,
                    'contain' => $contain
        ));
    }

    public function add($data) {
        $fieldList[$this->alias] = $this->fieldList;
        if (isset($data['SocialAccount'])) {
            $fieldList['SocialAccount'] = $this->SocialAccount->fieldList;
        }
        $result = $this->saveAll($data, array(
            'fieldList' => $fieldList,
        ));
        if ((bool) $result) {
            $data[$this->alias]['id'] = $this->id;
            $Event = new CakeEvent('Model.User.afterRegistration', $this, $data);
            $this->getEventManager()->dispatch($Event);
        }
        return $result;
    }

    public function getDisplayPicUrl($uid, $provider) {
        $url = '';
        if ($provider == 'Google') {

            $request_url = 'https://www.googleapis.com/plus/v1/people/' . $uid . '?fields=image&key=' . Configure::read('Opauth.Strategy.Google.api_key');

            $ch = curl_init();
            // set url 
            curl_setopt($ch, CURLOPT_URL, $request_url);

            //return the transfer as a string 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $result = json_decode(curl_exec($ch));
            $url = $result->image->url;
            curl_close($ch);
        } else if ($provider == 'Facebook') {
            $url = 'https://graph.facebook.com/' . $uid . '/picture?type=square';
        }
        return preg_replace('/\?.*/', '', $url);
    }

    public function profile($id) {
        $conditions = array($this->alias . '.id' => $id);
        $contain = array('Picture');
        return $this->find('first', array(
                    'conditions' => $conditions,
                    'contain' => $contain,
        ));
    }

    public function update($data) {
        $this->id = $data[$this->alias]['id'];
        if (isset($data['Picture']['attachment']) && $data['Picture']['attachment']['error'] === UPLOAD_ERR_OK) {
            $data['Picture']['model'] = $this->alias;
            // Unset the foreign_key if the user tries to specify it
            if (isset($data['Picture']['foreign_key'])) {
                unset($data['Picture']['foreign_key']);
            }
        } else {
            unset($data['Picture']);
        }
        return $this->saveAll($data, array(
                    'fieldList' => array(
                        'User' => array('first_name', 'last_name'),
                        'Picture'
                    )
        ));
    }

    public function findBySocial($data) {
        if (empty($data['email'])) {
            $Social = $this->SocialAccount->findByUidAndProvider($data['uid'], $data['provider']);
            return (empty($Social['SocialAccount']['user_id'])) ? false : $Social['SocialAccount']['user_id'];
        } else {
            $User = $this->findByEmail($data['email']);
            return (empty($User['User']['id'])) ? false : $User['User']['id'];
        }
    }

}
