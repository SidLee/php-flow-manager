<?php
namespace SidLee\FlowManager;

use SidLee\FlowManager\Exception\FlowException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractFlowManager
{
    /** @var string */
    protected $key;
    /** @var StepCollection */
    protected $items;
    /** @var FlattenedFlow */
    protected $flattenedFlow = null;
    /** @var null|NavigationResponse */
    protected $currentNavigationResponse = null;
    /** @var null|EventDispatcherInterface */
    protected $eventDispatcher = null;

    private $currentProcessedStepName;

    /**
     * @param string                                                      $key
     * @param \SidLee\FlowManager\StepCollection                        $items
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($key, StepCollection $items, EventDispatcherInterface $eventDispatcher = null)
    {
        if (strpos($key, '.') !== false) {
            throw new \InvalidArgumentException('The parameter $key contains a dot, which is a reserved character used in property paths.');
        }

        if (strpos($key, '%') !== false) {
            throw new \InvalidArgumentException('The parameter $key contains a percent sign, which is a reserved character used in property paths.');
        }

        if (count($items) === 0) {
            throw new \InvalidArgumentException('The parameter $steps should contain at least one step.');
        }

        $this->key = $key;
        $this->items = $items;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return \SidLee\FlowManager\NavigationResponse|null
     */
    public function getCurrentNavigationResponse()
    {
        if ($this->currentNavigationResponse === null) {
            $navResponse = new NavigationResponse();
            $navResponse->setNavigationDirection(NavigationDirection::DIRECT());

            return $navResponse;
        }

        return $this->currentNavigationResponse;
    }

    /**
     * @param \SidLee\FlowManager\NavigationResponse|null $currentNavResponse
     */
    public function setCurrentNavigationResponse(NavigationResponse $currentNavResponse)
    {
        $this->currentNavigationResponse = $currentNavResponse;
    }

    /**
     * @return null|\Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @return \SidLee\FlowManager\StepCollection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return FlattenedFlow
     */
    public function getFlattenedFlow()
    {
        if ($this->flattenedFlow === null) {
            $this->flattenedFlow = new FlattenedFlow($this);
        }

        return $this->flattenedFlow;
    }

    public function isStepEligibleForNavigation($targetStepFQN, NavigationResponse $navResponse, $data)
    {
        $step = $this->getFlattenedFlow()->getStep($targetStepFQN);
        $ascertainStepEligibilityEvent = new FlowManagerAscertainStepEligibilityEvent($this->currentProcessedStepName, $this->getFlattenedFlow(), $data, $navResponse, $step, $targetStepFQN);

        if ($this->eventDispatcher !== null) {
            $this->getEventDispatcher()->dispatch(FlowManagerEvents::ASCERTAIN_STEP_ELIGIBILITY_EVENT, $ascertainStepEligibilityEvent);
        }

        return $ascertainStepEligibilityEvent->isEligible();
    }

    /**
     * @return mixed
     */
    abstract public function getData();

    /**
     * @param Request $request
     *
     * @throws \LogicException
     * @return Response
     */
    public function handleRequest(Request $request)
    {
        $currentStepName = $this->resolveCurrentStepName();
        $this->currentProcessedStepName = $currentStepName;

        $currentStep = $this->getFlattenedFlow()->getStep($currentStepName);

        $response = $this->raisePreHandleEvent($request, $currentStepName);
        $response = $this->doHandleRequest($request, $response, $currentStep, $currentStepName);

        $response = $this->raisePostHandleEvent($request, $currentStepName, $response);
        if ($response instanceof Response) {
            return $response;
        }

        /** @var $response ResolvedNavigationResponse */
        if ($response->getNavigationDirection() === NavigationDirection::BACK()) {
            $stepRange = $this->getFlattenedFlow()->getRange($currentStepName, $response, $this->getData(), true, false);
            foreach ($stepRange as $rangeStepName => $rangeStep) {
                if ($this->isStepEligibleForNavigation($rangeStepName, $response, $this->getData())) {
                    $rangeStep->onBack($request, $this->getData());
                } else {
                    $rangeStep->onSkipped($request, $response, $this->getData());
                }
            }
        } elseif ($response->getNavigationDirection() === NavigationDirection::NEXT()) {
            $stepRange = $this->getFlattenedFlow()->getRange($currentStepName, $response, $this->getData(), true, false);
            foreach ($stepRange as $rangeStepName => $rangeStep) {
                if ($this->isStepEligibleForNavigation($rangeStepName, $response, $this->getData())) {
                    $rangeStep->onNext($request, $this->getData());
                } else {
                    $rangeStep->onSkipped($request, $response, $this->getData());
                }
            }
        }

        $this->setCurrentStepNameToData($response->getTargetStepName());

        return $this->getNavigationHttpResponse($request, $response);
    }

    /**
     * @param Request $request
     * @param         $currentStepName
     *
     * @return NavigationResponse|null|Response
     */
    private function raisePreHandleEvent(Request $request, $currentStepName)
    {
        $response = null;
        if ($this->getEventDispatcher() !== null) {
            $event = new FlowManagerRequestEvent($currentStepName, $this->getFlattenedFlow(), $this->getData(), $request);

            $this->getEventDispatcher()->dispatch(FlowManagerEvents::PRE_HANDLE_REQUEST_EVENT, $event);
            $response = $event->getResponse();

            if ($response instanceof NavigationResponse) {
                $response = $this->resolveNavigationResponse($response, $currentStepName);
                $this->setCurrentNavigationResponse($response);
            } elseif (!($response instanceof Response)) {
                $response = null;
            }
        }

        return $response;
    }

    /**
     * @param Request                     $request
     * @param NavigationResponse|Response $originalResponse
     * @param StepInterface               $currentStep
     * @param string                      $currentStepName
     *
     * @return NavigationResponse|Response
     * @throws \LogicException
     */
    private function doHandleRequest(Request $request, $originalResponse, StepInterface $currentStep, $currentStepName)
    {
        $response = $originalResponse;

        if ($response === null) {
            $response = $currentStep->handleRequest($request, $this->getCurrentNavigationResponse(), $this->getData());
        }

        if ($response instanceof NavigationResponse) {
            $response = $this->resolveNavigationResponse($response, $currentStepName);
            $this->setCurrentNavigationResponse($response);
        } elseif (!($response instanceof Response)) {
            throw new \LogicException("Step $currentStepName should've returned a response.");
        }

        return $response;
    }

    /**
     * @param Request                     $request
     * @param string                      $currentStepName
     * @param NavigationResponse|Response $originalResponse
     *
     * @return NavigationResponse|Response
     * @throws \LogicException
     */
    private function raisePostHandleEvent(Request $request, $currentStepName, $originalResponse)
    {
        $response = $originalResponse;

        if ($this->getEventDispatcher() !== null) {
            $event = new FlowManagerRequestEvent($currentStepName, $this->getFlattenedFlow(), $this->getData(), $request, $originalResponse);
            $this->getEventDispatcher()->dispatch(FlowManagerEvents::POST_HANDLE_REQUEST_EVENT, $event);
            /** @var Response|NavigationResponse|ResolvedNavigationResponse $response */
            $response = $event->getResponse();

            if ($response === $originalResponse && $response instanceof NavigationResponse) {
                // The event might of changed the data model's underlying state, which may cause some eligibility to change;
                // We need to re-resolve the navigationResponse in that case.
                $response = $response->toUnresolvedNavigationResponse();
            }

            if ($response instanceof NavigationResponse) {
                $response = $this->resolveNavigationResponse($response, $currentStepName);
                $this->setCurrentNavigationResponse($response);
            } elseif (!($response instanceof Response)) {
                throw new \LogicException("A post-handle request handler cleared the response.");
            }
        }

        return $response;
    }

    /**
     * @param NavigationResponse $navResponse
     * @param string             $currentStepName
     *
     * @throws \InvalidArgumentException
     * @throws Exception\FlowException
     * @return \SidLee\FlowManager\NavigationResponse
     */
    private function resolveNavigationResponse(NavigationResponse $navResponse, $currentStepName)
    {
        if ($navResponse === null) {
            throw new \InvalidArgumentException('The parameter $response is required.');
        }

        if ($navResponse instanceof ResolvedNavigationResponse) {
            return $navResponse;
        }

        $navDirection = $navResponse->getNavigationDirection();
        $targetStepName = $navResponse->getTargetStepName();
        $wasDirect = false;

        if ($navDirection === NavigationDirection::BACK()) {
            $targetStepName = $this->getFlattenedFlow()->resolvePreviousStepName($navResponse, $currentStepName, $this->getData());
        } elseif ($navDirection === NavigationDirection::NEXT()) {
            $targetStepName = $this->getFlattenedFlow()->resolveNextStepName($navResponse, $currentStepName, $this->getData());
        } elseif ($navDirection === NavigationDirection::DIRECT()) {
            $wasDirect = true;

            if ($targetStepName === null) {
                throw new \InvalidArgumentException('The property targetStepName of a direct $navResponse must be set.');
            } elseif ($targetStepName === '.') {
                $targetStepName = $currentStepName;
            } else {
                $targetStepName = str_replace('%root%', $this->key, $targetStepName);
            }

            $navDirection = $this->getFlattenedFlow()->detectDirectNavigationDirection($currentStepName, $targetStepName);

            $navResponseClone = clone $navResponse;
            $navResponseClone->setNavigationDirection($navDirection);

            if (!$this->isStepEligibleForNavigation($targetStepName, $navResponseClone, $this->getData())) {
                throw new FlowException("The step $targetStepName isn't eligible for the current data.");
            }
        }

        if ($navDirection !== null && $targetStepName !== null) {
            return new ResolvedNavigationResponse($navDirection, $targetStepName, $wasDirect);
        }

        throw new FlowException('The direct navigation response couldn\'t be resolved.');
    }

    /**
     * Returns the current step name from the underlying data source.
     *
     * @return string
     */
    abstract protected function getCurrentStepName();

    public final function getResolvedCurrentStepName()
    {
        return $this->resolveCurrentStepName();
    }

    /**
     * Responsible for setting the current step name in the underlying data source.
     *
     * @param string $targetStepName
     *
     * @return mixed
     */
    abstract protected function setCurrentStepNameToData($targetStepName);

    /**
     * @param Request                    $request
     * @param ResolvedNavigationResponse $response
     *
     * @return Response
     */
    abstract protected function getNavigationHttpResponse(Request $request, ResolvedNavigationResponse $response);

    /**
     * Returns the fully-qualified step name of the current step.
     *
     * @return string
     * @throws Exception\FlowException
     */
    protected function resolveCurrentStepName()
    {
        $currentStepName = $this->getCurrentStepName();
        if ($currentStepName === null) {
            $initialNavResponse = new NavigationResponse();
            $initialNavResponse->setNavigationDirection(NavigationDirection::DIRECT());

            $currentStepName = $this->getFlattenedFlow()->resolveFirstEligibleStepName($initialNavResponse, $this->getData());
        }

        return $currentStepName;
    }
}