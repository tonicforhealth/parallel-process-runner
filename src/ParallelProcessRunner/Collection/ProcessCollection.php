<?php

namespace Tonic\ParallelProcessRunner\Collection;

use Symfony\Component\Process\Process;
use Tonic\ParallelProcessRunner\Exception\NotProcessException;
use Tonic\ParallelProcessRunner\Exception\ProcessAlreadyInCollectionException;

/**
 * Class ProcessCollection.
 *
 * @author kandelyabre <kandelyabre@gmail.com>
 */
class ProcessCollection
{
    /**
     * @var Process[]
     */
    private $processes = [];

    /**
     * @param Process $process
     *
     * @throws NotProcessException
     * @throws ProcessAlreadyInCollectionException
     *
     * @return $this
     */
    public function add($process)
    {
        if (!$process instanceof Process) {
            throw new NotProcessException($process);
        }

        $key = $this->getKey($process);
        if (array_key_exists($key, $this->processes)) {
            throw new ProcessAlreadyInCollectionException($process);
        }

        $this->processes[$key] = $process;

        return $this;
    }

    /**
     * @return $this
     */
    public function clear()
    {
        $this->processes = [];

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->processes);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->processes);
    }

    /**
     * @param string   $processStatus
     * @param int|null $limit
     *
     * @see Process::STATUS_STARTED
     * @see Process::STATUS_READY
     * @see Process::STATUS_TERMINATED
     *
     * @return Process[]
     */
    public function spliceByStatus($processStatus, $limit = null)
    {
        $processes = array_slice(
            $this->filterByStatus($processStatus),
            0,
            is_null($limit) ? $this->count(): $limit
        );

        return array_map(function(Process $process){
            return $this->pop($process);
        }, array_values($processes));
    }

    /**
     * @return Process[]
     */
    public function toArray()
    {
        return array_values($this->processes);
    }

    /**
     * @param Process $process
     *
     * @return string
     */
    protected function getKey(Process $process)
    {
        return spl_object_hash($process);
    }

    /**
     * @param string $status
     *
     * @return Process[]
     */
    protected function filterByStatus($status)
    {
        return array_filter($this->processes, function (Process $process) use ($status) {
            return $process->getStatus() == $status;
        });
    }

    /**
     * @param Process $process
     *
     * @return Process
     */
    protected function pop(Process $process)
    {
        unset($this->processes[$this->getKey($process)]);

        return $process;
    }
}
