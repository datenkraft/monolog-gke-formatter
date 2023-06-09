<?php

declare(strict_types=1);

namespace Datenkraft\MonologGkeFormatter;

use GuzzleHttp\Psr7\ServerRequest;
use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;
use RuntimeException;

class GkeFormatter extends JsonFormatter
{
    public const BACKTRACE_DEFAULT_CALL = 6;

    protected int $deepToBacktrace;
    protected bool $httpRequestContext;
    protected bool $sourceLocationContext;

    /**
     * @param self::BATCH_MODE_* $batchMode
     *
     * @throws RuntimeException If the function json_encode does not exist
     */
    public function __construct(
        int $batchMode = self::BATCH_MODE_JSON,
        bool $appendNewline = true,
        bool $ignoreEmptyContextAndExtra = false,
        bool $httpRequestContext = false,
        bool $sourceLocationContext = false,
        int $deepToBacktrace = self::BACKTRACE_DEFAULT_CALL
    ) {
        parent::__construct($batchMode, $appendNewline, $ignoreEmptyContextAndExtra);
        $this->httpRequestContext = $httpRequestContext;
        $this->sourceLocationContext = $sourceLocationContext;
        $this->deepToBacktrace = $deepToBacktrace;

        $this->includeStacktraces();
    }

    /**
     * @inheritDoc
     */
    public function format(LogRecord $record): string
    {
        $request = ServerRequest::fromGlobals();
        $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $this->deepToBacktrace);
        $context = array_merge(
            $this->sourceLocationContext && isset($debug[$this->deepToBacktrace - 2])
                ? [
                'sourceLocation' => [
                    'file' => $debug[$this->deepToBacktrace - 2]['file'],
                    'line' => $debug[$this->deepToBacktrace - 2]['line'],
                    'function' => isset($debug[$this->deepToBacktrace - 1]['function']) && isset($debug[$this->deepToBacktrace - 1]['class'])
                        ? $debug[$this->deepToBacktrace - 1]['class'] . $debug[$this->deepToBacktrace - 1]['type'] . $debug[$this->deepToBacktrace - 1]['function']
                        : (
                            $debug[$this->deepToBacktrace - 1]['function'] ?? ''
                        ),
                ]
            ]
                : [],
            $this->httpRequestContext && str_contains(php_sapi_name(), 'cgi')
                ? [
                'httpRequest' => [
                    'requestMethod' => $request->getMethod(),
                    'requestUrl' => $request->getUri()->__toString(),
                    'requestSize' => $request->getBody()->getSize(),
                    'protocol' => $request->getProtocolVersion(),
                    'referer' => $request->getHeaderLine('Referer'),
                    'userAgent' => $request->getHeaderLine('User-Agent'),
                    'remoteIp' => $request->getHeaderLine('X-Forwarded-For'),
                ],
            ]
                : [],
            [
                'message' => $record['message'],
                'thread' => $record['channel'],
                'severity' => $record['level_name'],
                'serviceContext' => $record['context'],
                'timestamp' => $record['datetime']->getTimestamp(),
            ]
        );

        return parent::format(
            (new LogRecord(
                $record->datetime,
                $record->channel,
                $record->level,
                $record->message,
                $context,
                $record->extra,
                $record->formatted
            ))
        );
    }
}
