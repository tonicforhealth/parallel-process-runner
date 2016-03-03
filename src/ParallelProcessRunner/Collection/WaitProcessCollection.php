<?php

namespace Tonic\ParallelProcessRunner\Collection;

use Symfony\Component\Process\Process;
use Tonic\ParallelProcessRunner\Exception\ProcessesMustBeInReadyStatusException;

/**
 * Class WaitProcessCollection.
 *
 * @author kandelyabre <kandelyabre@gmail.com>
 */
class WaitProcessCollection extends ProcessCollection
{
    /**
     * {@inheritdoc}
     *
     * @param Process|Process[]|ProcessCollection|array $process
     *
     * @return $this
     *
     * @throws ProcessesMustBeInReadyStatusException
     */
    public function add($process)
    {
        switch (true) {
            case is_array($process):
                $this->addProcessesArray($process);
                break;
            case $process instanceof ProcessCollection:
                $this->addProcessesArray($process->toArray());
                break;
            case $process instanceof Process:
                $this->verifyProcess($process);
                parent::add($process);
        }

        return $this;
    }

    /**
     * @param array $processes
     *
     * @return array
     */
    protected function addProcessesArray(array $processes)
    {
        return array_map(function ($process) {
            return $this->add($process);
        }, $processes);
    }

    /**
     * @param Process $process
     *
     * @throws ProcessesMustBeInReadyStatusException
     * @throws \Tonic\ParallelProcessRunner\Exception\NotProcessException
     * @throws \Tonic\ParallelProcessRunner\Exception\ProcessAlreadyInCollectionException
     */
    protected function verifyProcess(Process $process)
    {
        if ($process->getStatus() != Process::STATUS_READY) {
            throw new ProcessesMustBeInReadyStatusException($process);
        }
    }
}
