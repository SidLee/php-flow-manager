SidLee\Flow-Manager
===================

Library to assist in the creation and management of complex wizard flows in projects using Symfony2's HttpFoundation component.

## Library Maintainers

 - [Daniel Petitclerc](dpetitclerc@sidlee.com)
 - [Andrew Moore](amoore@sidlee.com)

## Usage

### Creating a Step

In order to create a step, you must create a class implementing the interface `SidLee\FlowManager\StepInterface`. You may use `SidLee\FlowManager\AbstractStep` as a template if you wish.

	use SidLee\FlowManager\AbstractStep;
    use SidLee\FlowManager\NavigationResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;

    class WizardStartStep extends AbstractStep
    {
		public function handleRequest(Request $request, NavigationResponse $navResponse, $data) {
			return new Response("Hello World");
        }
    }

In order to cause a navigation within the flow, simply return a `SidLee\FlowManager\NavigationResponse` instead of a `Symfony\Component\HttpFoundation\Response`.

	use SidLee\FlowManager\AbstractStep;
    use SidLee\FlowManager\NavigationDirection;
    use SidLee\FlowManager\NavigationResponse;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;

    class WizardStartStep extends AbstractStep
    {
		public function handleRequest(Request $request, NavigationResponse $navResponse, $data) {
            if($request->request->getAlpha('navigation') === 'NEXT') {
                return new NavigationResponse(NavigationDirection::NEXT());
            }
			return new Response("Hello World");
        }
    }

### Creating a flow

The wizard (or flow) is created and managed via a specialized collection called `SidLee\FlowManager\StepCollection`.

In order to create a flow, you must first populate your `StepCollection` with the desired steps (and names).

    use SidLee\FlowManager\StepCollection;

    $stepCollection = new StepCollection();
    $stepCollection->add('start', new WizardStartStep());
    $stepCollection->add('accountInfo', new AccountInformationStep());
    $stepCollection->add('confirmAccountInfo', new AccountInformationConfirmationStep());
    $stepCollection->add('welcome', new WelcomeStep());

For more complex flows, you can also nest `StepCollection` instances (use `add()` with a `StepCollection` instead of a `StepInterface`).

### Using the created flow

Once the flow has been created, you must create an implementation of `SidLee\FlowManager\AbstractFlowManager` that will be able to manage the flow.

You must implement following functions:

 - `getCurrentStepName()`  
   This function is responsible for fetching the current step name from the underlying data source.

 - `setCurrentStepNameToData()`
   This function is responsible for setting the current step name in the underlying data source.

 - `getNavigationHttpResponse()`
   This function returns a `Symfony\Component\HttpFoundation\Response` whenever a navigation between steps is required.

Once your implementation is completed, you then have create a instance of your `FlowManager` by specifying a root key and the `StepCollection` representing your flow. You may also pass the desired `EventDispatcher` to be used for events.

## Step Identifiers

Steps will be assigned a string identified based on the name and their nesting level in the `StepCollection` as well as the root key passed to the `FlowManager`.

Assuming that you have the following structure inside a `StepCollection`:

    - wizard_start
    - registration_subflow
	    - account_information
	    - credentials_information
	    - captcha
    - download_client
    - welcome

and that your root key in the `FlowManager` is `"firstTimeSetup"`. The steps will be given the following fully qualified identifiers:

    - firstTimeSetup.wizard_start
    - firstTimeSetup.registration_subflow.account_information
    - firstTimeSetup.registration_subflow.credentials_information
    - firstTimeSetup.registration_subflow.captcha
    - firstTimeSetup.download_client
    - firstTimeSetup.welcome

These identifiers can be used to navigate directly to a step using `NavigationDirection::DIRECT()`.