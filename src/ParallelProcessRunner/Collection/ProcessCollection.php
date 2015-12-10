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
     * @var int
     */
    private $processIndex;

    /**
     * @param Process $process
     *
     * @throws NotProcessException
     * @throws ProcessAlreadyInCollectionException
     *
     * @return int index of last element
     */
    public function add($process)
    {
        if (!$process instanceof Process) {
            throw new NotProcessException($process);
        }

        $key = spl_object_hash($process);
        if (array_key_exists($key, $this->processes)) {
            throw new ProcessAlreadyInCollectionException($process);
        }

        $this->processes[$key] = $process;

        return $this->processIndex++;
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
        $processes = [];

        if (is_null($limit)) {
            $limit = $this->count();
        }

        foreach ($this->processes as $index => $process) {
            if (count($processes) >= $limit) {
                break;
            }

            if ($process->getStatus() == $processStatus) {
                unset($this->processes[$index]);
                $processes[] = $process;
            }
        }

        return $processes;
    }

    /**
     * @return Process[]
     */
    public function toArray()
    {
        return array_values($this->processes);
    }
}
