<?php
namespace SidLee\FlowManager;

class FlowManagerAscertainStepEligibilityEvent extends FlowManagerEvent
{
    /**
     * @var StepInterface
     */
    protected $step;
    /**
     * @var NavigationResponse
     */
    private $navResponse;

    private $isEligible = true;
    /**
     * @var
     */
    private $targetStepFQN;

    public function __construct($currentStepName, FlattenedFlow $flattenedFlow = null, $data = null, NavigationResponse $navResponse, StepInterface $step = null, $targetStepFQN)
    {
        parent::__construct($currentStepName, $flattenedFlow, $data);
        $this->step = $step;
        $this->navResponse = $navResponse;
        $this->targetStepFQN = $targetStepFQN;

        $this->pushEligibilityTestResult($step->isEligibleForNavigation($navResponse, $data));
    }

    /**
     * @return StepInterface
     */
    public function getStep()
    {
        return $this->step;
    }

    public function getStepFullyQualifiedName()
    {
        return $this->targetStepFQN;
    }

    public function markAsNonEligible()
    {
        $this->isEligible = false;
    }

    public function forceEligible()
    {
        $this->isEligible = true;
    }

    public function pushEligibilityTestResult($result)
    {
        $this->isEligible = $this->isEligible && $result;
    }

    /**
     * @return boolean
     */
    public function isEligible()
    {
        return $this->isEligible;
    }
} 