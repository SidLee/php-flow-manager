<?php
namespace SidLee\FlowManager\Tests;

use SidLee\FlowManager\FlowManagerRequestEvent;
use SidLee\FlowManager\FlowManagerEvents;
use SidLee\FlowManager\NavigationDirection;
use SidLee\FlowManager\StepInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

class AbstractFlowManagerWithEventsTest extends AbstractFlowManagerTestCase
{
    public function testHandleRequest_WithPreHandle_ReturnsResponse()
    {
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->never());

        $preHandleResponse = new Response('pre-handle response');
        $this->addListenerToDispatcher($this->eventDispatcher, FlowManagerEvents::PRE_HANDLE_REQUEST_EVENT, $preHandleResponse);

        $this->setHttpResponseToFlowManagerMock($this->flowManager, $this->never());
        $response = $this->flowManager->handleRequest($this->request);
        $this->assertSame($preHandleResponse, $response);
    }

    public function testHandleRequest_WithPreHandle_ReturnsBackNavResponse()
    {
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step2');
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);

        $this->addListenerToDispatcher($this->eventDispatcher, FlowManagerEvents::PRE_HANDLE_REQUEST_EVENT, $this->backNavResponse);

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
        $this->assertEquals(NavigationDirection::BACK(), $currentNavResponse->getNavigationDirection());
        $this->assertEquals('root.step1', $currentNavResponse->getTargetStepName());
    }

    public function testHandleRequest_WithPreHandle_ReturnsNextNavResponse()
    {
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);

        $this->addListenerToDispatcher($this->eventDispatcher, FlowManagerEvents::PRE_HANDLE_REQUEST_EVENT, $this->nextNavResponse);

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->never());
        $this->setOnNextToStepMock($step1, $this->once());

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step2 */
        $step2 = $this->flattenedFlow->getStep('root.step2');
        $this->setHandleRequestResultToStepMock($step2, $this->never());
        $this->setOnNextToStepMock($step2, $this->never());

        $expectedResponse = new Response('flow manager - response');
        $this->setHttpResponseToFlowManagerMock($this->flowManager, $this->once(), $expectedResponse);

        $response = $this->flowManager->handleRequest($this->request);
        $this->assertSame($expectedResponse, $response);

        $currentNavResponse = $this->flowManager->getCurrentNavigationResponse();
        $this->assertEquals(NavigationDirection::NEXT(), $currentNavResponse->getNavigationDirection());
        $this->assertEquals('root.step2', $currentNavResponse->getTargetStepName());
    }

    public function testHandleRequest_WithPreHandle_ReturnsDirectNavResponse()
    {
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);

        $this->directNavResponse->setTargetStepName('root.step3');
        $this->addListenerToDispatcher($this->eventDispatcher, FlowManagerEvents::PRE_HANDLE_REQUEST_EVENT, $this->directNavResponse);

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->never());
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
        $this->assertEquals(NavigationDirection::NEXT(), $currentNavResponse->getNavigationDirection());
        $this->assertEquals('root.step3', $currentNavResponse->getTargetStepName());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testHandleRequest_WithPreHandle_ReturnsResponseOfInvalidType()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->never());

        $this->addListenerToDispatcher($this->eventDispatcher, FlowManagerEvents::PRE_HANDLE_REQUEST_EVENT, 'invalid response');

        $this->setHttpResponseToFlowManagerMock($this->flowManager, $this->never());
        $this->flowManager->handleRequest($this->request);
    }

    public function testHandleRequest_WithPostHandle_ReturnsResponse()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->once(), $this->nextNavResponse);
        $this->setOnNextToStepMock($step1, $this->never());

        $postHandleResponse = new Response('post-handle response');
        $this->addListenerToDispatcher($this->eventDispatcher, FlowManagerEvents::POST_HANDLE_REQUEST_EVENT, $postHandleResponse);

        $response = $this->flowManager->handleRequest($this->request);
        $this->assertSame($postHandleResponse, $response);
    }

    public function testHandleRequest_WithPostHandle_ReturnsBackNavResponse()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);

        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step2');
        $this->addListenerToDispatcher($this->eventDispatcher, FlowManagerEvents::POST_HANDLE_REQUEST_EVENT, $this->backNavResponse);

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step2 */
        $step2 = $this->flattenedFlow->getStep('root.step2');
        $this->setHandleRequestResultToStepMock($step2, $this->once(), $this->nextNavResponse);
        $this->setOnNextToStepMock($step2, $this->never());
        $this->setOnBackToStepMock($step2, $this->once());

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step3 */
        $step3 = $this->flattenedFlow->getStep('root.step3');
        $this->setHandleRequestResultToStepMock($step3, $this->never());
        $this->setOnNextToStepMock($step3, $this->never());
        $this->setOnBackToStepMock($step3, $this->never());

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->never());
        $this->setOnNextToStepMock($step1, $this->never());
        $this->setOnBackToStepMock($step1, $this->never());

        $expectedResponse = new Response('flow manager - response');
        $this->setHttpResponseToFlowManagerMock($this->flowManager, $this->once(), $expectedResponse);

        $response = $this->flowManager->handleRequest($this->request);
        $this->assertSame($expectedResponse, $response);

        $currentNavResponse = $this->flowManager->getCurrentNavigationResponse();
        $this->assertSame(NavigationDirection::BACK(), $currentNavResponse->getNavigationDirection());
        $this->assertEquals('root.step1', $currentNavResponse->getTargetStepName());
    }

    public function testHandleRequest_WithPostHandle_ReturnsNextNavResponse()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);

        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step2');
        $this->addListenerToDispatcher($this->eventDispatcher, FlowManagerEvents::POST_HANDLE_REQUEST_EVENT, $this->nextNavResponse);

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step2 */
        $step2 = $this->flattenedFlow->getStep('root.step2');
        $this->setHandleRequestResultToStepMock($step2, $this->once(), $this->backNavResponse);
        $this->setOnNextToStepMock($step2, $this->once());
        $this->setOnBackToStepMock($step2, $this->never());

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step3 */
        $step3 = $this->flattenedFlow->getStep('root.step3');
        $this->setHandleRequestResultToStepMock($step3, $this->never());
        $this->setOnNextToStepMock($step3, $this->never());
        $this->setOnBackToStepMock($step3, $this->never());

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->never());
        $this->setOnNextToStepMock($step1, $this->never());
        $this->setOnBackToStepMock($step1, $this->never());

        $expectedResponse = new Response('flow manager - response');
        $this->setHttpResponseToFlowManagerMock($this->flowManager, $this->once(), $expectedResponse);

        $response = $this->flowManager->handleRequest($this->request);
        $this->assertSame($expectedResponse, $response);

        $currentNavResponse = $this->flowManager->getCurrentNavigationResponse();
        $this->assertSame(NavigationDirection::NEXT(), $currentNavResponse->getNavigationDirection());
        $this->assertEquals('root.step3', $currentNavResponse->getTargetStepName());
    }

    public function testHandleRequest_WithPostHandle_ReturnsDirectNavResponse()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');

        $this->directNavResponse->setTargetStepName('root.step3');
        $this->addListenerToDispatcher($this->eventDispatcher, FlowManagerEvents::POST_HANDLE_REQUEST_EVENT, $this->directNavResponse);

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->once(), $this->nextNavResponse);
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

    /**
     * @expectedException \LogicException
     */
    public function testHandleRequest_WithPostHandle_ReturnsResponseOfInvalidType()
    {
        $this->setIsEligibleToAllStepMocks($this->flattenedFlow, true);
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->once(), $this->nextNavResponse);

        $this->addListenerToDispatcher($this->eventDispatcher, FlowManagerEvents::POST_HANDLE_REQUEST_EVENT, 'invalid response');

        $this->flowManager->handleRequest($this->request);
    }

    public function testHandleRequest_WithPreHandleResponse_WithPostHandleResponse()
    {
        $this->setCurrentStepNameToFlowManagerMock($this->flowManager, 'root.step1');

        /** @var StepInterface|\PHPUnit_Framework_MockObject_MockObject $step1 */
        $step1 = $this->flattenedFlow->getStep('root.step1');
        $this->setHandleRequestResultToStepMock($step1, $this->never());

        $preHandleResponse = new Response('pre-handle response');
        $this->addListenerToDispatcher($this->eventDispatcher, FlowManagerEvents::PRE_HANDLE_REQUEST_EVENT, $preHandleResponse);

        $postHandleResponse = new Response('post-handle response');
        $this->addListenerToDispatcher($this->eventDispatcher, FlowManagerEvents::POST_HANDLE_REQUEST_EVENT, $postHandleResponse);

        $response = $this->flowManager->handleRequest($this->request);
        $this->assertSame($postHandleResponse, $response);
    }

    protected function addListenerToDispatcher(EventDispatcherInterface $eventDispatcher, $eventName, $responseToReturn)
    {
        $eventDispatcher->addListener(
            $eventName,
            function (FlowManagerRequestEvent $event) use ($responseToReturn) {
                $event->setResponse($responseToReturn);
            }
        );
    }
} 