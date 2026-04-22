<?php

/**
 * Copyright 2018 Glu Mobile Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace CrowdStar\BackgroundProcessing\Exception;

/**
 * Thrown after all closures have been executed in "continue on error" mode when one or more closures threw exceptions.
 * Contains all collected throwables for inspection.
 */
class BackgroundProcessingFailedException extends \Exception
{
    /**
     * @var \Throwable[]
     */
    private $exceptions;

    /**
     * @param \Throwable[] $exceptions
     */
    public function __construct(array $exceptions)
    {
        $count = count($exceptions);
        parent::__construct(
            "{$count} background processing task(s) failed",
            0,
            $exceptions[0] ?? null
        );

        $this->exceptions = $exceptions;
    }

    /**
     * @return \Throwable[]
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }
}
