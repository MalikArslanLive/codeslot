# Performance measurements

Measured on 15 November 2026, once per collection size on the separate clean WordPress 7.1.4 / PHP 8.3.36 / MariaDB 11.4 install. These are observations, not production guarantees or statistical benchmarks. Query counts measure the CodeSlot location call, not the entire WordPress page load. The first call includes cold context setup; subsequent cases share warmed WordPress metadata/object caches within the benchmark process.

| Collection | Location call | Queries | Memory delta |
|---|---:|---:|---:|
| 100 inactive snippets | 0.957 ms | 1 | 1,840 bytes |
| 1,000 inactive snippets | 0.842 ms | 1 | 952 bytes |
| 100 active nonmatching snippets at one location | 5.712 ms | 3 | 651,488 bytes |
| 1,000 active nonmatching snippets at one location | 48.840 ms | 3 | 6,076,488 bytes |

Command: `docker compose run --rm cli --path=/var/www/html/clean eval-file /var/www/html/wp-content/plugins/codeslot/tests/benchmark.php`. The benchmark creates and removes its fixtures. It does not time arbitrary user PHP or actual snippet output generation, database cold-starts, concurrent load, real visitor traffic, or persistent-cache deployments.

Queries select published snippets only at the requested location; inactive collections are not loaded into execution. Matching candidates are cached for that request; no result cache persists across users. CodeSlot uses native metadata APIs. Optional tracking, selector insertion and static JS/CSS delivery can load frontend assets; ordinary inline slot output needs no CodeSlot frontend controller. Administration lists paginate at 30. Exports are explicit privileged operations and can load all snippets; imports are bounded to 200 entries / 5 MiB. Large active collections at one location still cost memory/condition evaluation, as measured above. There is no claim of constant-time execution for thousands of active snippets.

The benchmark uses a valid nonmatching URL rule, checks every insert result, and cleans its fixtures even on failure. It does not count rejected saves as active snippets.
