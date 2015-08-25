<?php

/**
 * Application model for CakePHP.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
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
 * @package       app.Model
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses('Model', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AppModel extends Model {

    public $recursive = -1;
    public $actsAs = array('Containable');

    /**
     * Generate token used by the user registration system
     *
     * @param int $length Token Length
     * @return string
     */
    public function generateToken($length = 10) {
        $possible = '0123456789abcdefghijklmnopqrstuvwxyz';
        $token = "";
        $i = 0;

        while ($i < $length) {
            $char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);
            if (!stristr($token, $char)) {
                $token .= $char;
                $i++;
            }
        }
        return $token;
    }

    public function beforeFind($queryData) {

        if (isset($this->defaultFields) && !empty($this->defaultFields)) {
            $queryData['fields'] = array_merge((array) $this->defaultFields, (array) $queryData['fields']);
        }
        if (isset($this->defaultConditions) && !empty($this->defaultConditions)) {
            $queryData['conditions'] = array_merge((array) $this->defaultConditions, (array) $queryData['conditions']);
        }

        if (isset($queryData['conditions'][$this->alias . '.email'])) {
            if (is_string($queryData['conditions'][$this->alias . '.email'])) {
                $this->removeDot($queryData['conditions'][$this->alias . '.email']);
            } elseif (is_array($queryData['conditions'][$this->alias . '.email'])) {
                array_walk($queryData['conditions'][$this->alias . '.email'], array($this, 'removeDot'));
            }
        }
        return $queryData;
    }

    public function dateFormatBeforeSave($dateString, $format = 'Y-m-d') {
        return date($format, strtotime($dateString));
    }

    public function getNextAutoIncrement() {

        //if edit, then ID already exists
        $id = $this->id;

        //if no ID, then get next Auto Increment value
        if (!$id) {
            $table = Inflector::tableize($this->name);
            $result = $this->query("SHOW TABLE STATUS LIKE '$table'");
            $id = $result[0]['TABLES']['Auto_increment'];
        }

        return $id;
    }

    public function makeUniqueSlug(Model $Model, $slug = '', $settings = array()) {
        //   Example
        //   $this->Wedding->makeUniqueSlug($this->Wedding, 'rossrachel', array('slug' => 'unique_code', 'separator' => '', 'unique' => true));

        $Model->defaultConditions = array();
        $conditions = array();
        if ($settings['unique'] === true) {
            $conditions[$Model->alias . '.' . $settings['slug'] . ' LIKE'] = $slug . '%';
        }

        if (!empty($Model->id)) {
            $conditions[$Model->alias . '.' . $Model->primaryKey . ' !='] = $Model->id;
        }


        $duplicates = $Model->find('all', array(
            'recursive' => -1,
            'conditions' => $conditions,
            'fields' => array($settings['slug'])));

        if (!empty($duplicates)) {
            $duplicates = Set::extract($duplicates, '{n}.' . $Model->alias . '.' . $settings['slug']);
            if (!in_array(strtolower($slug), array_map('strtolower', $duplicates))) {
                return $slug;
            }

            $startSlug = $slug;
            $index = 1;

            while ($index > 0) {
                if (!in_array($startSlug . $settings['separator'] . $index, $duplicates)) {
                    $slug = $startSlug . $settings['separator'] . $index;
                    $index = -1;
                }
                $index++;
            }
        }
        return $slug;
    }
    
    public function beforeSave($options = array()) {
        if (!empty($this->data[$this->alias]['email'])) {
            $this->data[$this->alias]['email'] = $this->removeDot($this->data[$this->alias]['email']);
        }
        return true;
    }

    public function removeDot(&$gmail_id) {

        $parts = explode("@", $gmail_id);
        if (strtolower($parts[1]) === "gmail.com") {
            $username = str_replace('.', '', $parts[0]);
            $gmail_id = $username . '@' . $parts[1];
        }
        return $gmail_id;
    }

    public function test(&$gmail_id) {

        $parts = explode("@", $gmail_id);
        if (strtolower($parts[1]) === "gmail.com") {
            $username = str_replace('.', '', $parts[0]);
            $gmail_id = $username . '@' . $parts[1];
        }
        return $gmail_id;
    }

}
