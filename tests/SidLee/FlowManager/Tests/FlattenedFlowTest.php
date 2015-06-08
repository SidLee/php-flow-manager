<?php
namespace SidLee\FlowManager\Tests;

use SidLee\FlowManager\AbstractFlowManager;
use SidLee\FlowManager\FlattenedFlow;
use SidLee\FlowManager\NavigationDirection;
use SidLee\FlowManager\NavigationResponse;
use SidLee\FlowManager\StepInterface;

class FlattenedFlowTest extends FlowTestCase
{
    /** @var AbstractFlowManager */
    private $flowManager = null;
    /** @var FlattenedFlow */
    private $flattenedFlow = null;
    /** @var NavigationResponse */
    private $backNavResponse = null;
    /** @var NavigationResponse */
    private $nextNavResponse = null;
    /** @var NavigationResponse */
    private $directNavResponse = null;

    public function testDetectDirectNavigationDirection()
    {
        $this->assertDetectDirectNavigationDirection('root.step1', 'root.step2', NavigationDirection::NEXT());
        $this->assertDetectDirectNavigationDirection('root.step2', 'root.step1', NavigationDirection::BACK());
        $this->assertDetectDirectNavigationDirection('root.step1', 'root.step1', NavigationDirection::DIRECT());
    }

    /**
     * @expectedException \SidLee\FlowManager\Exception\FlowException
     */
    public function testDetectDirectNavigationDirection_StepNotInFlow()
    {
        $this->flattenedFlow->detectDirectNavigationDirection('root.step2', 'root.invalid_step');
    }

    public function testResolvePreviousStepName()
    {
        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setIsEligibleToStepMock($step1, true);

        $previousStepName = $this->flattenedFlow->resolvePreviousStepName($this->backNavResponse, 'root.step2', null);
        $this->assertEquals('root.step1', $previousStepName);
    }

    /**
     * @expectedException \SidLee\FlowManager\Exception\FlowException
     */
    public function testResolvePreviousStepName_NoPreviousStep()
    {
        $this->flattenedFlow->resolvePreviousStepName($this->backNavResponse, 'root.step1', null);
    }

    /**
     * @expectedException \SidLee\FlowManager\Exception\FlowException
     */
    public function testResolvePreviousStepName_NoPreviousStepEligible()
    {
        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setIsEligibleToStepMock($step1, false);

        $this->flattenedFlow->resolvePreviousStepName($this->backNavResponse, 'root.step2', null);
    }

    public function testResolveNextStepName()
    {
        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step3 */
        $step3 = $this->flattenedFlow->getStep('root.step3');
        $this->setIsEligibleToStepMock($step3, true);

        $nextStepName = $this->flattenedFlow->resolveNextStepName($this->nextNavResponse, 'root.step2', null);
        $this->assertEquals('root.step3', $nextStepName);
    }

    /**
     * @expectedException \SidLee\FlowManager\Exception\FlowException
     */
    public function testResolveNextStepName_NoNextStep()
    {
        $this->flattenedFlow->resolveNextStepName($this->nextNavResponse, 'root.step3', null);
    }

    /**
     * @expectedException \SidLee\FlowManager\Exception\FlowException
     */
    public function testResolveNextStepName_NoNextStepEligible()
    {
        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step3 */
        $step3 = $this->flattenedFlow->getStep('root.step3');
        $this->setIsEligibleToStepMock($step3, false);

        $this->flattenedFlow->resolveNextStepName($this->nextNavResponse, 'root.step2', null);
    }

    public function testGetStep()
    {
        $testStep = $this->getStepMock();
        $this->flowManager->getItems()->add('test_step', $testStep);

        $this->flowManager = $this->getFlowManagerMock('root', $this->flowManager->getItems());
        $this->flattenedFlow = new FlattenedFlow($this->flowManager);

        $stepFromFlow = $this->flattenedFlow->getStep('root.test_step');
        $this->assertSame($testStep, $stepFromFlow);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetStep_StepNameIsNull()
    {
        $this->flattenedFlow->getStep(null);
    }

    /**
     * @expectedException \SidLee\FlowManager\Exception\FlowException
     */
    public function testGetStep_StepNotInFlow()
    {
        $this->flattenedFlow->getStep('root.invalid_step');
    }

    /**
     * @expectedException \SidLee\FlowManager\Exception\FlowException
     */
    public function testResolveFirstEligibleStepName_NoEligible()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, false);

