<?php

namespace Tonic\ParallelProcessRunner\Exception;

use Symfony\Component\Process\Process;

/**
 * Class ProcessAlreadyInCollectionException.
 *
 * @author kandelyabre <kandelyabre@gmail.com>
 */
class ProcessAlreadyInCollectionException extends AbstractProcessException
{
    /**
     * @var Process
     */
    private $process;

    /**
     * ProcessAlreadyInCollectionException constructor.
     *
     * @param Process $process
     */
    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    /**
     * @return Process
     */
    public function getProcess()
    {
        return $this->process;
    }
}
