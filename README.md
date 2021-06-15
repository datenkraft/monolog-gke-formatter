# Monolog extension for Google Cloud logging formatter

This library can re-format json log to Google Kubernetes Engine format 

## Installation

```
composer require datenkraft/monolog-gke-formatter
```

## Usage

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Datenkraft\MonologGkeFormatter\GkeFormatter;

$handler = new StreamHandler('php://stdout');
$handler->setFormatter(new GkeFormatter());
```

## Credits

Forked from https://github.com/MacPaw/monolog-gke-formatter
Thank you!
