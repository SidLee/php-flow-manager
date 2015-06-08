<?php
namespace SidLee\FlowManager;

use Symfony\Component\EventDispatcher\Event;

abstract class FlowManagerEvent extends Event
{
    /** @var mixed */
    protected $data;
    /**
     * @var null|string
     */
    protected $currentStepName;
    /**
     * @var FlattenedFlow|null
     */
    private $flattenedFlow;

    /**
     * @param string|null        $currentStepName
     * @param FlattenedFlow|null $flattenedFlow
     * @param mixed|null         $data
     */
    public function __construct($currentStepName, FlattenedFlow $flattenedFlow = null, $data = null)
    {
        $this->data = $data;
        $this->currentStepName = $currentStepName;
        $this->flattenedFlow = $flattenedFlow;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return null|string
     */
    public function getCurrentStepName()
    {
        return $this->currentStepName;
    }

    /**
     * @return FlattenedFlow|null
     */
    public function getFlattenedFlow()
    {
        return $this->flattenedFlow;
    }
} 