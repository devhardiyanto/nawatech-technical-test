# k6 Performance Report - Orders Endpoint

## Test Context

- Endpoint: `GET /api/orders?per_page=20`
- Script: `tests/performance/orders-list.k6.js`
- Database: PostgreSQL (`herd_postgres` on `127.0.0.1:5432`)
- App server: `php artisan serve --host=127.0.0.1 --port=8000`
- k6: `v1.7.1`

## Command Executed

```bash
k6 run tests/performance/orders-list.k6.js -e BASE_URL=http://127.0.0.1:8000 -e VUS=100 -e DURATION=30s
```

## Real Output Summary

- `http_req_failed`: `0.00%` (passed)
- `http_req_duration p(95)`: `1.46s` (failed threshold)
- `http_req_duration avg`: `1.26s`
- `http_reqs`: `2537`
- `iterations`: `2537`
- `checks_succeeded`: `100%` (`7611/7611`)

## Threshold Result

Configured thresholds in script:

- `http_req_failed: rate < 0.01`
- `http_req_duration{endpoint:orders_index}: p(95) < 300ms`

Result:

- `http_req_failed`: ✅ Passed
- `p(95) < 300ms`: ❌ Failed (`p(95)=1.46s`)

## Conclusion

Current implementation is **kept as-is** based on latest rerun result.
At `VUS=100` for `30s`, endpoint reliability is good (`0%` failed requests), but latency target from requirement (`<300ms`) is not yet achieved.
