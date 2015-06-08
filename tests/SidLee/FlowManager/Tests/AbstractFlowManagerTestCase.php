<?php
namespace SidLee\FlowManager\Tests;

use SidLee\FlowManager\AbstractFlowManager;
use SidLee\FlowManager\FlattenedFlow;
use SidLee\FlowManager\NavigationDirection;
use SidLee\FlowManager\NavigationResponse;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \PHPUnit_Framework_MockObject_MockObject as MockObject;
use \PHPUnit_Framework_MockObject_Matcher_Invocation as Matcher;

abstract class AbstractFlowManagerTestCase extends FlowTestCase
{
    /** @var AbstractFlowManager|\PHPUnit_Framework_MockObject_MockObject $flowManager */
    protected $flowManager = null;
    /** @var FlattenedFlow */
    protected $flattenedFlow = null;
    /** @var EventDispatcherInterface */
    protected $eventDispatcher = null;
    /** @var Request */
    protected $request = null;
    /** @var NavigationResponse */
    protected $backNavResponse = null;
    /** @var NavigationResponse */
    protected $nextNavResponse = null;
    /** @var NavigationResponse */
    protected $directNavResponse = null;

    protected function setUp()
    {
        parent::setUp();
        $this->eventDispatcher = new EventDispatcher();
        $this->flowManager = $this->getFlowManagerMockWithSteps(3, 'root', 'step', $this->eventDispatcher);
        $this->flattenedFlow = $this->flowManager->getFlattenedFlow();
        $this->request = new Request();

        $this->backNavResponse = $this->getNavigationResponse(NavigationDirection::BACK());
        $this->nextNavResponse = $this->getNavigationResponse(NavigationDirection::NEXT());
        $this->directNavResponse = $this->getNavigationResponse(NavigationDirection::DIRECT());
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $flowManager
     * @param string $stepName
     */
    protected function setCurrentStepNameToFlowManagerMock(MockObject $flowManager, $stepName)
    {
        $flowManager->expects($this->any())->method('getCurrentStepName')->will($this->returnValue($stepName));
    }

    protected function setHandleRequestResultToStepMock(MockObject $stepMock, Matcher $matcher, $result = null)
    {
        $stepMock->expects($matcher)->method('handleRequest')->will($this->returnValue($result));
    }

    protected function setOnBackToStepMock(MockObject $stepMock, Matcher $matcher, $result = null)
    {
        $stepMock->expects($matcher)->method('onBack')->will($this->returnValue($result));
    }

    protected function setOnNextToStepMock(MockObject $stepMock, Matcher $matcher, $result = null)
    {
        $stepMock->expects($matcher)->method('onNext')->will($this->returnValue($result));
    }

    protected function setHttpResponseToFlowManagerMock(MockObject $flowManager, Matcher $matcher, Response $response = null)
    {
        $flowManager->expects($matcher)->method('getNavigationHttpResponse')->will($this->returnValue($response));
    }
} 