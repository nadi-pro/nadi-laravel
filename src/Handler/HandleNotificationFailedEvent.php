<?php

namespace Nadi\Laravel\Handler;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Events\NotificationFailed;
use Nadi\Data\Type;
use Nadi\Laravel\Actions\ExtractTags;
use Nadi\Laravel\Actions\FormatModel;
use Nadi\Laravel\Data\Entry;
use Nadi\Laravel\Support\OpenTelemetrySemanticConventions;

class HandleNotificationFailedEvent extends Base
{
    /**
     * Record a new notification message was sent.
     *
     * @return void
     */
    public function handle(NotificationFailed $event)
    {
        $notification_class = get_class($event->notification);
        $notifiable = $this->formatNotifiable($event->notifiable);
        $is_queued = in_array(ShouldQueue::class, class_implements($event->notification));

        // Generate OpenTelemetry semantic convention attributes
        $otelAttributes = OpenTelemetrySemanticConventions::notificationAttributes($notification_class, $event->channel);

        // Add user context if available
        $userAttributes = OpenTelemetrySemanticConventions::userAttributes();

        // Add session context if available
        $sessionAttributes = OpenTelemetrySemanticConventions::sessionAttributes();

        // Merge all OpenTelemetry attributes
        $otelData = array_merge($otelAttributes, $userAttributes, $sessionAttributes);

        $entryData = [
            'notification' => $notification_class,
            'queued' => $is_queued,
            'notifiable' => $notifiable,
            'channel' => $event->channel,
            'data' => $event->data,
            // Add OpenTelemetry semantic convention data
            'otel' => $otelData,
        ];

        $this->store(Entry::make(Type::NOTIFICATION, $entryData)
            ->setHashFamily($this->hash($notification_class.$notifiable.date('Y-m-d')))
            ->tags($this->tags($event))
            ->toArray());
    }

    /**
     * Extract the tags for the given event.
     *
     * @param  \Illuminate\Notifications\Events\NotificationSent  $event
     * @return array
     */
    private function tags($event)
    {
        return array_merge([
            $this->formatNotifiable($event->notifiable),
        ], ExtractTags::from($event->notification));
    }

    /**
     * Format the given notifiable into a tag.
     *
     * @param  mixed  $notifiable
     * @return string
     */
    private function formatNotifiable($notifiable)
    {
        if ($notifiable instanceof Model) {
            return FormatModel::given($notifiable);
        } elseif ($notifiable instanceof AnonymousNotifiable) {
            return 'Anonymous:'.implode(',', $notifiable->routes);
        }

        return get_class($notifiable);
    }
}
