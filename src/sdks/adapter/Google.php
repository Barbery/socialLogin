<?php

namespace Barbery\SocialLogin\Sdks\Adapter;

use Exception;
use Barbery\SocialLogin\Sdks\Base;
use Barbery\SocialLogin\Sdks\SdkInterface;

/**
 * google登陆
 * @author barbery <a6798246@gmail.com>
 */
class Google extends Base implements SdkInterface
{
    protected $authorizeUrls = array(
        'default' => 'https://accounts.google.com/o/oauth2/auth',
    );

    protected $tokenUrls = array(
        'default' => 'https://www.googleapis.com/oauth2/v3/token',
    );



    public function getAccessToken()
    {
        $this->checkState();
        $tokenUrl = empty($this->tokenUrls[$this->display]) ? $this->tokenUrls['default'] : $this->tokenUrls[$this->display];
        $params = array(
            'grant_type'    => 'authorization_code',
            'client_id'     => $this->key,
            'redirect_uri'  => $this->redirectUrl,
            'client_secret' => $this->secret,
            'code'          => $_GET['code']
        );

        $token = json_decode($this->httpPost($tokenUrl, $params), true);
        if (empty($token['access_token'])) {
            $this->setLastError(parent::ERROR, $token['error_description']);
            throw new Exception('获取token失败', parent::ERROR);
        }

        $token['timeout'] = empty($token['expires_in']) ? 0 : time() + $token['expires_in'];
        $this->token = $token;
        return $token;
    }





    public function getUserInfo()
    {
        //组装URL
        $userInfoUrl = 'https://www.googleapis.com/plus/v1/people/me?'
            . 'access_token=' . $this->token['access_token'];

        $info = json_decode($this->httpGet($userInfoUrl), true);
        if (isset($info['error']) && $info['error']['code'] != 0) {
            $this->setLastError($info['error']['code'], $info['error']['message']);
            throw new Exception('获取用户信息失败', parent::ERROR);
        }

        $this->userInfo = $info;

        $gender = array(
            'male'   => 1,
            'female' => 2,
            'other'  => 0
        );

        $userInfo = array(
            'nickname' => $info['nickname'],
            'face'     => $info['image']['url'],
            'gender'   => $gender[$info['gender']],
            'openid'   => $info['id'],
        );

        $this->openId = $info['id'];
        return $userInfo;
    }





    public function getAuthorizeURL()
    {
        $params                  = array();
        $params['client_id']     = $this->key;
        $params['redirect_uri']  = $this->redirectUrl;
        $params['response_type'] = $this->responseType;
        $params['state']         = $this->getState();
        $params['display']       = $this->display;
        $params['scope']         = $this->scope;
        $authorizeUrl = empty($this->authorizeUrls[$this->display]) ? $this->authorizeUrls['default'] : $this->authorizeUrls[$this->display];

        return $authorizeUrl . "?" . http_build_query($params);
    }

}