<?php

/**
 * UserIdentityManager
 *
 * UserIdentityManager class is used for interacting with UserIdentityAPI class.
 * UserIdentityManager class is used for get user detail, create user
 * Copyright (c) 2014 <ahref Foundation -- All rights reserved.
 * Author: Pradeep Kumar <pradeep@incaendo.com>
 * This file is part of <Backendconnector>.
 * This file can not be copied and/or distributed without the express permission of
 *   <ahref Foundation.
 */

class UserIdentityManager extends CFormModel{

  /**
   * createUser
   *
   * This function is used for create  user
   * @param (array) $userDetail
   * @return (array) $saveUser
   */

  public function createUser($userDetail) {
    $saveUser = array();
    $response = array();
    $saveUser['success'] = false;
    try {
      if (empty($userDetail['firstname'])) {
        throw new Exception(Yii::t('discussion', 'Please enter first name'));
      }
      if ((array_key_exists('type', $userDetail) && $userDetail['type'] == 'user')
        || !array_key_exists('type', $userDetail)) {
        if (empty($userDetail['lastname'])) {
          throw new Exception(Yii::t('discussion', 'Please enter last name'));
        }
      }
      if (empty($userDetail['email']) || !filter_var($userDetail['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception(Yii::t('discussion', 'Please enter a valid email'));
      }
      if (empty($userDetail['password'])) {
        throw new Exception(Yii::t('discussion', 'Please enter password'));
      }

      $user = new UserIdentityAPI();
      $response = $user->createUser(IDM_USER_ENTITY, $userDetail);
      if (array_key_exists('_status', $response) &&  $response['_status'] == 'OK') {

        $now = new \DateTime();
        $now->setTimezone(new \DateTimeZone('Europe/Rome'));
        
        // Scrivo file di log
        if (is_dir(RUNTIME_DIRECTORY)) 
        {
          if(is_writable(RUNTIME_DIRECTORY))
          {
		  $filename = RUNTIME_DIRECTORY.'/registration.txt';
		  // Fix hide password
		  $userDetail['password']='******';
      		  file_put_contents($filename, $now->format('Y-m-d H:i:s').' ### '.json_encode($userDetail). PHP_EOL, FILE_APPEND);
          }
        }

        $saveUser['id'] = $response['_id'];
        $saveUser['msg'] = Yii::t('discussion', 'You have successfully created your account');
        $saveUser['success'] = true;
      } else {
        if (array_key_exists('_status', $response) &&  $response['_status'] == 'ERR') {
          if (array_key_exists('nickname', $response['_issues'])) {
            $message = $response['_issues']['nickname'];
          } else if (array_key_exists('email', $response['_issues'])) {
            $message = $response['_issues']['email'];
          }
          if (strpos($message, "is not unique") !== false) {
            $message = Yii::t('discussion', 'Email id already in use, Please choose a different email id');
            if (array_key_exists('nickname', $response['_issues'])) {
              $message = Yii::t('discussion', 'Nickname already in use, Choose another');
            }
          } else {
            $message = Yii::t('discussion', 'Some technical problem occurred, contact administrator');
          }
        }
        else {
          $message = Yii::t('discussion', 'Some technical problem occurred, contact administrator');
        }
        $saveUser['msg'] = $message;
      }
    } catch (Exception $e) {
      $saveUser['msg'] = $e->getMessage();
      Yii::log($e->getMessage(), 'error', 'Error in createUser method ' );
    }
    return $saveUser;
  }

  /**
   * validateUser
   *
   * This function is used for validate user
   * @param (array) $userDetail
   * @return (boolean) $userStatus
   */
   public function validateUser($userDetail) {
    $inputParam = '';
    $userStatus = array();
    $userStatus['success'] = false;
    try {
      if (empty($userDetail['email']) || !filter_var($userDetail['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception(Yii::t('discussion', 'Please enter a valid email'));
      } else {
        $userDetail['email'] = urlencode($userDetail['email']);
      }
      if (empty($userDetail['password'])) {
        throw new Exception(Yii::t('discussion', 'Please enter password'));
      } else {
        $userDetail['password'] = urlencode($userDetail['password']);
      }
      $user = new UserIdentityAPI();
      $userStatus = $user->getUserDetail(IDM_USER_ENTITY, $userDetail);
      if (array_key_exists('success', $userStatus) && !$userStatus['success']) {
        $userStatus['msg'] = Yii::t('discussion', 'Some technical problem occurred, contact administrator');
      } else if (array_key_exists('_items', $userStatus)) {
        if (empty($userStatus['_items'])) {
          $userStatus['msg'] = Yii::t('discussion', 'You have entered either wrong email id or password. Please try again');
        } else {
          if (array_key_exists('status', $userStatus['_items'][0]) && $userStatus['_items'][0]['status'] == 0) {
            throw new Exception(Yii::t('discussion', 'Please activate you account'));
          }
          Yii::app()->session->open();
          $user = array();
          if (array_key_exists('firstname', $userStatus['_items'][0]) && !empty($userStatus['_items'][0]['firstname'])) {
            $user['firstname'] = $userStatus['_items'][0]['firstname'];
          }
          if (array_key_exists('lastname', $userStatus['_items'][0]) && !empty($userStatus['_items'][0]['lastname'])) {
             $user['lastname'] = $userStatus['_items'][0]['lastname'];
          }
          if (array_key_exists('nickname', $userStatus['_items'][0]) && !empty($userStatus['_items'][0]['nickname'])) {
             $user['nickname'] = $userStatus['_items'][0]['nickname'];
          }
          if (array_key_exists('email', $userStatus['_items'][0]) && !empty($userStatus['_items'][0]['email'])) {
            $user['email'] = $userStatus['_items'][0]['email'];
          }
          if (array_key_exists('created', $userStatus['_items'][0]) && !empty($userStatus['_items'][0]['created'])) {
            $user['creationDate'] = $userStatus['_items'][0]['created'];
          }
          if (array_key_exists('etag', $userStatus['_items'][0]) && !empty($userStatus['_items'][0]['etag'])) {
            $user['etag'] = $userStatus['_items'][0]['etag'];
          }
          if (array_key_exists('_id', $userStatus['_items'][0]) && !empty($userStatus['_items'][0]['_id'])) {
            $user['id'] = $userStatus['_items'][0]['_id'];
          }
          Yii::app()->session['user'] = $user;
          $userStatus['success'] = true;
        }
      }
    } catch (Exception $e) {
      $userStatus['msg'] = $e->getMessage();
      Yii::log('', 'error', 'Error in validateUser method :' . $e->getMessage());
    }
    return $userStatus;
  }

  /**
   * setUserDetailInSession
   *
   * This function is used for getting information by userid and store it in session.
   * @param (string) $userId
   * @return (array) $userStatus
   */
  public function setUserDetailInSession($userId) {
    $user = new UserIdentityAPI();
    $userStatus = $user->getUserInfo(IDM_USER_ENTITY, $userId);
    if (array_key_exists('success', $userStatus) && empty($userStatus['success'])) {
      $userStatus['msg'] = 'Some technical problem occurred, contact administrator';
    } else {
      Yii::app()->session->open();
      $user = array();
      if (array_key_exists('firstname', $userStatus) && !empty($userStatus['firstname'])) {
        $user['firstname'] = $userStatus['firstname'];
      }
      if (array_key_exists('lastname', $userStatus) && !empty($userStatus['lastname'])) {
        $user['lastname'] = $userStatus['lastname'];
      }
      if (array_key_exists('email', $userStatus) && !empty($userStatus['email'])) {
        $user['email'] = $userStatus['email'];
      }
      if (array_key_exists('created', $userStatus) && !empty($userStatus['created'])) {
        $user['creationDate'] = $userStatus['created'];
      }
      if (array_key_exists('etag', $userStatus) && !empty($userStatus['etag'])) {
        $user['etag'] = $userStatus['etag'];
      }
      if (array_key_exists('_id', $userStatus) && !empty($userStatus['_id'])) {
        $user['id'] = $userStatus['_id'];
      }
      Yii::app()->session['user'] = $user;
      $userStatus['success'] = true;
    }
    return $userStatus;
  }

  public function curlPut($email) {
    $userIdentityApi = new UserIdentityManager();
        $inputParam = array(
          'status' => 1,
          'email' => $email
        );
        return $userIdentityApi->curlPut($inputParam);
    }
  }
