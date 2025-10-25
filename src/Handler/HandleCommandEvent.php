<?php

namespace Nadi\Laravel\Handler;

use Illuminate\Console\Events\CommandFinished;
use Nadi\Data\Type;
use Nadi\Laravel\Data\Entry;
use Nadi\Laravel\Support\OpenTelemetrySemanticConventions;
use Symfony\Component\Console\Command\Command;

class HandleCommandEvent extends Base
{
    public function handle(CommandFinished $event)
    {
        if ($this->shouldIgnore($event)) {
            return;
        }

        $command = $event->command ?? $event->input->getArguments()['command'] ?? 'default';
        $exitCode = $event->exitCode;

        // Generate OpenTelemetry semantic convention attributes
        $otelAttributes = OpenTelemetrySemanticConventions::commandAttributes($command);

        // Add user context if available
        $userAttributes = OpenTelemetrySemanticConventions::userAttributes();

        // Add session context if available
        $sessionAttributes = OpenTelemetrySemanticConventions::sessionAttributes();

        // Merge all OpenTelemetry attributes
        $otelData = array_merge($otelAttributes, $userAttributes, $sessionAttributes);

        $entryData = [
            'command' => $command,
            'exit_code' => $exitCode,
            'arguments' => $event->input->getArguments(),
            'options' => $event->input->getOptions(),
            // Add OpenTelemetry semantic convention data
            'otel' => $otelData,
        ];

        $this->store(Entry::make(Type::COMMAND, $entryData)
            ->setHashFamily($this->hash($command.$exitCode.date('Y-m-d')))
            ->toArray());
    }

    /**
     * Determine if the event should be ignored.
     *
     * @param  mixed  $event
     * @return bool
     */
    private function shouldIgnore($event)
    {
        return $event->exitCode !== Command::FAILURE;
    }
}
