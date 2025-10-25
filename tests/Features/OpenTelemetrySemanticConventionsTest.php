<?php

namespace Nadi\Laravel\Tests\Features;

use Nadi\Laravel\Support\OpenTelemetrySemanticConventions;
use Nadi\Laravel\Tests\TestCase;

class OpenTelemetrySemanticConventionsTest extends TestCase
{
    /** @test */
    public function it_has_laravel_specific_constants()
    {
        // Laravel-specific constants
        $this->assertEquals('laravel.route.name', OpenTelemetrySemanticConventions::LARAVEL_ROUTE_NAME);
        $this->assertEquals('laravel.route.action', OpenTelemetrySemanticConventions::LARAVEL_ROUTE_ACTION);
        $this->assertEquals('laravel.controller', OpenTelemetrySemanticConventions::LARAVEL_CONTROLLER);
        $this->assertEquals('laravel.middleware', OpenTelemetrySemanticConventions::LARAVEL_MIDDLEWARE);
        $this->assertEquals('laravel.job.class', OpenTelemetrySemanticConventions::LARAVEL_JOB_CLASS);
        $this->assertEquals('laravel.job.queue', OpenTelemetrySemanticConventions::LARAVEL_JOB_QUEUE);
    }

    /** @test */
    public function it_inherits_core_constants()
    {
        // Core constants inherited from parent class
        $this->assertEquals('http.method', OpenTelemetrySemanticConventions::HTTP_METHOD);
        $this->assertEquals('http.url', OpenTelemetrySemanticConventions::HTTP_URL);
        $this->assertEquals('http.status_code', OpenTelemetrySemanticConventions::HTTP_STATUS_CODE);

        $this->assertEquals('db.system', OpenTelemetrySemanticConventions::DB_SYSTEM);
        $this->assertEquals('db.statement', OpenTelemetrySemanticConventions::DB_STATEMENT);

        $this->assertEquals('exception.type', OpenTelemetrySemanticConventions::EXCEPTION_TYPE);
        $this->assertEquals('exception.message', OpenTelemetrySemanticConventions::EXCEPTION_MESSAGE);

        $this->assertEquals('service.name', OpenTelemetrySemanticConventions::SERVICE_NAME);
        $this->assertEquals('user.id', OpenTelemetrySemanticConventions::USER_ID);
    }

    /** @test */
    public function it_delegates_exception_attributes_to_parent()
    {
        $exception = new \RuntimeException('Test exception', 500);

        $attributes = OpenTelemetrySemanticConventions::exceptionAttributes($exception);

        $this->assertIsArray($attributes);
        $this->assertEquals(\RuntimeException::class, $attributes[OpenTelemetrySemanticConventions::EXCEPTION_TYPE]);
        $this->assertEquals('Test exception', $attributes[OpenTelemetrySemanticConventions::EXCEPTION_MESSAGE]);
        $this->assertArrayHasKey(OpenTelemetrySemanticConventions::CODE_FILEPATH, $attributes);
        $this->assertArrayHasKey(OpenTelemetrySemanticConventions::CODE_LINENO, $attributes);
    }

    /** @test */
    public function it_delegates_performance_attributes_to_parent()
    {
        $startTime = microtime(true) - 0.1; // 100ms ago
        $memoryPeak = 1024 * 1024; // 1MB

        $attributes = OpenTelemetrySemanticConventions::performanceAttributes($startTime, $memoryPeak);

        $this->assertIsArray($attributes);
        $this->assertArrayHasKey(OpenTelemetrySemanticConventions::DURATION, $attributes);
        $this->assertArrayHasKey(OpenTelemetrySemanticConventions::MEMORY_USAGE, $attributes);
        $this->assertGreaterThan(90, $attributes[OpenTelemetrySemanticConventions::DURATION]); // At least 90ms
        $this->assertEquals($memoryPeak, $attributes[OpenTelemetrySemanticConventions::MEMORY_USAGE]);
    }

    /** @test */
    public function it_provides_laravel_specific_helper_methods()
    {
        // Test Laravel job attributes
        $jobAttributes = OpenTelemetrySemanticConventions::jobAttributes('App\\Jobs\\ProcessOrder', 'high');
        $this->assertEquals('App\\Jobs\\ProcessOrder', $jobAttributes[OpenTelemetrySemanticConventions::LARAVEL_JOB_CLASS]);
        $this->assertEquals('high', $jobAttributes[OpenTelemetrySemanticConventions::LARAVEL_JOB_QUEUE]);

        // Test Laravel notification attributes
        $notificationAttributes = OpenTelemetrySemanticConventions::notificationAttributes('App\\Notifications\\Welcome', 'mail');
        $this->assertEquals('App\\Notifications\\Welcome', $notificationAttributes[OpenTelemetrySemanticConventions::LARAVEL_NOTIFICATION_CLASS]);
        $this->assertEquals('mail', $notificationAttributes[OpenTelemetrySemanticConventions::LARAVEL_NOTIFICATION_CHANNEL]);

        // Test Laravel command attributes
        $commandAttributes = OpenTelemetrySemanticConventions::commandAttributes('app:process-data');
        $this->assertEquals('app:process-data', $commandAttributes[OpenTelemetrySemanticConventions::LARAVEL_ARTISAN_COMMAND]);
    }
}
