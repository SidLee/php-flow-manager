<?php
namespace SidLee\FlowManager;

use Symfony\Component\HttpFoundation\Request;

abstract class AbstractStep implements StepInterface
{
    /**
     * @inheritdoc
     */
    public abstract function handleRequest(Request $request, NavigationResponse $navResponse, $data);

    /**
     * @inheritdoc
     */
    public function isEligibleForNavigation(NavigationResponse $navResponse, $data)
    {
        // A step based on AbstractStep is always eligible. Override if logic is needed.
        return true;
    }

    /**
     * @inheritdoc
     */
    public function onBack(Request $request, $data)
    {
        // A step based on AbstractStep does nothing on this event. Override if needed.
    }

    /**
     * @inheritdoc
     */
    public function onNext(Request $request, $data)
    {
        // A step based on AbstractStep does nothing on this event. Override if needed.
    }

    /**
     * @inheritdoc
     */
    public function onSkipped(Request $request, NavigationResponse $navResponse, $data)
    {
        // A step based on AbstractStep does nothing on this event. Override if needed.
    }
}