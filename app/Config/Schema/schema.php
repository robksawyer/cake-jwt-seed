<?php

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config.Schema
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
/**
 *
 * Using the Schema command line utility
 *
 * Use it to configure database for Gridle
 *
 */
/*
 * During schema update with new table if Cakephp throws an error of Database missing in datasource
 * And Cache='Redis'
 * Then, go to terminal and  type :
 * redis-cli --raw keys "{{$PREFIX}}cake_model*" | xargs redis-cli DEL
 * where $PREFIX = variable in core.php
 */

App::uses('ClassRegistry', 'Utility');

class masterSchema extends CakeSchema {

    public $name = 'master';
    
    /*
     * Table name : users 
     * Description : this table will be use for login and signup
     */
    var $users = array(
        'id' => array('type' => 'integer', 'null' => false, 'length' => 9, 'key' => 'primary'),
        'email' => array('type' => 'string', 'null' => true, 'length' => 320, 'key' => 'index'),
        'password' => array('type' => 'string', 'null' => true, 'length' => 128),
        'slug' => array('type' => 'string', 'null' => true, 'default' => NULL),
        'first_name' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 35),
        'last_name' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 35),
        'gender' => array('type' => 'string', 'null' => true, 'length' => 6),
        'image' => array('type' => 'string', 'null' => true, 'length' => 512),
        'dob' => array('type' => 'date', 'null' => true, 'default' => NULL),
        'location' => array('type' => 'string', 'null' => true, 'default' => false, 'length' => 100),
        'role' => array('type' => 'string', 'null' => false, 'default' => 'user', 'length' => 16),
        'last_login' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
        'last_action' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
        'active' => array('type' => 'boolean', 'null' => false, 'default' => '0'),
        'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
        'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
        'indexes' => array(
            'PRIMARY' => array('column' => 'id', 'unique' => 1),
            'BY_UNIQUE_EMAIL' => array('column' => array('email'), 'unique' => 1)
        )
    );

    /*
     * Table name : social_accounts 
     * Description : this table will be use to login through various social accounts like facebook, Google etc
     */
    public $social_accounts = array(
        'id' => array('type' => 'integer', 'null' => false, 'length' => 9, 'key' => 'primary'),
        'user_id' => array('type' => 'integer', 'null' => false, 'length' => 9, 'key' => 'index'),
        'uid' => array('type' => 'string', 'null' => false, 'length' => 200),
        /* Social account provider */
        'provider' => array('type' => 'string', 'null' => false, 'length' => 40),
        'created' => array('type' => 'datetime', 'null' => false),
        'deleted' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
        'modified' => array('type' => 'datetime', 'null' => false),
        'indexes' => array(
            'PRIMARY' => array('column' => 'id', 'unique' => 1),
            'SOCIAL_ACCOUNT_BY_USER_ID' => array('column' => 'user_id', 'unique' => 0)
        )
    );
    
}
