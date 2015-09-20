<?php

namespace Barbery\SocialLogin\Sdks\Adapter;

use Exception;
use Barbery\SocialLogin\Sdks\Base;
use Barbery\SocialLogin\Sdks\SdkInterface;

/**
 * github登陆
 * @author barbery <a6798246@gmail.com>
 */
class Github extends Base implements SdkInterface
{
    protected $authorizeUrls = array(
        'default' => 'https://github.com/login/oauth/authorize',
    );

    protected $tokenUrls = array(
        'default' => 'https://github.com/login/oauth/access_token',
    );



    public function getAccessToken()
    {
        $this->checkState();
        $tokenUrl = empty($this->tokenUrls[$this->display]) ? $this->tokenUrls['default'] : $this->tokenUrls[$this->display];
        $params = array(
            'client_id'     => $this->key,
            'redirect_uri'  => $this->redirectUrl,
            'client_secret' => $this->secret,
            'code'          => $_GET['code'],
            'state'         => $_GET['state'],
        );

        $token = $this->httpPost($tokenUrl, $params);
        parse_str($token, $token);
        if (empty($token['access_token'])) {
            $this->setLastError(parent::ERROR, $token['error']);
            throw new Exception('获取token失败', parent::ERROR);
        }

        $token['timeout'] = empty($token['expires_in']) ? 0 : time() + $token['expires_in'];
        $this->token = $token;
        return $token;
    }




    public function getUserInfo()
    {
        //组装URL
        $userInfoUrl = 'https://api.github.com/user?'
            . 'access_token=' . $this->token['access_token'];

        $info = json_decode($this->httpGet($userInfoUrl), true);
        if (isset($info['message'])) {
            $this->setLastError(parent::ERROR, $info['message']);
            throw new Exception('获取用户信息失败', parent::ERROR);
        }

        $this->userInfo = $info;
        $userInfo = array(
            'nickname' => $info['login'],
            'face'     => $info['avatar_url'],
            'gender'   => 0,
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