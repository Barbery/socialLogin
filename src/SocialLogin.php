<?php

namespace Barbery\SocialLogin;

use Exception;

/**
* 
*/
class SocialLogin
{
    private $type     = '';
    private $configs  = array();
    private $classMap = array(
        'qq'     => 'Qq',
        'weibo'  => 'Weibo',
        'google' => 'Google',
        'github' => 'Github',
    );
    private $obj = null;



    public function __construct(array $config = array(), $type = '')
    {
        $this->type    = strtolower(empty($type) ? $_GET['type'] : $type);
        $this->configs = $config;
        $sessionId = session_id();
        if (empty($sessionId)) {
            session_start();
        }
        
    }



    private function getInstance()
    {
        if (empty($this->classMap[$this->type])) {
            throw new Exception('暂时不支持该登陆方式', -1);
        }

        if (empty($this->configs[$this->type])) {
            throw new Exception('配置信息有误', -1);
        }

        $class = "Barbery\\SocialLogin\\Sdks\\adapter\\{$this->classMap[$this->type]}";
        $this->obj = new $class($this->configs[$this->type]);

        if ( ! empty($this->configs['proxy'])) {
            $this->obj->setProxy($this->configs['proxy']);
        }

        return $this->obj;
    }




    public function authAndGetUserInfo()
    {
        $this->getInstance();
        if (empty($_GET['code'])) {
            return $this->redirectToAuth();
        }

        $result = $this->obj->authAndGetUserInfo();
        $result['from'] = $this->type;
        return $result;
    }




    public function getAuthorizeURL()
    {
        return $this->getAuthorizeURL();
    }



    public function getFullUserInfo()
    {
        if (empty($this->obj)) {
            return array();
        }
        return $this->obj->getFullUserInfo();
    }




    public function getLastError()
    {
        if (empty($this->obj)) {
            return array(
                'code' => 0,
                'msg'  => ''
            );
        }

        return $this->obj->getLastError();
    }




    private function redirectToAuth()
    {
        $url = $this->obj->getAuthorizeURL();
        header("Location: {$url}");
        exit;
    }
}