<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\SendSlackNotification;
use App\Notifications\Slack\TextSlackNotification;
use App\Services\Slack\SlackNotificationSender;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class SendSlackNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.slack.notifications.bot_user_oauth_token', 'xoxb-test-token');
    }

    public function test_it_can_be_dispatched_for_async_delivery(): void
    {
        Queue::fake();

        SendSlackNotification::dispatch(new TextSlackNotification('C1234567890', 'Node capacity warning.'));

        Queue::assertPushed(
            SendSlackNotification::class,
            static function (SendSlackNotification $job): bool {
                return $job->notification->destinationChannelId() === 'C1234567890'
                    && $job->notification->content() === 'Node capacity warning.';
            }
        );
    }

    public function test_it_posts_a_message_to_the_slack_chat_api(): void
    {
        Http::fake([
            'https://slack.com/api/chat.postMessage' => Http::response(['ok' => true], 200),
        ]);

        $job = new SendSlackNotification(new TextSlackNotification('C1234567890', 'Weekly report generated.'));
        $job->handle(app(SlackNotificationSender::class));

        Http::assertSent(static function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://slack.com/api/chat.postMessage'
                && $request->hasHeader('Authorization', 'Bearer xoxb-test-token')
                && ($request->data()['channel'] ?? null) === 'C1234567890'
                && ($request->data()['text'] ?? null) === 'Weekly report generated.';
        });
    }

    public function test_it_throws_when_slack_is_not_configured(): void
    {
        config()->set('services.slack.notifications.bot_user_oauth_token', '');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SLACK_BOT_USER_OAUTH_TOKEN is not configured.');

        $job = new SendSlackNotification(new TextSlackNotification('C1234567890', 'Test message'));
        $job->handle(app(SlackNotificationSender::class));
    }

    public function test_it_throws_when_slack_rejects_the_message(): void
    {
        Http::fake([
            'https://slack.com/api/chat.postMessage' => Http::response([
                'ok' => false,
                'error' => 'channel_not_found',
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('channel_not_found');

        $job = new SendSlackNotification(new TextSlackNotification('C404', 'Test message'));
        $job->handle(app(SlackNotificationSender::class));
    }
}
