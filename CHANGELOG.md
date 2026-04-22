# 1.0.3 (2026-04-22)

## Added

- **Error handling control**: New execution type system for closure error handling
    - `EXECUTION_TYPE_STOP_ON_ERROR` (default) — preserves existing behavior
    - `EXECUTION_TYPE_CONTINUE_ON_ERROR` — executes all closures; failures collected and thrown as
      [BackgroundProcessingFailedException]
    - `setExecutionType()` / `getExecutionType()` to configure the mode

## Removed

- Method `BackgroundProcessing::reset()` (was documented as internal test utility only)

# 1.0.2 (2023-12-12)

## Changed
- Throw unique exceptions instead of general ones. ( PR #1 by @daniel-glu )

# 1.0.1 (2019-01-14)

## Added
- Allow stopping timing the current transaction before starting processing tasks in the background.

# 1.0.0 (2018-08-14)

## Changed
* Use the Apache-2.0 license.

# 0.0.2 (2018-05-31)

First release that is ready for use.

[BackgroundProcessingFailedException]: https://github.com/Crowdstar/background-processing/blob/1.0.3/src/Exception/BackgroundProcessingFailedException.php
