<?php
namespace SidLee\FlowManager\Tests;

use SidLee\FlowManager\AbstractFlowManager;
use SidLee\FlowManager\FlattenedFlow;
use SidLee\FlowManager\NavigationDirection;
use SidLee\FlowManager\NavigationResponse;
use SidLee\FlowManager\StepCollection;
use SidLee\FlowManager\StepCollectionInterface;
use SidLee\FlowManager\StepInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;

abstract class FlowTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|StepInterface
     */
    protected function getStepMock()
    {
        return $this->getMockForAbstractClass("\\SidLee\\FlowManager\\StepInterface");
    }

    /**
     * Set isEligibleForNavigation of the supplied step to the supplied value.
     * @param \PHPUnit_Framework_MockObject_MockObject $stepMock
     * @param bool $isEligible
     */
    protected function setIsEligibleToStepMock(MockObject $stepMock, $isEligible)
    {
        $stepMock->expects($this->any())
            ->method('isEligibleForNavigation')
            ->will($this->returnValue($isEligible));
    }

    /**
     * Set isEligibleForNavigation of every step of the flow to the value supplied.
     * If $stepsToToggleValue is supplied, those steps will have the opposite value (!$isEligible).
     * @param FlattenedFlow|\PHPUnit_Framework_MockObject_MockObject $flow
     * @param bool $isEligible
     * @param string[] $stepsToInverseValue
     */
    protected function setIsEligibleToAllStepMocks(FlattenedFlow $flow, $isEligible, array $stepsToInverseValue = array())
    {
        foreach ($flow as $name => $step) {
            if (in_array($name, $stepsToInverseValue)) {
                $this->setIsEligibleToStepMock($step, !$isEligible);
            } else {
                $this->setIsEligibleToStepMock($step, $isEligible);
            }
        }
    }

    /**
     * @param $key
     * @param StepCollectionInterface $steps
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractFlowManager
     */
    protected function getFlowManagerMock($key, StepCollectionInterface $steps, EventDispatcherInterface $eventDispatcher = null)
    {
        return $this->getMockForAbstractClass("\\SidLee\\FlowManager\\AbstractFlowManager", array($key, $steps, $eventDispatcher));
    }

    /**
     * @param int $numberOfSteps
     * @param string $flowManagerKey
     * @param string $stepPrefix
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     * @return \SidLee\FlowManager\AbstractFlowManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFlowManagerMockWithSteps($numberOfSteps = 3, $flowManagerKey = 'root', $stepPrefix = 'step', EventDispatcherInterface $eventDispatcher = null)
    {
        $steps = new StepCollection();
        for ($i = 1; $i <= $numberOfSteps; $i++) {
            $steps->add($stepPrefix . $i, $this->getStepMock());
        }

        $flowManager = $this->getFlowManagerMock($flowManagerKey, $steps, $eventDispatcher);
        return $flowManager;
    }

    /**
     * @param NavigationDirection $direction
     * @param string $targetStepName
     * @return NavigationResponse
     */
    protected function getNavigationResponse(NavigationDirection $direction, $targetStepName = null)
    {
        $navResponse = new NavigationResponse();
        $navResponse->setNavigationDirection($direction);
        $navResponse->setTargetStepName($targetStepName);
        return $navResponse;
    }

    /**
     * @param $steps
     * @param array $expectedFlow
     */
    protected function assertStepNames($steps, array $expectedFlow)
    {
        $this->assertCount(count($expectedFlow), $steps);

        $index = 0;
        foreach ($steps as $name => $step) {
            $this->assertTrue($name === $expectedFlow[$index], "The step at the index $index should have the key \"$name\".");
            $index++;
        }
    }
} 