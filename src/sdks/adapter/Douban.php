<?php

namespace Barbery\SocialLogin\Sdks\Adapter;

use Exception;
use Barbery\SocialLogin\Sdks\Base;
use Barbery\SocialLogin\Sdks\SdkInterface;

/**
 * douban登陆
 * @author barbery <a6798246@gmail.com>
 */
class Douban extends Base implements SdkInterface
{
    protected $authorizeUrls = array(
        'default' => 'https://www.douban.com/service/auth2/auth',
    );

    protected $tokenUrls = array(
        'default' => 'https://www.douban.com/service/auth2/token',
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
            $this->setLastError($token['code'], $token['message']);
            throw new Exception('获取token失败', parent::ERROR);
        }

        $token['timeout'] = empty($token['expires_in']) ? 0 : time() + $token['expires_in'];
        $this->token = $token;
        return $token;
    }





    public function getUserInfo()
    {
        //组装URL
        $userInfoUrl = 'https://api.douban.com/v2/user/~me?';
        $info = json_decode($this->httpGet($userInfoUrl, array("Authorization: Bearer {$this->token['access_token']}")), true);
        if (isset($info['code']) && $info['code'] != 0) {
            $this->setLastError($info['code'], $info['msg']);
            throw new Exception('获取用户信息失败', parent::ERROR);
        }

        $this->userInfo = $info;
        $this->openId = $info['id'];
        $userInfo = array(
            'nickname' => $info['name'],
            'face'     => empty($info['large_avatar']) ? str_replace('/u', '/up', $info['avatar']) : $info['large_avatar'],
            'gender'   => 0,
            'openid'   => $info['id'],
        );

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