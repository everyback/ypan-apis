<?php

namespace App\Listeners;

use App\Events\ShareEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Model\share_count as Count;
use App\Model\share_uses as Uses;

class ShareListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public $tries = 1;

    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ShareEvent $event)
    {
        //

        $user = $event->getUser();
        $ip = $event->getIp();
        $timestamp = $event->getTimestamp();
        $action = $event->getAction();
        $settime = time() - 24*60*60;
        $path = $event->getPath();
        $doesexis = Uses::orWhere([
            ["created_at", ">" ,"$settime"],
            ["share_path" ,$path],
            ["user_id",$user],
        ])
                        ->orWhere([
                            ["user_ip",$ip],
                            ["created_at", ">" ,"$settime"],
                            ["share_path" ,$path],
                        ])
                        ->doesntExist();

        if ($doesexis === true)
        {
            $log = new Uses; //先记录
            $log->user_id = $user;
            $log->user_ip = $ip;
            $log->action = $action;
            $log->share_path = $path;

            $log->save();

            //$count = Count::where("share_path" ,$path)->first();//再加量
            $count = Count::find($path);

            switch ($action)
            {
                case "resave": $count->resave = $count->resave + 1;break;
                case "read": $count->read = $count->read + 1;break;
                case "download": $count->download = $count->download + 1;break;
            }
//            dd($count->read);
            $count->save();
            //  }


        }

    }
}
