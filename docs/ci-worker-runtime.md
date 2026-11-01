# Isolated PHP workers on Windows and Linux

The concurrency suites intentionally use `php -n` to avoid inheriting local
debugger/INI settings. Ubuntu's PDO extension is shared, unlike the Windows
runtime where PDO is built in. Loading only `pdo_sqlite.so` without `pdo.so`
produced `undefined symbol: pdo_dbh_ce` and `Class "PDO" not found` on GitHub
Actions. The workers never reached their readiness barriers, causing 34 failures.

All seven concurrency suites now use `Tests\Support\PhpWorker::command()`.
It loads PDO before its SQLite driver when a shared PDO module exists, followed
by the remaining required extensions. The separate worker probe performs a real
in-memory SQLite write/read and verifies required extensions before CI starts
the full suite. No financial controls or tests are disabled.

Original failure: https://github.com/ziyadusmankhan/NGO-Management-System/actions/runs/41331847203
