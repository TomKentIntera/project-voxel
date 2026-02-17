<?php

declare(strict_types=1);

namespace Interadigital\CoreModels\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Interadigital\CoreModels\Database\Factories\AvailabilityNotificationFactory;

class AvailabilityNotification extends Model
{
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'plan',
        'email',
        'region',
    ];

    /**
     * The event map for the model.
     *
     * @var array<string, class-string>
     */
    protected $dispatchesEvents = [
        'created' => \App\Events\AvailabilityNotificationCreated::class,
    ];

    /**
     * Route notifications for the Slack channel.
     */
    public function routeNotificationForSlack(object $notification): mixed
    {
        return '#availability-requests';
    }

    protected static function newFactory(): Factory
    {
        return AvailabilityNotificationFactory::new();
    }
}
