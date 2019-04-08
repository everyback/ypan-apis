<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ShareEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    protected $user;

    protected $ip;

    protected $timestamp;

    protected $action;

    protected $path;

    public function __construct($user, $ip, $action,$path,$timestamp)
    {
        //
        //dd("dsfdsghtrejhuytrjh");
        $this->user = $user;
        $this->ip = $ip;
        $this->timestamp = $timestamp;
        $this->path = $path;
        $this->action = $action;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getIp()
    {
        return $this->ip;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
