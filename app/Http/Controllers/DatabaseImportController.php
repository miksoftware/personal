<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatabaseImportController extends Controller
{
    /**
     * Only INSERT statements targeting these tables will be executed.
     * Everything else (migrations, users, cache, jobs, sessions…) is skipped.
     */
    private const ALLOWED_TABLES = [
        'clients',
        'licenses',
        'developments',
        'payments',
    ];

    public function index()
    {
        return view('db-import.index', [
            'allowedTables' => self::ALLOWED_TABLES,
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'sql_file' => ['required', 'file', 'mimetypes:application/sql,application/octet-stream,text/plain,text/x-sql', 'max:51200'],
        ], [
            'sql_file.required' => 'Debes seleccionar un archivo SQL.',
            'sql_file.max'      => 'El archivo no puede superar los 50 MB.',
        ]);

        $file = $request->file('sql_file');

        if (strtolower($file->getClientOriginalExtension()) !== 'sql') {
            return back()->withErrors(['sql_file' => 'Solo se permiten archivos con extensión .sql.']);
        }

        $sql = file_get_contents($file->getRealPath());
        if ($sql === false) {
            return back()->withErrors(['sql_file' => 'No se pudo leer el archivo.']);
        }

        $allStatements = $this->splitSql($sql);

        // Separate statements into: allowed inserts, skipped inserts, other (non-insert)
        $toExecute = [];
        $skipped   = [];

        foreach ($allStatements as $stmt) {
            if (!preg_match('/^\s*INSERT\s+(?:IGNORE\s+)?INTO\s+/i', $stmt)) {
                continue; // not an INSERT — ignore silently
            }

            $table = $this->extractTableName($stmt);

            if ($table === null || !in_array($table, self::ALLOWED_TABLES, true)) {
                $skipped[] = $table ?? '(desconocida)';
                continue;
            }

            $toExecute[] = $stmt;
        }

        if (empty($toExecute)) {
            return back()->withErrors([
                'sql_file' => 'No se encontraron sentencias INSERT para las tablas permitidas ('
                    . implode(', ', self::ALLOWED_TABLES) . ').',
            ]);
        }

        $executed = 0;
        $errors   = [];

        DB::statement('SET foreign_key_checks = 0');

        foreach ($toExecute as $i => $stmt) {
            try {
                DB::unprepared($stmt);
                $executed++;
            } catch (\Throwable $e) {
                $errors[] = 'Sentencia #' . ($i + 1) . ': ' . $e->getMessage();
            }
        }

        DB::statement('SET foreign_key_checks = 1');

        return back()->with([
            'import_done'     => true,
            'import_executed' => $executed,
            'import_skipped'  => array_unique($skipped),
            'import_errors'   => $errors,
            'import_filename' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * Extract the table name from an INSERT INTO statement.
     * Handles backtick, double-quote, single-quote, or bare identifiers.
     */
    private function extractTableName(string $stmt): ?string
    {
        if (preg_match('/^\s*INSERT\s+(?:IGNORE\s+)?INTO\s+[`"\']?(\w+)[`"\']?\s*/i', $stmt, $m)) {
            return strtolower($m[1]);
        }
        return null;
    }

    /**
     * Split a SQL file into individual statements, respecting string literals.
     */
    private function splitSql(string $sql): array
    {
        $statements = [];
        $current    = '';
        $inString   = false;
        $stringChar = '';
        $len        = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];

            if ($inString) {
                $current .= $char;
                if ($char === '\\') {
                    if ($i + 1 < $len) {
                        $current .= $sql[++$i];
                    }
                } elseif ($char === $stringChar) {
                    if ($i + 1 < $len && $sql[$i + 1] === $stringChar) {
                        $current .= $sql[++$i];
                    } else {
                        $inString = false;
                    }
                }
            } else {
                if ($char === '\'' || $char === '"' || $char === '`') {
                    $inString   = true;
                    $stringChar = $char;
                    $current   .= $char;
                } elseif ($char === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
                    while ($i < $len && $sql[$i] !== "\n") {
                        $i++;
                    }
                } elseif ($char === '/' && $i + 1 < $len && $sql[$i + 1] === '*') {
                    $i += 2;
                    while ($i + 1 < $len && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                        $i++;
                    }
                    $i += 2;
                } elseif ($char === ';') {
                    $trimmed = trim($current);
                    if ($trimmed !== '') {
                        $statements[] = $trimmed;
                    }
                    $current = '';
                } else {
                    $current .= $char;
                }
            }
        }

        $trimmed = trim($current);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }
}

    public function index()
    {
        return view('db-import.index');
    }

    public function import(Request $request)
    {
        $request->validate([
            'sql_file' => ['required', 'file', 'mimetypes:application/sql,application/octet-stream,text/plain,text/x-sql', 'max:51200'],
        ], [
            'sql_file.required' => 'Debes seleccionar un archivo SQL.',
            'sql_file.max'      => 'El archivo no puede superar los 50 MB.',
        ]);

        $file = $request->file('sql_file');

        // Extra extension check (MIME can be unreliable for .sql)
        if (strtolower($file->getClientOriginalExtension()) !== 'sql') {
            return back()->withErrors(['sql_file' => 'Solo se permiten archivos con extensión .sql.']);
        }

        $sql = file_get_contents($file->getRealPath());

        if ($sql === false) {
            return back()->withErrors(['sql_file' => 'No se pudo leer el archivo.']);
        }

        // Split on semicolons that are not inside string literals.
        // We use a simple splitter and then filter to INSERT statements only.
        $rawStatements = $this->splitSql($sql);

        $insertStatements = array_values(array_filter(
            $rawStatements,
            fn($s) => preg_match('/^\s*INSERT\s+INTO\s+/i', $s)
        ));

        if (empty($insertStatements)) {
            return back()->withErrors(['sql_file' => 'El archivo no contiene sentencias INSERT INTO. Solo se procesan inserts de datos.']);
        }

        $executed  = 0;
        $errors    = [];

        try {
            DB::transaction(function () use ($insertStatements, &$executed, &$errors) {
                // Disable FK checks during import so order of inserts doesn't matter
                DB::statement('SET foreign_key_checks = 0');

                foreach ($insertStatements as $i => $stmt) {
                    try {
                        DB::unprepared($stmt);
                        $executed++;
                    } catch (\Throwable $e) {
                        // Collect up to 20 errors, then abort
                        $errors[] = 'Sentencia #' . ($i + 1) . ': ' . $e->getMessage();
                        if (count($errors) >= 20) {
                            throw new \RuntimeException('Se alcanzó el límite de errores. Importación cancelada.');
                        }
                    }
                }

                DB::statement('SET foreign_key_checks = 1');

                // If any errors occurred, roll back the entire import
                if (!empty($errors)) {
                    throw new \RuntimeException('Se encontraron errores durante la importación.');
                }
            });
        } catch (\RuntimeException $e) {
            return back()->with([
                'import_errors'   => $errors,
                'import_executed' => $executed,
                'import_failed'   => true,
                'import_message'  => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            return back()->withErrors(['sql_file' => 'Error inesperado: ' . $e->getMessage()]);
        }

        return back()->with([
            'import_success'  => true,
            'import_executed' => $executed,
            'import_filename' => $file->getClientOriginalName(),
        ]);
    }

    /**
     * Split a SQL file into individual statements.
     * Handles string literals so semicolons inside quotes are not treated as delimiters.
     */
    private function splitSql(string $sql): array
    {
        $statements = [];
        $current    = '';
        $inString   = false;
        $stringChar = '';
        $len        = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];

            if ($inString) {
                $current .= $char;
                // Handle escape sequences inside strings
                if ($char === '\\') {
                    if ($i + 1 < $len) {
                        $current .= $sql[++$i];
                    }
                } elseif ($char === $stringChar) {
                    // Check for doubled quote (MySQL escape: '' or "")
                    if ($i + 1 < $len && $sql[$i + 1] === $stringChar) {
                        $current .= $sql[++$i];
                    } else {
                        $inString = false;
                    }
                }
            } else {
                if ($char === '\'' || $char === '"' || $char === '`') {
                    $inString   = true;
                    $stringChar = $char;
                    $current   .= $char;
                } elseif ($char === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
                    // Skip single-line comment
                    while ($i < $len && $sql[$i] !== "\n") {
                        $i++;
                    }
                } elseif ($char === '/' && $i + 1 < $len && $sql[$i + 1] === '*') {
                    // Skip block comment
                    $i += 2;
                    while ($i + 1 < $len && !($sql[$i] === '*' && $sql[$i + 1] === '/')) {
                        $i++;
                    }
                    $i += 2; // skip closing */
                } elseif ($char === ';') {
                    $trimmed = trim($current);
                    if ($trimmed !== '') {
                        $statements[] = $trimmed;
                    }
                    $current = '';
                } else {
                    $current .= $char;
                }
            }
        }

        // Catch any trailing statement without a semicolon
        $trimmed = trim($current);
        if ($trimmed !== '') {
            $statements[] = $trimmed;
        }

        return $statements;
    }
}
