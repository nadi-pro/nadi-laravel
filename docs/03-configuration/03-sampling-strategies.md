# Sampling Strategies

Control the rate of data collection to manage volume and costs.

## Available Strategies

### Fixed Rate Sampling

Samples a fixed percentage of events.

```env
NADI_SAMPLING_STRATEGY=fixed_rate
NADI_SAMPLING_RATE=0.1  # 10% of events
```

### Dynamic Rate Sampling

Adjusts sampling rate based on load.

```env
NADI_SAMPLING_STRATEGY=dynamic_rate
NADI_SAMPLING_BASE_RATE=0.05
NADI_SAMPLING_LOAD_FACTOR=1.0
```

### Interval Sampling

Samples events at fixed time intervals.

```env
NADI_SAMPLING_STRATEGY=interval
NADI_SAMPLING_INTERVAL_SECONDS=60
```

### Peak Load Sampling

Reduces sampling during high load periods.

```env
NADI_SAMPLING_STRATEGY=peak_load
NADI_SAMPLING_BASE_RATE=0.1
NADI_SAMPLING_LOAD_FACTOR=1.0
```

## Strategy Classes

Custom sampling strategies can be registered in `config/nadi.php`:

```php
'sampling' => [
    'strategies' => [
        'dynamic_rate' => Nadi\Sampling\DynamicRateSampling::class,
        'fixed_rate' => Nadi\Sampling\FixedRateSampling::class,
        'interval' => Nadi\Sampling\IntervalSampling::class,
        'peak_load' => Nadi\Sampling\PeakLoadSampling::class,
        'custom' => App\Sampling\CustomSampling::class,
    ],
]
```

## Recommendations

| Use Case                | Strategy       | Rate           |
| ----------------------- | -------------- | -------------- |
| Development             | `fixed_rate`   | `1.0` (100%)   |
| Low-traffic production  | `fixed_rate`   | `0.5` (50%)    |
| High-traffic production | `dynamic_rate` | Base `0.05`    |
| Cost optimization       | `interval`     | Every 60s      |

## Next Steps

- [Environment Variables](01-environment-variables.md) - All configuration options
