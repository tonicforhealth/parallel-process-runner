<?php

namespace Tonic\ParallelProcessRunner\Event;

/**
 * Class ProcessBeforeStartEvent.
 *
 * @author kandelyabre <kandelyabre@gmail.com>
 */
class ProcessBeforeStartEvent extends AbstractProcessEvent
{
    const EVENT_NAME = 'parallel_process_runner.process.start.before';
}
