<?php
class Logger {
    public static function write(string $file, string $level, string $msg, array $ctx = []): void {
        $line = implode(' | ', [
            gmdate('Y-m-d H:i:s') . ' UTC',
            strtoupper($level),
            basename(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'])
              . ':' . debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['line'],
            $msg,
            empty($ctx) ? '' : json_encode($ctx, JSON_UNESCAPED_SLASHES)
        ]);
        file_put_contents(__DIR__ . '/../logs/' . $file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
