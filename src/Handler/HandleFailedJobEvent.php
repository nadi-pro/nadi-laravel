<?php

namespace Nadi\Laravel\Handler;

use Nadi\Data\Type;
use Nadi\Laravel\Actions\ExceptionContext;
use Nadi\Laravel\Actions\ExtractProperties;
use Nadi\Laravel\Actions\ExtractTags;
use Nadi\Laravel\Data\Entry;
use Illuminate\Encryption\Encrypter;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class HandleFailedJobEvent extends Base
{
    /**
     * Handle the event.
     */
    public function handle(JobFailed $event): void
    {
        $connection = $event->connectionName;
        $job = $event->job;

        $payload = $job->payload();

        if (empty(data_get($payload, 'job'))) {
            Log::error('Missing job key in the queue job payload.', $payload);

            return;
        }

        if (empty(data_get($payload, 'data.command'))) {
            Log::error('Unable to extract empty command.', $payload);

            return;
        }

        $queue = $job->getQueue();
        $content = $this->defaultJobData(
            $connection, $queue, $payload,
            $this->data($payload)
        );

        $this->store(Entry::make(
            Type::QUEUE, [
                'data' => $content,
                'status' => 'failed',
                'exception' => [
                    'file' => $event->exception->getFile(),
                    'message' => $event->exception->getMessage(),
                    'trace' => $event->exception->getTrace(),
                    'line' => $event->exception->getLine(),
                    'line_preview' => ExceptionContext::get($event->exception),
                ],
            ])
            ->tags(
                array_merge($this->tags($payload), ['failed'])
            )
            ->setHashFamily(
                $this->hash(
                    Type::QUEUE.
                    'failed'.
                    $event->exception->getFile().
                    $event->exception->getLine().
                    $event->exception->getMessage().
                    date('Y-m-d H')
                )
            )->toArray());
    }

    /**
     * Get the default entry data for the given job.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @return array
     */
    protected function defaultJobData($connection, $queue, array $payload, array $data)
    {
        return [
            'connection' => $connection,
            'queue' => $queue,
            'name' => data_get($payload, 'displayName'),
            'tries' => data_get($payload, 'maxTries'),
            'timeout' => data_get($payload, 'timeout'),
            'data' => $data,
        ];
    }

    /**
     * Extract the job "data" from the job payload.
     *
     * @return array
     */
    protected function data(array $payload)
    {
        if (! isset($payload['data']['command'])) {
            return data_get($payload, 'data', []);
        }

        return ExtractProperties::from(
            $this->getCommand($payload['data'])
        );
    }

    /**
     * Extract the tags from the job payload.
     *
     * @return array
     */
    protected function tags(array $payload)
    {
        if (! isset($payload['data']['command'])) {
            return [];
        }

        return ExtractTags::fromJob(
            $this->getCommand($payload['data'])
        );
    }

    /**
     * Get the command from the given payload.
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    protected function getCommand(array $data)
    {
        if (Str::startsWith(data_get($data, 'command'), 'O:')) {
            return unserialize($data['command']);
        }

        if (app()->bound(Encrypter::class)) {
            return unserialize(app(Encrypter::class)->decrypt(data_get($data, 'command')));
        }

        throw new RuntimeException('Unable to extract job payload.');
    }
}
