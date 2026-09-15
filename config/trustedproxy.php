<?php

$proxies = trim((string) env('TRUSTED_PROXIES', '*'));

return [
    /*
    | Laravel 10 parity defaults to trusting every upstream proxy. Production
    | deployments must provide the actual proxy IP addresses or CIDR ranges.
    */
    'proxies' => in_array($proxies, ['*', '**'], true)
        ? $proxies
        : array_values(array_filter(array_map(trim(...), explode(',', $proxies)))),
];
