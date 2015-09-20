<?php

namespace Barbery\SocialLogin\Sdks;


/**
 * SDK基类
 * @author barbery <a6798246@gmail.com>
 */
class Base
{
    const ERROR = -1;


    //配置APP参数
    protected $key;
    protected $secret;
    protected $redirectUrl;
    protected $scope = null;
    protected $responseType = 'code';
    protected $forceLogin = false;
    protected $display = null;

    protected $token;
    protected $openId;
    protected $from;
    protected $userInfo;
    protected $lastError;
    // 是否启用代理
    protected $enableProxy = false;
    protected $proxy = array();

    function __construct(array $config = array())
    {
    	foreach ($config as $key => $value) {
    		$this->{$key} = $value;
    	}

    	$this->from = ltrim(strtolower(get_class($this)), 'Barbery\\SocialLogin\\Sdks\\Adapter');
    }




    public function authAndGetUserInfo()
    {
        $this->getAccessToken();
        $result = $this->getUserInfo();
        $result['access_token']  = $this->token['access_token'];
        $result['timeout']       = $this->token['timeout'];
        $result['refresh_token'] = isset($this->token['refresh_token']) ? $this->token['refresh_token'] : '';
        
        // clean session value
        unset($_SESSION[$this->from]);

        return $result;
    }





    public function getFullUserInfo()
    {
        return $this->userInfo;
    }



    public function getLastError()
    {
        return $this->lastError;
    }



    public function getTokenInfo()
    {
        return $this->token;
    }



    public function setProxy($proxy)
    {
        $this->proxy = $proxy;
    }



    protected function setLastError($code, $msg = '')
    {
        $this->lastError = array(
            'code' => $code,
            'msg' => $msg
        );
    }






    protected function setSession($key, $value)
    {
        $_SESSION[$this->from][$key] = $value;
    }



    protected function getSession($key)
    {
        return isset($_SESSION[$this->from][$key]) ? $_SESSION[$this->from][$key] : null;
    }




    protected function getState()
    {
        $state = md5(uniqid() . rand(1, 99999) . $this->from);
        $this->setSession('state', $state);
        return $state;
    }




    protected function checkState()
    {
        $state = $this->getSession('state');
        if ( ! empty($state) && $state !== $_GET['state']) {
            throw new Exception('禁止CSRF攻击', parent::ERROR);
        }
    }




    protected function httpGet($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'from barbery\'s sociallogin sdk');
        if ($this->enableProxy) {
            $this->_setProxy($ch);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }




    protected function httpPost($url, $params)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        if ($this->enableProxy) {
            $this->_setProxy($ch);
        }

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }



    private function _setProxy(&$ch)
    {
        if ( ! empty($this->proxy['dns'])) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy['dns']);
        }

        if ( ! empty($this->proxy['type'])) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, $this->proxy['type']);
        }
    }

}