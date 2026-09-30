OT1 capacity alert.

Signal:    {{ $key }}
Value:     {{ $value }}
Threshold: {{ $threshold ?? '—' }}

{{ $message }}

Host:      {{ $host }}
At:        {{ $now }}

Next alert for the same signal in ≥60 min unless it clears + re-trips.

See docs/OT1_LIMITS.md §7 (alert plan) and §11 (load-management phases).
