<?php

class Logger {
    private string $logFile;
    private bool $enabled;

    public function __construct(string $logFile = 'feeder.log', bool $enabled = true) {
        $this->logFile = __DIR__ . '/' . $logFile;
        $this->enabled = $enabled;
    }

    private function write(string $level, string $message, array $context = []): void {
        if (!$this->enabled) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;

        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }

    public function info(string $message, array $context = []): void {
        $this->write('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void {
        $this->write('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void {
        $this->write('WARNING', $message, $context);
    }

    public function debug(string $message, array $context = []): void {
        $this->write('DEBUG', $message, $context);
    }
}
