<?php

namespace Tonic\ParallelProcessRunner;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Process\Process;
use Tonic\ParallelProcessRunner\Event\ParallelProcessRunnerEventType;
use Tonic\ParallelProcessRunner\Event\ProcessEvent;
use Tonic\ParallelProcessRunner\Event\ProcessOutEvent;

/**
 * Class ParallelProcessRunnerTest.
 *
 * @author kandelyabre <kandelyabre@gmail.com>
 */
class ParallelProcessRunnerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @see ParallelProcessRunner::getEventDispatcher
     */
    public function testEventDispatcher()
    {
        $this->assertInstanceOf(EventDispatcherInterface::class, (new ParallelProcessRunner())->getEventDispatcher());
    }

    /**
     * @see ParallelProcessRunner::getEventDispatcher
     */
    public function testCustomEventDispatcher()
    {
        $eventDispatcher = $this->getMock(EventDispatcherInterface::class);
        $this->assertEquals($eventDispatcher, (new ParallelProcessRunner($eventDispatcher))->getEventDispatcher());
    }

    /**
     * @return array
     */
    public function providerRun()
    {
        return [
            'first process is longer then second' => [
                2,
                [
                    $this->getEchoProcess('1', 50000),
                    $this->getEchoProcess('2'),
                ],
                [2, 1],
            ],

            'first process is longer then second but only one in parallel' => [
                1,
                [
                    $this->getEchoProcess('1', 50000),
                    $this->getEchoProcess('2'),
                ],
                [1, 2],
            ],

            'second process is longer then first' => [
                2,
                [
                    $this->getEchoProcess('1'),
                    $this->getEchoProcess('2', 1000),
                ],
                [1, 2],
            ],

            'one process - two parallels' => [
                2,
                [
                    $this->getEchoProcess('1'),
                ],
                [1],
            ],
        ];
    }

    /**
     * @param int   $maxParallelProcess
     * @param mixed $processes
     * @param array $expectedResult
     *
     * @dataProvider providerRun
     */
    public function testRun($maxParallelProcess, $processes, array $expectedResult)
    {
        $runner = new ParallelProcessRunner();
        $runner->setStatusCheckWait(10);
        $runner->setMaxParallelProcess($maxParallelProcess);

        $runner->add($processes);

        $this->assertEquals($expectedResult, $this->getOutputArray($runner->run()));
    }

    /**
     * @see ParallelProcessRunner::reset
     */
    public function testReset()
    {
        $runner = new ParallelProcessRunner();
        $runner->add($this->getEchoProcess());
        $runner->reset();
        $this->assertEmpty($runner->run());
    }

    /**
     * @return array
     */
    public function providerStop()
    {
        return [
            'stop before run' => [
                1,
                [
                    $this->getEchoProcess('stop'),
                    $this->getEchoProcess('never called'),
                ],
                [
                    'stop',
                ],
            ],

            'stop while run' => [
                2,
                [
                    $this->getEchoProcess('finish', 5000000, 'start'),
                    $this->getEchoProcess('stop'),
                ],
                [
                    'stop',
                    'start',
                ],
            ],
        ];
    }

    /**
     * @param int   $maxParallelProcess
     * @param mixed $processes
     * @param array $expectedResult
     *
     * @dataProvider providerStop
     */
    public function testStop($maxParallelProcess, $processes, array $expectedResult)
    {
        $runner = new ParallelProcessRunner();
        $runner->setMaxParallelProcess($maxParallelProcess);
        $runner->add($processes);

        $runner->getEventDispatcher()->addListener(ParallelProcessRunnerEventType::PROCESS_STOP_AFTER, function (ProcessEvent $event) use ($runner) {
            if ($event->getProcess()->getOutput() == 'stop') {
                $runner->stop();
            }
        });

        $this->assertEquals($expectedResult, $this->getOutputArray($runner->run()));
    }

    /**
     * @param int $count
     */
    public function testBeforeStartEvent($count = 3)
    {
        $processes = [];
        while ($count-- > 0) {
            $processes[] = $this->getEchoProcess();
        }
        $events = $this->hookEventsByName($processes, ParallelProcessRunnerEventType::PROCESS_START_BEFORE);

        $this->assertEquals(count($processes), count($events));
        foreach ($events as $index => $event) {
            $this->assertInstanceOf(ProcessEvent::class, $event);
            $this->assertEquals($event->getProcess(), $processes[$index]);
        }
    }

    /**
     * @see ParallelProcessRunnerEventType::PROCESS_STOP_AFTER
     */
    public function testAfterStopEvent()
    {
        $processes = [
            $this->getEchoProcess(),
        ];

        $events = $this->hookEventsByName($processes, ParallelProcessRunnerEventType::PROCESS_STOP_AFTER);

        $this->assertEquals(count($processes), count($events));
        foreach ($events as $index => $event) {
            $this->assertInstanceOf(ProcessEvent::class, $event);
            $this->assertEquals($event->getProcess(), $processes[$index]);
        }
    }

    /**
     * @see ParallelProcessRunnerEventType::PROCESS_OUT
     */
    public function testOutEvent()
    {
        $process = $this->getEchoProcess('last', 1000000, 'first');

        $expected = ['first', 'last'];

        /** @var ProcessOutEvent[] $events */
        $events = $this->hookEventsByName([$process], ParallelProcessRunnerEventType::PROCESS_OUT);

        $this->assertEquals(count($expected), count($events));
        foreach ($events as $index => $event) {
            $this->assertInstanceOf(ProcessOutEvent::class, $event);
            $this->assertEquals($event->getProcess(), $process);

            $this->assertEquals($expected[$index], $event->getOutData());
            $this->assertEquals('out', $event->getOutType());
        }
    }

    /**
     * @param array  $processes
     * @param string $hookedEventName
     *
     * @return ProcessEvent[]
     */
    protected function hookEventsByName(array $processes, $hookedEventName)
    {
        $events = [];
        $eventDispatcher = $this->getMock(EventDispatcher::class, ['dispatch']);
        $eventDispatcher->expects($this->any())->method('dispatch')
            ->willReturnCallback(function ($evenName, $event) use (&$events, $hookedEventName) {
                if ($hookedEventName == $evenName) {
                    $events[] = $event;
                }
            });

        $runner = new ParallelProcessRunner($eventDispatcher);
        $runner->add($processes)->run();

        return $events;
    }

    /**
     * @param string $string
     * @param int    $wait
     * @param string $beforeWait
     *
     * @return Process
     */
    private function getEchoProcess($string = '', $wait = 0, $beforeWait = '')
    {
        $phpCode = sprintf('echo %s; flush(); usleep(%d); echo %s; flush();', var_export($beforeWait, true), $wait, var_export($string, true));

        return new Process(sprintf('%s -r %s', PHP_BINARY, escapeshellarg($phpCode)));
    }

    /**
     * @param Process[] $processes
     *
     * @return array
     */
    private function getOutputArray(array $processes)
    {
        return array_map(function (Process $process) {
            return $process->getOutput();
        }, $processes);
    }
}
