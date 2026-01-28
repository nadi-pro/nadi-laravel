# Query Handler

Monitors slow database queries.

## Event

Listens to: `Illuminate\Database\Events\QueryExecuted`

## What's Captured

- SQL query with bindings replaced
- Execution time in milliseconds
- Database connection name
- Query location in code

## Threshold Configuration

Only queries exceeding the threshold are captured:

```env
NADI_QUERY_SLOW_THRESHOLD=500  # milliseconds
```

Default: 500ms (half second)

## Binding Replacement

Query placeholders are replaced with actual values:

```sql
-- Before
SELECT * FROM users WHERE id = ?

-- After (in captured data)
SELECT * FROM users WHERE id = 123
```

String values are properly quoted and escaped.

## Tags

Slow queries receive the `slow` tag for easy filtering.

## Configuration

```php
// config/nadi.php
'query' => [
    'slow-threshold' => env('NADI_QUERY_SLOW_THRESHOLD', 500),
],
```

## Next Steps

- [Job Handler](04-jobs.md) - Failed job monitoring
