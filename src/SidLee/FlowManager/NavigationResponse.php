<?php

namespace SidLee\FlowManager;

class NavigationResponse
{
    /** @var NavigationDirection */
    private $navigationDirection;
    /** @var string */
    private $targetStepName;

    /**
     * @param NavigationDirection $navigationDirection
     * @param string $targetStepName
     */
    public function __construct(NavigationDirection $navigationDirection = null, $targetStepName = null)
    {
        $this->navigationDirection = $navigationDirection;
        $this->targetStepName = $targetStepName;
    }

    /**
     * @return \SidLee\FlowManager\NavigationDirection
     */
    public function getNavigationDirection()
    {
        return $this->navigationDirection;
    }

    /**
     * @param \SidLee\FlowManager\NavigationDirection $navigationDirection
     */
    public function setNavigationDirection(NavigationDirection $navigationDirection)
    {
        $this->navigationDirection = $navigationDirection;
    }

    /**
     * @return string
     */
    public function getTargetStepName()
    {
        return $this->targetStepName;
    }

    /**
     * @param string $targetStepName
     */
    public function setTargetStepName($targetStepName)
    {
        $this->targetStepName = $targetStepName;
    }
}