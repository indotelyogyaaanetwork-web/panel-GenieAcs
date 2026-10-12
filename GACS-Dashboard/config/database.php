<?php
declare(strict_types=1);
function db(): mysqli {
    static $c = null;
    if ($c === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $c = new mysqli(env('DB_HOST', 'localhost'), env('DB_USER'), env('DB_PASS'), env('DB_NAME'), (int)env('DB_PORT', '3306'));
        $c->set_charset('utf8mb4');
    }
    return $c;
}
/** Prepared statement; semua parameter di-bind sebagai string (MySQL mengonversi otomatis). */
function q(string $sql, array $p = []): mysqli_stmt {
    $s = db()->prepare($sql);
    if ($p) $s->bind_param(str_repeat('s', count($p)), ...array_values($p));
    $s->execute();
    return $s;
}
function rows(string $sql, array $p = []): array { return q($sql, $p)->get_result()->fetch_all(MYSQLI_ASSOC); }
