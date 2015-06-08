<?php
namespace SidLee\FlowManager;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

interface StepInterface extends StepCollectionAddableInterface
{
    /**
     * Handles the incoming HTTP request if eligible
     *
     * @param Request            $request
     * @param NavigationResponse $navResponse
     * @param                    $data
     * @return Response|NavigationResponse
     */
    public function handleRequest(Request $request, NavigationResponse $navResponse, $data);

    /**
     * Verifies if the step should be displayed to the user or skipped.
     * If isEligibleForNavigation returns false, the step will be skipped.
     *
     * @param \SidLee\FlowManager\NavigationResponse $navResponse
     * @param                                          $data
     * @return boolean
     */
    public function isEligibleForNavigation(NavigationResponse $navResponse, $data);

    /**
     * Simple event hook that is called if this step is navigated over during a BACK operation.
     *
     * @param Request $request
     * @param         $data
     * @return void
     */
    public function onBack(Request $request, $data);

    /**
     * Simple event hook that is called if this step is navigated over during a NEXT operation.
     *
     * @param Request $request
     * @param         $data
     * @return void
     */
    public function onNext(Request $request, $data);

    /**
     * Simple event hook that is called if this step is skipped during a navigation operation.
     *
     * @param Request            $request
     * @param NavigationResponse $navResponse
     * @param                    $data
     * @return void
     */
    public function onSkipped(Request $request, NavigationResponse $navResponse, $data);
} 