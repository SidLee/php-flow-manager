<?php
/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
namespace SidLee\FlowManager;

use Eloquent\Enumeration\AbstractEnumeration;

/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */

/**
 * Class NavigationDirection
 * @package Ergonet\Library\Flow
 * @method static \SidLee\FlowManager\NavigationDirection BACK();
 * @method static \SidLee\FlowManager\NavigationDirection NEXT();
 * @method static \SidLee\FlowManager\NavigationDirection DIRECT();
 */
class NavigationDirection extends AbstractEnumeration
{
    /**
     * We want to go back one step.
     */
    const BACK = 'back';
    /**
     * We want to go forward one step.
     */
    const NEXT = 'next';
    /**
     * We want to go to a specific step, whether it's forward or backward in the flow.
     */
    const DIRECT = 'direct';
}