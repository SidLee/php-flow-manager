<?php
namespace SidLee\FlowManager;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FlowManagerRequestEvent extends FlowManagerEvent
{
    /** @var Request|null */
    protected $request;
    /** @var NavigationResponse|Response */
    protected $response;

    public function __construct($currentStepName, FlattenedFlow $flattenedFlow = null, $data = null, Request $request, $response = null)
    {
        parent::__construct($currentStepName, $flattenedFlow, $data);

        $this->request = $request;
        if ($response !== null) {
            $this->setResponse($response);
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return \SidLee\FlowManager\NavigationResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param \SidLee\FlowManager\NavigationResponse|\Symfony\Component\HttpFoundation\Response $response
     *
     * @throws \InvalidArgumentException
     */
    public function setResponse($response)
    {
        if (!($response instanceof Response) && !($response instanceof NavigationResponse)) {
            throw new \InvalidArgumentException('The argument $response must be of type Response or NavigationResponse.');
        }

        $this->response = $response;
    }
} 