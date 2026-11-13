# Performance measurements

Measured once per collection size on the disposable local WordPress 7.1.4 / PHP 8.3.36 / MariaDB 11.4 site, with the free companion stack active. These are observations, not production guarantees or statistical benchmarks. Query counts measure the CodeSlot location call, not the entire WordPress page load. The first call includes cold context setup; subsequent cases share warmed WordPress metadata/object caches within the benchmark process.

| Collection | Location call | Queries | Memory delta |
|---|---:|---:|---:|
| 100 inactive snippets | 2.336 ms | 5 | 49,600 bytes |
| 1,000 inactive snippets | 1.188 ms | 1 | 40,024 bytes |
| 100 active nonmatching snippets at one location | 6.038 ms | 3 | 551,688 bytes |
| 1,000 active nonmatching snippets at one location | 70.512 ms | 3 | 4,916,944 bytes |

Command: `docker exec codeslot-wp wp eval-file wp-content/plugins/codeslot/tests/benchmark.php`. The benchmark creates and removes its fixtures. It does not time arbitrary user PHP or actual snippet output generation, database cold-starts, concurrent load, real visitor traffic, or persistent-cache deployments.

Queries select published snippets only at the requested location; inactive collections are not loaded into execution. Matching candidates are cached for that request; no result cache persists across users. CodeSlot uses native metadata APIs and loads no frontend assets. Administration lists paginate at 30. Exports are explicit privileged operations and can load all snippets; imports are bounded to 200 entries / 5 MiB. Large active collections at one location still cost memory/condition evaluation, as measured above. There is no claim of constant-time execution for thousands of active snippets.
