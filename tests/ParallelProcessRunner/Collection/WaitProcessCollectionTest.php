<?php

namespace Tonic\ParallelProcessRunner\Collection;

use Symfony\Component\Process\Process;
use Tonic\ParallelProcessRunner\Exception\NotProcessException;
use Tonic\ParallelProcessRunner\Exception\ProcessesMustBeInReadyStatusException;

/**
 * Class WaitProcessCollectionTest.
 *
 * @author kandelyabre <kandelyabre@gmail.com>
 */
class WaitProcessCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function providerAdd()
    {
        return [
            'process' => [
                $this->getProcess(),
                [
                    $this->getProcess(),
                ],
            ],

            'array' => [
                [
                    $this->getProcess(),
                ],
                [
                    $this->getProcess(),
                ],
            ],

            'collection' => [
                $this->getCollection([$this->getProcess()]),
                [
                    $this->getProcess(),
                ],
            ],

            'collection array' => [
                [
                    $this->getCollection([$this->getProcess()]),
                ],
                [
                    $this->getProcess(),
                ],
            ],

            'mixed array' => [
                [
                    $this->getCollection([$this->getProcess()]),
                    [
                        $this->getCollection([$this->getProcess()]),
                    ],
                    $this->getProcess(),
                    [
                        $this->getProcess(),
                    ],
                ],
                [
                    $this->getProcess(),
                    $this->getProcess(),
                    $this->getProcess(),
                    $this->getProcess(),
                ],
            ],
        ];
    }

    /**
     * @param       $mixed
     * @param array $expected
     *
     * @dataProvider providerAdd
     */
    public function testAdd($mixed, array $expected)
    {
        $collection = new WaitProcessCollection();
        $collection->add($mixed);
        $this->assertEquals($expected, $collection->toArray());
    }

    /**
     * @expectedException \Tonic\ParallelProcessRunner\Exception\ProcessesMustBeInReadyStatusException
     */
    public function testAddNotReadyProcess()
    {
        try {
            $process = $this->getProcess(-1);
            $collection = new WaitProcessCollection();
            $collection->add($process);
        } catch (ProcessesMustBeInReadyStatusException $exception) {
            $this->assertEquals($process, $exception->getProcess());
            throw $exception;
        }
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Process
     */
    private function getProcess($status = Process::STATUS_READY)
    {
        $process = $this->getMock(Process::class, ['getStatus'], ['']);
        $process->expects($this->any())->method('getStatus')->willReturn($status);

        return $process;
    }

    /**
     * @param array $processes
     *
     * @return ProcessCollection
     *
     * @throws \Tonic\ParallelProcessRunner\Exception\NotProcessException
     * @throws \Tonic\ParallelProcessRunner\Exception\ProcessAlreadyInCollectionException
     */
    private function getCollection(array $processes)
    {
        $collection = new ProcessCollection();
        foreach ($processes as $process) {
            $collection->add($process);
        }

        return $collection;
    }
}
