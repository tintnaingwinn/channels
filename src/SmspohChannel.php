<?php

namespace NotificationChannels\Smspoh;

use Illuminate\Notifications\Notification;
use NotificationChannels\Smspoh\Exceptions\CouldNotSendNotification;

class SmspohChannel
{
    /**
     * The Smspoh client instance.
     *
     * @var \NotificationChannels\Smspoh\SmspohApi
     */
    protected $smspoh;

    /**
     * The phone number notifications should be sent from.
     *
     * @var string
     */
    protected $sender;

    /**
     * @var int
     * The message body content count should be no longer than 6 message parts(918).
     */
    protected $character_limit_count = 918;

    public function __construct(SmspohApi $smspoh, $sender)
    {
        $this->smspoh = $smspoh;
        $this->sender = $sender;
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param \Illuminate\Notifications\Notification $notification
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface|void
     * @throws \NotificationChannels\Smspoh\Exceptions\CouldNotSendNotification
     */
    public function send($notifiable, Notification $notification)
    {
        if (! $to = $notifiable->routeNotificationFor('smspoh', $notification)) {
            return;
        }

        $message = $notification->toSmspoh($notifiable);

        if (is_string($message)) {
            $message = new SmspohMessage($message);
        }

        if (mb_strlen($message->content) > $this->character_limit_count) {
            throw CouldNotSendNotification::contentLengthLimitExceeded($this->character_limit_count);
        }

        return $this->smspoh->send([
            'sender' => $message->sender ?: $this->sender,
            'to' => $to,
            'message' => trim($message->content),
            'test' => $message->test ?: 0,
        ]);
    }
}
