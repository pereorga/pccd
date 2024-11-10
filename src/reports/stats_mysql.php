<?php

/**
 * This file is part of PCCD.
 *
 * (c) Pere Orga Esteve <pere@orga.cat>
 * (c) Víctor Pàmies i Riudor <vpamies@gmail.com>
 *
 * This source file is subject to the AGPL license that is bundled with this
 * source code in the file LICENSE.
 */

function stats_mysql(): void
{
    require_once __DIR__ . '/../common.php';

    echo '<h2>MariaDB Statistics</h2>';

    echo '<h3>InnoDB Buffer Pool Stats</h3>';
    $bufferPoolStats = get_db()->query("SHOW GLOBAL STATUS LIKE 'Innodb_buffer_pool%';")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($bufferPoolStats as $stat) {
        echo "{$stat['Variable_name']}: {$stat['Value']}<br>";
    }

    echo '<h3>Query Cache Stats</h3>';
    $queryCacheStats = get_db()->query("SHOW GLOBAL STATUS LIKE 'Qcache%';")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($queryCacheStats as $stat) {
        echo "{$stat['Variable_name']}: {$stat['Value']}<br>";
    }

    echo '<h3>Performance Metrics</h3>';
    $slowQueries = get_db()->query("SHOW GLOBAL STATUS LIKE 'Slow_queries';")->fetch(PDO::FETCH_ASSOC);
    assert(is_array($slowQueries) && is_string($slowQueries['Value']));
    echo "Slow Queries: {$slowQueries['Value']}<br>";

    echo '<h3>Thread Statistics</h3>';
    $threadStats = get_db()->query("SHOW GLOBAL STATUS LIKE 'Threads%';")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($threadStats as $stat) {
        echo "{$stat['Variable_name']}: {$stat['Value']}<br>";
    }

    echo '<h3>Table Cache</h3>';
    $tableCache = get_db()->query("SHOW GLOBAL STATUS LIKE 'Open_tables';")->fetch(PDO::FETCH_ASSOC);
    assert(is_array($tableCache) && is_string($tableCache['Value']));
    echo "Open Tables: {$tableCache['Value']}<br>";

    $records = get_db()->query("
        SELECT
            table_name,
            ROUND((data_length + index_length) / 1024 / 1024, 2) AS size_mb,
            table_rows
        FROM
            information_schema.tables
        WHERE
            table_schema = 'pccd'
        ORDER BY
            size_mb DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo '<h3>Table Sizes</h3>';
    foreach ($records as $r) {
        echo "Table {$r['table_name']} size: {$r['size_mb']}MB<br>";
    }
    echo '<h3>Row Counts</h3>';
    foreach ($records as $r) {
        echo "Table {$r['table_name']} rows: {$r['table_rows']}<br>";
    }

    echo '<h3>Database Summary</h3>';
    $total_size_mb = get_db()->query("SELECT
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2)
    FROM
        information_schema.tables
    WHERE
        table_schema = 'pccd'
    GROUP BY
        table_schema")->fetchColumn();
    echo "Total Database size: {$total_size_mb} MB<br>";

    $tablesWithoutPK = get_db()->query("
            SELECT
                t.table_name
            FROM
                information_schema.tables t
                LEFT JOIN information_schema.table_constraints c
                    ON t.table_name = c.table_name
                    AND c.constraint_type = 'PRIMARY KEY'
            WHERE
                t.table_schema = 'pccd'
                AND c.constraint_name IS NULL
            ORDER BY
                t.table_name
        ")->fetchAll(PDO::FETCH_ASSOC);

    echo '<br>Tables without primary keys:<br>';
    foreach ($tablesWithoutPK as $table) {
        echo "- {$table['table_name']}<br>";
    }
}
