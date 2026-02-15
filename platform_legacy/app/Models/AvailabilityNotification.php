<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use App\Notifications\AvailabilityNotification as Notification;
use App\Events\AvailabilityNotificationCreated;

class AvailabilityNotification extends Model
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'plan',
        'email',
        'region'
    ];

    /**
     * Route notifications for the Slack channel.
     */
    public function routeNotificationForSlack($notification): mixed
    {
        return '#availability-requests';
    }

    /**
         * The event map for the model.
         *
         * @var array
         */
        protected $dispatchesEvents = [
            'created' => AvailabilityNotificationCreated::class,
        ];
}
