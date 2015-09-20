<?php

namespace Barbery\SocialLogin\Sdks\Adapter;

use Exception;
use Barbery\SocialLogin\Sdks\Base;
use Barbery\SocialLogin\Sdks\SdkInterface;

/**
 * qq登陆
 * @author barbery <a6798246@gmail.com>
 */
class Qq extends Base implements SdkInterface
{
    protected $authorizeUrls = array(
        'default' => 'https://graph.qq.com/oauth2.0/authorize',
        'mobile'  => 'https://graph.z.qq.com/moc2/authorize',
    );

    protected $tokenUrls = array(
        'default' => 'https://graph.qq.com/oauth2.0/token',
        'mobile'  => 'https://graph.z.qq.com/moc2/token',
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

        $token  = array();
        $result = $this->httpGet($tokenUrl . '?' . http_build_query($params));
        if (strpos($result, "callback") !== false) {
            $lpos = strpos($result, "(");
            $rpos = strrpos($result, ")");
            $token = json_decode(substr($result, $lpos + 1, $rpos - $lpos -1), true);
        } else {
            parse_str($result, $token);
        }

        if (empty($token['access_token'])) {
            $this->setLastError($token['error'], $token['error_description']);
            throw new Exception('获取token失败', parent::ERROR);
        }

        $token['timeout'] = empty($token['expires_in']) ? 0 : time() + $token['expires_in'];
        $this->token = $token;
        return $token;
    }





    public function getOpenId()
    {
        $user = array();
        $str = $this->httpGet('https://graph.qq.com/oauth2.0/me?access_token=' . $this->token['access_token']);
        if (strpos($str, "callback") !== false) {
            $lpos = strpos($str, "(");
            $rpos = strrpos($str, ")");
            $str  = substr($str, $lpos + 1, $rpos - $lpos -1);
            $user = json_decode($str, true);
        } else {
            parse_str($str, $user);
        }

        if (empty($user['openid'])) {
            $this->setLastError($user['error'], $user['error_description']);
            throw new Exception('获取openid失败', parent::ERROR);
        }

        $this->openId = $user['openid'];
        return $user['openid'];
    }





    public function getUserInfo()
    {
        $this->getOpenId();
        //组装URL
        $userInfoUrl = 'https://graph.qq.com/user/get_user_info?'
            . 'access_token=' . $this->token['access_token']
            . '&oauth_consumer_key=' . $this->key
            . '&openid=' . $this->openId
            . '&format=json';

        $info = json_decode($this->httpGet($userInfoUrl), true);
        if (isset($info['ret']) && $info['ret'] != 0) {
            $this->setLastError($info['ret'], $info['msg']);
            throw new Exception('获取用户信息失败', parent::ERROR);
        }

        $this->userInfo = $info;
        $gender = array(
            '男' => 1,
            '女' => 2,
        );

        $userInfo = array(
            'nickname' => $info['nickname'],
            'face'     => empty($info['figureurl_qq_2']) ? $info['figureurl_qq_1'] : $info['figureurl_qq_2'],
            'gender'   => isset($gender[$info['gender']]) ? $gender[$info['gender']] : 0,
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