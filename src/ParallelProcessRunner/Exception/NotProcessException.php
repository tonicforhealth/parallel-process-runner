<?php

namespace Tonic\ParallelProcessRunner\Exception;

/**
 * Class NotProcessException.
 *
 * @author kandelyabre <kandelyabre@gmail.com>
 */
class NotProcessException extends AbstractProcessException
{
    /**
     * @var mixed
     */
    private $object;

    /**
     * NotProcessException constructor.
     *
     * @param mixed $object
     */
    public function __construct($object)
    {
        $this->object = $object;
    }

    /**
     * @return mixed
     */
    public function getObject()
    {
        return $this->object;
    }
}
