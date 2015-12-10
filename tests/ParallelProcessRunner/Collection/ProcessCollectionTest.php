<?php

namespace Tonic\ParallelProcessRunner\Collection;

use Symfony\Component\Process\Process;
use Tonic\ParallelProcessRunner\Exception\NotProcessException;

/**
 * Class ProcessCollectionTest.
 *
 * @author kandelyabre <kandelyabre@gmail.com>
 */
class ProcessCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function providerAdd()
    {
        return [
            'empty' => [
                [],
            ],

            '1 element' => [
                [$this->getProcess()],
            ],

            'many elements' => [
                [$this->getProcess(), $this->getProcess()],
            ],
        ];
    }

    /**
     * @param array $processes
     *
     * @dataProvider providerAdd
     *
     * @return ProcessCollection
     */
    public function testAdd(array $processes)
    {
        $collection = $this->getCollection($processes);
        $this->assertEquals($processes, $collection->toArray());

        return $collection;
    }

    /**
     * @return array
     */
    public function providerNotProcess()
    {
        return [
            [1],
            [false],
            [null],
            [new \stdClass()],
        ];
    }

    /**
     * @param mixed $notProcess
     *
     * @expectedException \Tonic\ParallelProcessRunner\Exception\NotProcessException
     *
     * @throws \Tonic\ParallelProcessRunner\Exception\NotProcessException
     * @dataProvider providerNotProcess
     */
    public function testAddNotProcess($notProcess)
    {
        try {
            $this->getCollection($notProcess)->add($notProcess);
        } catch (NotProcessException $exception) {
            $this->assertEquals($notProcess, $exception->getObject());
            throw $exception;
        }
    }

    public function testClear()
    {
        $collection = $this->getCollection($this->getProcess());
        $collection->clear();
        $this->assertEmpty($collection->toArray());
    }

    public function testIsEmpty()
    {
        $collection = $this->getCollection();
        $this->assertTrue($collection->isEmpty());
        $collection->add($this->getProcess());
        $this->assertFalse($collection->isEmpty());
    }

    /**
     * @param array $processes
     *
     * @throws NotProcessException
     * @dataProvider providerAdd
     */
    public function testCount(array $processes)
    {
        $collection = $this->getCollection($processes);
        $this->assertEquals(count($collection->toArray()), $collection->count());
    }

    /**
     * @return array
     */
    public function providerSpliceByStatus()
    {
        return [
            [
                1,
                [
                    $this->getProcess(),
                ],
                [
                ],
            ],
            [
                1,
                [
                    $this->getProcess(),
                    $this->getProcess(1),
                    $this->getProcess(2),
                ],
                [
                    $this->getProcess(1),
                ],
            ],
            [
                1,
                [
                    $this->getProcess(1),
                    $this->getProcess(1),
                    $this->getProcess(1),
                ],
                [
                    $this->getProcess(1),
                    $this->getProcess(1),
                    $this->getProcess(1),
                ],
            ],

            [
                1,
                [
                    $this->getProcess(1),
                    $this->getProcess(1),
                    $this->getProcess(1),
                ],
                [
                    $this->getProcess(1),
                    $this->getProcess(1),
                ],
                2,
            ],
        ];
    }

    /**
     * @param array $processes
     * @param array $expectedResult
     *
     * @dataProvider providerSpliceByStatus
     */
    public function testSpliceByStatus($status, array $processes, array $expectedResult, $limit = null)
    {
        $collection = $this->getCollection($processes);
        $result = $collection->spliceByStatus($status, $limit);
        $this->assertEquals($expectedResult, $result);
        $this->assertEquals($processes, array_merge(
            $collection->toArray(),
            $expectedResult
        ));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Process
     */
    private function getProcess($status = null)
    {
        $process = $this->getMock(Process::class, ['getStatus'], ['']);
        $process->expects($this->any())->method('getStatus')->willReturn($status);

        return $process;
    }

    /**
     * @param array|Process|null     $processes
     * @param ProcessCollection|null $collection
     *
     * @return ProcessCollection
     *
     * @throws NotProcessException
     */
    private function getCollection($processes = null, ProcessCollection $collection = null)
    {
        if (is_null($collection)) {
            $collection = new ProcessCollection();
        }

        if (!is_array($processes)) {
            $processes = [$processes];
        }

        foreach (array_filter($processes) as $process) {
            $collection->add($process);
        }

        return $collection;
    }
}
