<?php

namespace App\Listeners;

use App\Events\LoginEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use App\User;
use Illuminate\Http\Request;

class LoginListener implements ShouldQueue
{
    public $tries = 1;

    public function handle(LoginEvent $event )
    {
        //获取事件中保存的信息
        $user = $event->getUser();
        $agent = $event->getAgent();
        $ip = $event->getIp();
        $result = $event->getResult();
        $timestamp = $event->getTimestamp();
        $action = $event->getAction();

       // dd($agent);

        if (gettype($user) === 'array')
        {
           // dd($user);
            $users = isset($user['name']) ?User::where('name',$user['name'])->value('id'):User::where('email',$user['email'])->value('id');
           // dd($users);
            if (!isset($users) )
            {
                $user = -99;
            }
            else{
                $user = $users;
            }
            $login_info = [
                // 'ip' => $ip,
                'create_at' => $timestamp,
                'user_id' => $user,
            ];
        }else
        {
            $login_info = [
                // 'ip' => $ip,
                'create_at' => $timestamp,
                'user_id' => $user->id
            ];
        }

        //登录信息


        // zhuzhichao/ip-location-zh 包含的方法获取ip地理位置
       // $addresses = \Ip::find($ip);
       // $login_info['address'] = implode(' ', $addresses);
        $login_info['address'] = $ip;
        // jenssegers/agent 的方法来提取agent信息
        $login_info['device'] = $agent->device(); //设备名称
        $browser = $agent->browser();
        $login_info['browser'] = $browser . ' ' . $agent->version($browser); //浏览器
        $platform = $agent->platform();
        $login_info['platform'] = $platform . ' ' . $agent->version($platform); //操作系统
        $login_info['language'] = implode(',', $agent->languages()); //语言
        $login_info['action'] = $action;
        $login_info['result'] = $result;
        //设备类型
        if ($agent->isTablet()) {
            // 平板
            $login_info['device_type'] = 'tablet';
        } else if ($agent->isMobile()) {
            // 便捷设备
            $login_info['device_type'] = 'mobile';
        } else if ($agent->isRobot()) {
            // 爬虫机器人
            $login_info['device_type'] = 'robot';
            $login_info['device'] = $agent->robot(); //机器人名称
        } else {
            // 桌面设备
            $login_info['device_type'] = 'desktop';
        }

        //插入到数据库
        DB::table('Login_log')->insert($login_info);

    }
}
