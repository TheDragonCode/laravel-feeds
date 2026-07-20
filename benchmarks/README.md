# Export benchmark

Run the end-to-end JSON Lines export benchmark with its default 100,000 rows, 10 fields, and three iterations:

```bash
composer benchmark
```

Override the workload when checking a local change:

```bash
composer benchmark -- --rows=200000 --iterations=5
```

The command reports elapsed time, rows per second, output bytes, and peak memory for each run and the median throughput.
