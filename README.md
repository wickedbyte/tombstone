# Tombstone

This library includes a variety of ways handling tombstone hits in your codebase,
including handlers that trigger PHP errors (e.g. `E_USER_DEPRECATED`), use the
local filesystem to track tombstone hits, and drop-in integrations for your project's
existing PSR-3 Logger, PSR-14 Event Dispatcher, PSR-6/PSR-16 Cache implementations.

## Installation & Configuration

Install as a non-development dependency with Composer:

```shell
composer require wickedbyte/tombstone
```

Somewhere in your project's bootstrap process or service container (e.g. via a
service provider), configure a new `GraveyardConfiguration` instance, and pass
it to the `Graveyard::config()` method. For example:

```php
    use WickedByte\Tombstone\Graveyard;
    use WickedByte\Tombstone\GraveyardConfiguration;

    Graveyard::config(new GraveyardConfiguration(handlers: [
        new \WickedByte\Tombstone\Handlers\InMemoryRateLimitHandler(),
        new \WickedByte\Tombstone\Handlers\PhpErrorHandler(),
        // Add additional handlers for your project here...
    ]));
```

If, for some reason, a tombstone is hit before the configuration is set, the library
will default to a fallback configuration that will log the tombstone hit as a PHP
`E_USER_DEPRECATED` error.

The `GraveyardConfiguration` class accepts the following named arguments:

- `rethrow_exceptions`: A boolean indicating whether exceptions that occur
  while handling the `TombstoneActivated` event should be rethrown or suppressed.
  This can be useful for debugging, but should be disabled in production.
  The default is `false`.


- `trace_depth`: The number of stack frames to include in the tombstone's
  backtrace. This can be used to reduce the size of the tombstone log output.
  The default is `10`. Setting the value to `0` will use the full backtrace stack trace.


- `logger`: A optional PSR-3 logger instance that will be used to for logging
  exceptions that occur while handling the `TombstoneActivated` event as errors.
  Note: this logger is separate from the logger used by the PSR-3 tombstone handlers,
  though you can certainly pass the same `LoggerInterface` instance to both.


- `handlers`: An array of `TombstoneHandlerInterface` instances that will be
  called when a tombstone is hit. The handlers will be called in the order
  they are defined in the array. If a handler sets the `TombstoneActivated`
  instance's `$propagate` property to `false`, no further handlers will be
  invoked. This can be used to prevent spamming logs or other side effects.
  The defaults are `InMemoryRateLimitHandler` and `PhpErrorHandler`.

## Usage

To mark a location in your code as a tombstone, use the `tombstone` function:

```php
    \tombstone('2021-01-01 This code is *probably* dead');
```

Alternatively, you can use the `Graveyard::tombstone()` static method directly:

```php
    \WickedByte\Tombstone\Graveyard::tombstone('2021-01-01 This code is *probably* dead');
```

Both methods accept an arbitrary string message argument. The message should be
brief, and may include a date or other information about when or why the code
tombstone was added. The message will be logged when the tombstone is hit, and is
used as part of the tombstone's unique identifier. This means that if you can add
more granularity by making the message string dynamic.

The second parameter, `$extra`, is an optional array of additional context information
that can be used by your custom helper implementations. For example, if you add a
handler that sends an email notification, you can use the `$extra` array to include
the email address of the person who added the tombstone and the recipient address.

## Tombstone Handlers

### Included Tombstone Handlers

#### `InMemoryRateLimitHandler`

While code that is assumed to already be dead will _probably_ not be hit frequently,
it's better to be safe than sorry, especially when paying for production log storage.

To spamming logs and other side effects when a tombstone is hit in a loop or
at high-frequency, this handler will limit the number of tombstone hits that are logged to
one-per-request. More accurately -- important for non-HTTP contexts, like
queue workers and cron-jobs -- one-per-instantiation of the configured
handler class. The underlying array is a key/value pairing of the tombstone's
8-byte identifier and the value `true`, in order to minimize memory usage.

#### `PhpErrorHandler`

This handler will trigger a PHP error when a tombstone is hit, allowing your
project's error handling system to log the tombstone hit as it would any other
PHP error. By default, the error level is `E_USER_DEPRECATED`, but the other two
runtime user error levels (`E_USER_ERROR`, `E_USER_WARNING` and `E_USER_NOTICE`)
are also supported.

#### `FilesystemGraveyardHandler`

This handler uses the local filesystem to track tombstone hits. Provide it with
a directory path (it can create the directory if it does not exist) and tombstone
activations will be logged to a file in that directory, one per tombstone ID.
You will need to manually clear the directory if you want to reset the tombstone.
We only touch the file if it does not already exist.

#### `PsrLoggerHandler`

This handler will log tombstone hits to a PSR-3 logger instance, e.g. Monolog.
The default log level is `warning`, but can be configured with any of the PSR-3
log levels. The handler will log additional context information about the tombstone
hit, including the tombstone's message, the stack trace, and contextual information
for the current request.

#### `PsrEventDispatcherHandler`

This handler integrates with your existing PSR-14 Event Dispatcher, and dispatches
the `TombstoneActivated` event. This allows you to add additional listeners to the
event, that do not have to implement the `TombstoneHandlerInterface` interface, and
are defined along with the rest of your project's event listeners.

#### `PsrCacheHandler` / `PsrSimpleCacheHandler`

These handlers will cache tombstone hits using a PSR-6 or PSR-16 cache instance.
If the cache implementation uses some kind of remote driver like Redis or Memcached,
this can be useful for tracking tombstone hits across multiple servers or processes.
The cache key is the tombstone's unique identifier, and the value is either the
`TombstoneActivated` instance or `1`. The latter is much more memory and bandwidth
efficient, but does not include the tombstone's message or stack trace, which might
be useful for debugging, just how a tombstone was hit, and by whom.

The default cache TTL is `86400` seconds (24 hours), but can be configured to any
non-negative integer value. If the TTL is set to `0`, the cache will never expire
-- you probably do not want that, though it is technically allowed.

####

### Creating Custom Tombstone Handlers

To create a custom handler, implement the `TombstoneHandlerInterface` interface:

```php
    use WickedByte\Tombstone\TombstoneActivated;

    interface TombstoneHandlerInterface
    {
        public function handle(TombstoneActivated $tombstone): void;
    }
```

Then add an instance of your custom handler to the `GraveyardConfiguration` instance.
Handlers can be dynamically added or reset at runtime by calling the
`Graveyard::config()->pushHandlers()` or `Graveyard::config()->setHandlers()` methods.

If you want to stop the propagation of the tombstone event to other handlers, set the
`$propagate` property of the passed in `TombstoneActivated` instance to `false`.

## Contributing

Run `make` to build the project Docker image, create the "./build" cache directory
and install vendor dependencies with Composer.

To upgrade the project dependencies to their current major versions, run `make upgrade`, or
to just update them within the bounds of their currently defined constraints, run `make update`

Common Actions with Makefile targets:

- `make bash`
- `make phpunit`
- `make psysh`
- `make phpcs`
- `make phpstan`
- `make rector`
- `make rector-dry-run`
- `make ci`

To get a fresh start, run `make clean` to delete the vendor and build directories,
which will trigger a docker image rebuild, the next time `make` is run.

For anything else not defined in the Makefile, use:

```shell
docker compose run --rm -it app {your-command-here}
```
