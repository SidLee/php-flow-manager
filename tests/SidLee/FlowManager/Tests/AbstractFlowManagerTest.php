<?php
namespace SidLee\FlowManager\Tests;

use SidLee\FlowManager\NavigationDirection;
use SidLee\FlowManager\NavigationResponse;
use SidLee\FlowManager\StepInterface;
use Symfony\Component\HttpFoundation\Response;

class AbstractFlowManagerTest extends AbstractFlowManagerTestCase
{
    public function testGetCurrentNavigationResponse_NoCurrent()
    {
        $flowManagerReflectionClass = new \ReflectionClass("\\SidLee\\FlowManager\\AbstractFlowManager");
        //This is the only way to be sure the getter works fine when the property is null.
        $navRespReflectionProperty = $flowManagerReflectionClass->getProperty('currentNavigationResponse');
        $navRespReflectionProperty->setAccessible(true);
        $navRespReflectionProperty->setValue($this->flowManager, null);

        $initialResponse = $this->flowManager->getCurrentNavigationResponse();
        $this->assertEquals(NavigationDirection::DIRECT(), $initialResponse->getNavigationDirection());
        $this->assertNull($initialResponse->getTargetStepName());
    }

    public function testGetCurrentNavigationResponse_WithCurrent()
    {
        $this->flowManager->setCurrentNavigationResponse($this->backNavResponse);
        $this->assertSame($this->backNavResponse, $this->flowManager->getCurrentNavigationResponse());
    }

