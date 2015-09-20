<?php

namespace Barbery\SocialLogin\Sdks\Adapter;

use Exception;
use Barbery\SocialLogin\Sdks\Base;
use Barbery\SocialLogin\Sdks\SdkInterface;

/**
 * weibo登陆
 * @author barbery <a6798246@gmail.com>
 */
class Weibo extends Base implements SdkInterface
{
    protected $authorizeUrls = array(
        'default' => 'https://api.weibo.com/oauth2/authorize',
        'mobile'  => 'https://open.weibo.cn/oauth2/authorize ',
    );

    protected $tokenUrls = array(
        'default' => 'https://api.weibo.com/oauth2/access_token',
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
            $this->setLastError($token['error_code'], $token['error']);
            throw new Exception('获取token失败', parent::ERROR);
        }

        $token['timeout'] = empty($token['expires_in']) ? 0 : time() + $token['expires_in'];
        $this->token = $token;
        return $token;
    }





    public function getUserInfo()
    {
        $this->openId = $this->token['uid'];
        //组装URL
        $userInfoUrl = 'https://api.weibo.com/2/users/show.json?'
            . 'access_token=' . $this->token['access_token']
            . '&uid=' . $this->openId;

        $info = json_decode($this->httpGet($userInfoUrl), true);
        if (isset($info['error_code']) && $info['error_code'] != 0) {
            $this->setLastError($info['error_code'], $info['error']);
            throw new Exception('获取用户信息失败', parent::ERROR);
        }

        $this->userInfo = $info;
        $gender = array(
            'm' => 1,
            'f' => 2,
            'n' => 0
        );

        $userInfo = array(
            'nickname' => $info['name'],
            'face'     => str_replace('/50/', '/180/', $info['profile_image_url']),
            'gender'   => $gender[$info['gender']],
            'openid'   => $this->openId,
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