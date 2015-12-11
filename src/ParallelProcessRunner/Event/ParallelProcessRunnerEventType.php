<?php

namespace Tonic\ParallelProcessRunner\Event;

/**
 * Class ParallelProcessRunnerEventType.
 *
 * @author kandelyabre <kandelyabre@gmail.com>
 */
class ParallelProcessRunnerEventType
{
    const PROCESS_STOP_AFTER = 'parallel_process_runner.process.stop.after';

    const PROCESS_START_BEFORE = 'parallel_process_runner.process.start.before';

    const PROCESS_OUT = 'parallel_process_runner.process.out';
}
