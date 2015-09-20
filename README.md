# SocialLogin 一个简单易用的跨框架第三方登陆库 | An easy to use cross-frame social login library

SocialLogin 是一个简单易用的跨框架第三方登陆库，专注和致力于解决第三方登陆的问题，无需再看官方手册，一个一个服务商去接入登陆服务，现在只需要使用SocialLogin库，把接入的服务商appkey 和 appsecret填好，立即就可以使用，让你无需关心细节实现，专注实现业务逻辑。

[![Latest Stable Version](https://poser.pugx.org/barbery/social-login/v/stable)](https://packagist.org/packages/barbery/social-login) [![Total Downloads](https://poser.pugx.org/barbery/social-login/downloads)](https://packagist.org/packages/barbery/social-login) [![Latest Unstable Version](https://poser.pugx.org/barbery/social-login/v/unstable)](https://packagist.org/packages/barbery/social-login) [![License](https://poser.pugx.org/barbery/social-login/license)](https://packagist.org/packages/barbery/social-login)

## 如何安装 | Installation

```
composer require barbery/social-login:dev-master
```
如果你还不懂如何使用composer，请看：[composer中国镜像](http://www.phpcomposer.com/)


## 如何使用 | Usage

在框架的控制器中，建立一个Action来统一处理第三方登陆，以下的例子是假设在user的控制器中添加一个socialLogin的Action来处理
```php
<?php

// 声明包引用
use Barbery\SocialLogin\SocialLogin;

class User extend Controllers
{
    # 其他Action....

    /**
     * @name 第三方登陆统一网关
     * @method GET
     * @param string redirectUrl Y 第三方登陆完毕跳回地址
     * @return mixed
     */
    public function socialLoginAction()
    {
        // 配置可以移到框架的配置文件里面
        $config = [
            'proxy' => [
                'dns' => '127.0.0.1:1080',
                'type' => CURLPROXY_SOCKS5
            ],
            'weibo' => [
                'key' => '111111111',
                'secret' => 'xxxxxxxxx',
                'redirectUrl' => '/user/socialLogin?type=weibo',
                'scope' => 'email'
            ],
            'qq' => [
                'key' => '111111111',
                'secret' => 'xxxxxxxxxxxx',
                'redirectUrl' => '/user/socialLogin?type=qq',
            ],
            'google' => [
                'key' => 'xxxxxxxxxxxxxx',
                'secret' => 'xxxxxxxxxx',
                'redirectUrl' => '/user/socialLogin?type=google',
                'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
                'enableProxy' => true,
            ],
            'github' => [
                'key' => 'xxxxxxxxxxxx',
                'secret' => 'xxxxxxxxxxxxx',
                'redirectUrl' => '/user/socialLogin?type=github',
            ],
        ];


        try {
            $obj = new SocialLogin($config);
            $socialInfo = $obj->authAndGetUserInfo();
        } catch (Exception $e) {
            echo $e->getMessage(), '具体原因：' . print_r($obj->getLastError(), true);
            exit;
        }

        print_r($socialInfo);
            // Array
            // (
            //     [nickname] => 偶尔陶醉
            //     [face] => http://tp1.sinaimg.cn/1763035100/180/5684566873/1
            //     [gender] => 1
            //     [openid] => 11111111
            //     [access_token] => xxxxxxxxxxxxxx
            //     [timeout] => 1600413552
            //     [refresh_token] => 
            //     [from] => weibo
            // )
        
        if ( ! User::isExist($socialInfo['openid'], $socialInfo['from'])) {
            # 如果用户不存在, 引导用户完成后续注册
        } else {
            # 用户存在，则完成登陆
            redirect($_GET['redirectUrl']);
        }
    }


    # 其他Action....
}

```

配好后，如果用户需要第三方登陆，只需要引导用户跳转到
`/user/socialInfo?type={$type}&redirectUrl=/index` 下就可以了，$type为你传进去的配置，例如，你只设置了google和github的登陆，type就只能是google或github，redirectUrl参数是当用户成功登陆后, 跳回地址。


# 配置说明 | Config

目前支持的第三方登陆有：Github，Google，QQ，Weibo，后续会不断增加，支持的列表可以从[这里](https://github.com/Barbery/socialLogin/tree/master/src/sdks/adapter)看到。

配置demo:
```php
[
    // 支持代理设置，国内的GFW，登陆google时你懂的
    'proxy' => [
        'dns' => '127.0.0.1:1080',
        //代理类型，具体请看 curl CURLOPT_PROXYTYPE 选项说明：http://php.net/manual/zh/function.curl-setopt.php 
        'type' => CURLPROXY_SOCKS5
    ],
    // 第三方登陆应用设置
    // 服务商
    'weibo' => [
        //应用信息
        'key' => '11111111111',
        'secret' => 'xxxxxxxxxxxxxxx',
        // 授权返回地址，注意：这里需要跳回到统一的第三方处理action, 其中type参数要和服务商对应不可缺失
        'redirectUrl' => '/user/socialLogin?type=weibo',
        // 授权内容
        'scope' => 'email'
    ],
    'qq' => [
        'key' => '11111111111',
        'secret' => 'xxxxxxxxxxxxxxx',
        'redirectUrl' => 'http://cvmeta.com/user/socialLogin?type=qq',
    ],
    'google' => [
        'key' => 'xxxxxxxxxxxxxxx',
        'secret' => 'xxxxxxxxxxxxxxx',
        'redirectUrl' => '/user/socialLogin?type=google',
        'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile',
        // 是否启用代理
        'enableProxy' => true,
    ],
    'github' => [
        'key' => 'xxxxxxxxxxxxxxx',
        'secret' => 'xxxxxxxxxxxxxxx',
        'redirectUrl' => '/user/socialLogin?type=github',
    ],
];
```



# 文档 | Document

private function **getInstance**()  
获取sdk对象

public function **authAndGetUserInfo**()  
引导用户授权且获取用户信息，该方法统一返回一个userInfo数据结构
```php
<?php
$userInfo = array(
    'nickname'      => '第三方昵称',
    'face'          => '第三方头像url，优先拿大图',
    'gender'        => '性别',//1为男，2为女，0为未设置或未知
    'openid'        => '第三方唯一身份标识id',
    'access_token'  => '授权access_token',
    'timeout'       => 'access_token过期时间戳',
    'refresh_token' => 'refresh_token','如果有则返回，无则为空字符串',
    'from'          => '第三方来源标识'//google,github,weibo,qq...etc.
);
```
public function **getAuthorizeURL**()  
获取第三方授权地址

public function **getFullUserInfo**()  
获取全部返回的用户信息

public function **getLastError**()  
获取返回的最后的一个错误信息
```php
array(
    'code' => '第三方登陆服务商返回错误码',//如果没有错误码则为 -1
    'msg' => '第三方登陆服务商返回的错误msg'
);
```
