<?php

namespace Barbery\SocialLogin\Sdks;

interface SdkInterface
{
    // 获取授权地址
    public function getAuthorizeURL();

    // 获取授权token
    public function getAccessToken();
    
    // 获取用户信息
    public function getUserInfo();

    // 获取所有返回的用户信息
    public function getFullUserInfo();
}