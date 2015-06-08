<?php
namespace SidLee\FlowManager\Tests;

use SidLee\FlowManager\FlowManagerRequestEvent;
use SidLee\FlowManager\NavigationDirection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FlowManagerEventTest extends FlowTestCase
{
    public function testSetResponse_AssignResponse()
    {
        $response = new Response('event response');
        $event = new FlowManagerRequestEvent(null, null, null, new Request());
        $event->setResponse($response);
        $this->assertSame($response, $event->getResponse());
    }
    public function testConstructor_ResponseOfTypeResponse()
    {
        $response = new Response('event response');
        $event = new FlowManagerRequestEvent(null, null, null, new Request(), $response);
        $this->assertSame($response, $event->getResponse());
    }

    public function testSetResponse_AssignNavResponse()
    {
        $response = $this->getNavigationResponse(NavigationDirection::NEXT());
        $event = new FlowManagerRequestEvent(null, null, null, new Request());
        $event->setResponse($response);
        $this->assertSame($response, $event->getResponse());
    }

    public function testConstructor_ResponseOfTypeNavResponse()
    {
        $response = $this->getNavigationResponse(NavigationDirection::NEXT());
        $event = new FlowManagerRequestEvent(null, null, null, new Request(), $response);
        $this->assertSame($response, $event->getResponse());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testSetResponse_ResponseOfInvalidType()
    {
        $event = new FlowManagerRequestEvent(null, null, null, new Request());
        /** @noinspection PhpParamsInspection */
        $event->setResponse('invalid response');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructor_ResponseOfInvalidType()
    {
        new FlowManagerRequestEvent(null, null, null, new Request(), 'invalid response');
    }
} 