        $navResponse = $this->getNavigationResponse(NavigationDirection::DIRECT());
        $this->flattenedFlow->resolveFirstEligibleStepName($navResponse, null);
    }

    public function testResolveFirstEligibleStepName()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, false, array('root.step2'));

        $navResponse = $this->getNavigationResponse(NavigationDirection::DIRECT());
        $firstEligible = $this->flattenedFlow->resolveFirstEligibleStepName($navResponse, null);

        $this->assertEquals('root.step2', $firstEligible);
    }

    public function testGetRange_Forward_NoExclude()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);

        $navResponse = $this->getNavigationResponse(NavigationDirection::NEXT(), 'root.step3');
        $rangeSteps = $this->flattenedFlow->getRange('root.step1', $navResponse, null, false);

        $expectedStepNames = array('root.step1', 'root.step2', 'root.step3');
        $this->assertStepNames($rangeSteps, $expectedStepNames);
    }

    public function testGetRange_Forward_ExcludeDestination()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);

        $navResponse = $this->getNavigationResponse(NavigationDirection::NEXT(), 'root.step3');
        $rangeSteps = $this->flattenedFlow->getRange('root.step1', $navResponse, null);

        $expectedStepNames = array('root.step1', 'root.step2');
        $this->assertStepNames($rangeSteps, $expectedStepNames);
    }

    public function testGetRange_Forward_NotEligible()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true, array('root.step2'));

        $navResponse = $this->getNavigationResponse(NavigationDirection::NEXT(), 'root.step3');
        $rangeSteps = $this->flattenedFlow->getRange('root.step1', $navResponse, null, false);

        $expectedStepNames = array('root.step1', 'root.step3');
        $this->assertStepNames($rangeSteps, $expectedStepNames);
    }

    public function testGetRange_Backward_NoExclude()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);

        $navResponse = $this->getNavigationResponse(NavigationDirection::BACK(), 'root.step1');
        $rangeSteps = $this->flattenedFlow->getRange('root.step3', $navResponse, null, false);

        $expectedStepNames = array('root.step3', 'root.step2', 'root.step1');
        $this->assertStepNames($rangeSteps, $expectedStepNames);
    }

    public function testGetRange_Backward_ExcludeDestination()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);

        $navResponse = $this->getNavigationResponse(NavigationDirection::BACK(), 'root.step1');
        $rangeSteps = $this->flattenedFlow->getRange('root.step3', $navResponse, null);

        $expectedStepNames = array('root.step3', 'root.step2');
        $this->assertStepNames($rangeSteps, $expectedStepNames);
    }

    public function testGetRange_Backward_NotEligible()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true, array('root.step2'));

        $navResponse = $this->getNavigationResponse(NavigationDirection::BACK(), 'root.step1');
        $rangeSteps = $this->flattenedFlow->getRange('root.step3', $navResponse, null, false);

        $expectedStepNames = array('root.step3', 'root.step1');
        $this->assertStepNames($rangeSteps, $expectedStepNames);
    }

    /**
     * @param string $currentStepName
     * @param string $targetStepName
     * @param NavigationDirection $expectedDirection
     */
    private function assertDetectDirectNavigationDirection($currentStepName, $targetStepName, NavigationDirection $expectedDirection)
    {
        $navDirection = $this->flattenedFlow->detectDirectNavigationDirection($currentStepName, $targetStepName);
        $this->assertSame($navDirection, $expectedDirection);
    }

    protected function setUp()
    {
        parent::setUp();
        $this->flowManager = $this->getFlowManagerMockWithSteps();
        $this->flattenedFlow = new FlattenedFlow($this->flowManager);

        $this->backNavResponse = $this->getNavigationResponse(NavigationDirection::BACK());
        $this->nextNavResponse = $this->getNavigationResponse(NavigationDirection::NEXT());
        $this->directNavResponse = $this->getNavigationResponse(NavigationDirection::DIRECT());
    }
}