    /**
     * @expectedException \SidLee\FlowManager\Exception\FlowException
     */
    public function testHandleRequest_CurrentStepDoesntExist()
    {
        foreach ($this->flattenedFlow as $step) {
            $this->setHandleRequestResultToStepMock($step, $this->never());
        }

        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'invalid.step');
        $this->flowManager->handleRequest($this->request);
    }

    public function testHandleRequest_NoCurrentStep_HandlesFirstEligible()
    {
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, null);

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setIsEligibleToStepMock($step1, true);

        $stepResponse = new Response('step1 response');
        $this->setHandleRequestResultToStepMock($step1, $this->once(), $stepResponse);
        $this->setHttpResponseToFlowManagerMock($this->flowManager, $this->never());

        $response = $this->flowManager->handleRequest($this->request);
        $this->assertSame($stepResponse, $response);
    }

    /**
     * @expectedException \SidLee\FlowManager\Exception\FlowException
     */
    public function testHandleRequest_EmptyNavResponse()
    {
        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->once(), new NavigationResponse());

        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');
        $this->flowManager->handleRequest($this->request);
    }

    /**
     * @expectedException \LogicException
     */
    public function testHandleRequest_InvalidResponseType()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->once(), 'invalid response');

        $this->flowManager->handleRequest($this->request);
    }

    public function testHandleRequest_ReturnsResponse()
    {
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $stepResponse = new Response('step1 response');
        $this->setHandleRequestResultToStepMock($step1, $this->once(), $stepResponse);
        $this->setHttpResponseToFlowManagerMock($this->flowManager, $this->never());

        $response = $this->flowManager->handleRequest($this->request);
        $this->assertSame($stepResponse, $response);
    }

    public function testHandleRequest_ReturnsBackNavResponse()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);

        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step3');
        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step3 */
        $step3 = $this->flattenedFlow->getStep('root.step3');
        $this->setHandleRequestResultToStepMock($step3, $this->once(), $this->backNavResponse);
        $this->setOnBackToStepMock($step3, $this->once());

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step2 */
        $step2 = $this->flattenedFlow->getStep('root.step2');
        $this->setHandleRequestResultToStepMock($step2, $this->never());
        $this->setOnBackToStepMock($step2, $this->never());

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->never());
        $this->setOnBackToStepMock($step1, $this->never());

        $expectedResponse = new Response('flow manager - response');
        $this->setHttpResponseToFlowManagerMock($this->flowManager, $this->once(), $expectedResponse);

        $response = $this->flowManager->handleRequest($this->request);
        $this->assertSame($expectedResponse, $response);

        $currentNavResponse = $this->flowManager->getCurrentNavigationResponse();
        $this->assertEquals(NavigationDirection::BACK(), $currentNavResponse->getNavigationDirection());
        $this->assertEquals('root.step2', $currentNavResponse->getTargetStepName());
    }

    /**
     * @expectedException \SidLee\FlowManager\Exception\FlowException
     */
    public function testHandleRequest_ReturnsBackNavResponse_NoStep()
    {
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->once(), $this->backNavResponse);

        $this->flowManager->handleRequest($this->request);
    }

    /**
     * @expectedException \SidLee\FlowManager\Exception\FlowException
     */
    public function testHandleRequest_ReturnsBackNavResponse_NoStepEligible()
    {
        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setIsEligibleToStepMock($step1, false);
        $this->setHandleRequestResultToStepMock($step1, $this->never());
        $this->setOnBackToStepMock($step1, $this->never());

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step2 */
        $step2 = $this->flattenedFlow->getStep('root.step2');
        $this->setHandleRequestResultToStepMock($step2, $this->once(), $this->backNavResponse);

        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step2');
        $this->flowManager->handleRequest($this->request);
    }

    public function testHandleRequest_ReturnsNextNavResponse()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step3 */
        $step3 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step3, $this->once(), $this->nextNavResponse);
        $this->setOnNextToStepMock($step3, $this->once());

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step2 */
        $step2 = $this->flattenedFlow->getStep('root.step2');
        $this->setHandleRequestResultToStepMock($step2, $this->never());
        $this->setOnNextToStepMock($step2, $this->never());

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step3 = $this->flattenedFlow->getStep('root.step3');
        $this->setHandleRequestResultToStepMock($step3, $this->never());
        $this->setOnNextToStepMock($step3, $this->never());

        $expectedResponse = new Response('flow manager - response');
        $this->setHttpResponseToFlowManagerMock($this->flowManager, $this->once(), $expectedResponse);

        $response = $this->flowManager->handleRequest($this->request);
        $this->assertSame($expectedResponse, $response);

        $currentNavResponse = $this->flowManager->getCurrentNavigationResponse();
        $this->assertEquals(NavigationDirection::NEXT(), $currentNavResponse->getNavigationDirection());
        $this->assertEquals('root.step2', $currentNavResponse->getTargetStepName());
    }

    /**
     * @expectedException \SidLee\FlowManager\Exception\FlowException
     */
    public function testHandleRequest_ReturnsNextNavResponse_NoStep()
    {
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step3');

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step3 */
        $step3 = $this->flattenedFlow->getStep('root.step3');
        $this->setHandleRequestResultToStepMock($step3, $this->once(), $this->nextNavResponse);

        $this->flowManager->handleRequest($this->request);
    }

    /**
     * @expectedException \SidLee\FlowManager\Exception\FlowException
     */
    public function testHandleRequest_ReturnsNextNavResponse_NoStepEligible()
    {
        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step3 */
        $step3 = $this->flattenedFlow->getStep('root.step3');
        $this->setIsEligibleToStepMock($step3, false);
        $this->setHandleRequestResultToStepMock($step3, $this->never());
        $this->setOnNextToStepMock($step3, $this->never());

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step2 */
        $step2 = $this->flattenedFlow->getStep('root.step2');
        $this->setHandleRequestResultToStepMock($step2, $this->once(), $this->nextNavResponse);

        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step2');
        $this->flowManager->handleRequest($this->request);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHandleRequest_ReturnsDirectNavResponse_NoTargetStep()
    {
        $this->directNavResponse->setTargetStepName(null);

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->once(), $this->directNavResponse);

        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');
        $this->flowManager->handleRequest($this->request);
    }

    /**
     * @expectedException \SidLee\FlowManager\Exception\FlowException
     */
    public function testHandleRequest_ReturnsDirectNavResponse_TargetStepIsNotEligible()
    {
        $this->directNavResponse->setTargetStepName('root.step2');

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->once(), $this->directNavResponse);

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step2 */
        $step2 = $this->flattenedFlow->getStep('root.step2');
        $this->setIsEligibleToStepMock($step2, false);

        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');
        $this->flowManager->handleRequest($this->request);
    }

    public function testHandleRequest_ReturnsDirectNavResponse_ResolvesToBack()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step3');
        $this->directNavResponse->setTargetStepName('root.step1');

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step3 */
        $step3 = $this->flattenedFlow->getStep('root.step3');
        $this->setHandleRequestResultToStepMock($step3, $this->once(), $this->directNavResponse);
        $this->setOnBackToStepMock($step3, $this->once());

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step2 */
        $step2 = $this->flattenedFlow->getStep('root.step2');
        $this->setHandleRequestResultToStepMock($step2, $this->never());
        $this->setOnBackToStepMock($step2, $this->once());

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->never());
        $this->setOnBackToStepMock($step1, $this->never());

        $expectedResponse = new Response('flow manager - response');
        $this->setHttpResponseToFlowManagerMock($this->flowManager, $this->once(), $expectedResponse);

        $response = $this->flowManager->handleRequest($this->request);
        $this->assertSame($expectedResponse, $response);

        $currentNavResponse = $this->flowManager->getCurrentNavigationResponse();
        $this->assertSame(NavigationDirection::BACK(), $currentNavResponse->getNavigationDirection());
        $this->assertEquals('root.step1', $currentNavResponse->getTargetStepName());
    }

    public function testHandleRequest_ReturnsDirectNavResponse_ResolvesToNext()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');
        $this->directNavResponse->setTargetStepName('root.step3');

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->once(), $this->directNavResponse);
        $this->setOnNextToStepMock($step1, $this->once());

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step2 */
        $step2 = $this->flattenedFlow->getStep('root.step2');
        $this->setHandleRequestResultToStepMock($step2, $this->never());
        $this->setOnNextToStepMock($step2, $this->once());

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step3 */
        $step3 = $this->flattenedFlow->getStep('root.step3');
        $this->setHandleRequestResultToStepMock($step3, $this->never());
        $this->setOnNextToStepMock($step3, $this->never());

        $expectedResponse = new Response('flow manager - response');
        $this->setHttpResponseToFlowManagerMock($this->flowManager, $this->once(), $expectedResponse);

        $response = $this->flowManager->handleRequest($this->request);
        $this->assertSame($expectedResponse, $response);

        $currentNavResponse = $this->flowManager->getCurrentNavigationResponse();
        $this->assertSame(NavigationDirection::NEXT(), $currentNavResponse->getNavigationDirection());
        $this->assertEquals('root.step3', $currentNavResponse->getTargetStepName());
    }

    public function testHandleRequest_ReturnsDirectNavResponse_ResolvesToDirect()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');
        $this->directNavResponse->setTargetStepName('root.step1');

        foreach ($this->flattenedFlow as $name => $step) {
            if ($name === 'root.step1') {
                $this->setHandleRequestResultToStepMock($step, $this->once(), $this->directNavResponse);
            } else {
                $this->setHandleRequestResultToStepMock($step, $this->never());
            }

            $this->setOnNextToStepMock($step, $this->never());
            $this->setOnBackToStepMock($step, $this->never());
        }

        $expectedResponse = new Response('flow manager - response');
        $this->setHttpResponseToFlowManagerMock($this->flowManager, $this->once(), $expectedResponse);

        $response = $this->flowManager->handleRequest($this->request);
        $this->assertSame($expectedResponse, $response);

        $currentNavResponse = $this->flowManager->getCurrentNavigationResponse();
        $this->assertSame(NavigationDirection::DIRECT(), $currentNavResponse->getNavigationDirection());
        $this->assertEquals('root.step1', $currentNavResponse->getTargetStepName());
    }
}