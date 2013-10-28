# SilexActionController

Extended controller functionality for [Silex](http://silex.sensiolabs.org).


## Introduction -- workflow

I have developed a personal workflow in which I organize my controllers in
classes, while keeping Silex functionality. This is different from Silex's
[ServiceControllerServiceProvider](http://silex.sensiolabs.org/doc/providers/service_controller.html),
because that is meant to completely separate your controllers from the framework.
In my controllers I want to have the framework functionality, for example to
redirect requests or return a JSON response. Small example controller:

	class UserController
	{
		function getUser($name) {
			$user = $this->app['service.user']->getByName($name);

			if ($user) {
				return $this->app['twig']->render('user.twig');
			}

			return $this->app->redirect('/noUser');
		}
	}

I like this way of organizing my code, which to some extent resembles Symfony 2's
and Zend Framework 2's method of controller classes. This Silex extension makes
this workflow less verbose.


## README example classes

To explain the usage of this extension, the following example classes are used:


#### Example\ServiceController

	namespace Example;

	class ServiceController
	{
		function show() {
			return __METHOD__;
		}
	}

#### Example\ActionController

	namespace Example;

	use Silex\Application;
	use ThisPageCannotBeFound\Silex\ApplicationAwareInterface;

	class ApplicationAwareController implements ApplicationAwareInterface
	{
		protected $app;

		function setApplication(Application $app) {
			$this->app = $app;
		}

		function show() {
			return __METHOD__;
		}
	}


## Usage


### ActionControllerServiceProvider

First you'll need to register the provider with your app, after which you can
define controllers:

	use Silex\Application;
	use ThisPageCannotBeFound\Silex\Provider\ActionControllerServiceProvider;

	$app = new Application();

	$app->register(new ActionControllerServiceProvider());

	// Map controller, comparable to Silex's ServiceControllerServiceProvider,
	// except by using just the controller class name. The service provider will
	// create the class instance for you.
	$app['controller.example.service'] = 'Example\\ServiceController';
	$app['controller.example.action'] = 'Example\\ActionController';

#### Defining routes: 'service controller'

	// Map route to a class method.
	$app->get('/example', 'controller.example.service:show');

#### Defining routes: 'action controller'

	// Map route to an entire class, method will be the action parameter.
	$app->get('/example/{action}', 'controller.example.action');


### ApplicationAwareInterface

Implement this interface to have the ActionControllerServiceProvider inject a
Silex app instance into the class. (see ApplicationAwareController example class)


### AbstractController

This class provides helper methods to make the calls cleaner. It contains the
Silex Application request - response methods (e.g. redirect, abort) and the
methods from the default service providers, copied from their traits. It also
implements the ApplicationAwareInterface, so it receives the Application
instance upon instantiation. This class can be extended to add functionality to
your controller classes.

Example - revised UserController:

	class UserController extends AbstractController
	{
		function getUser($name) {
			$user = $this->app['service.user']->getByName($name);

			if ($user) {
				return $this->render('user.twig');
			}

			return $this->redirect('/noUser');
		}
	}
