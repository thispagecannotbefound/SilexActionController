<?php

namespace ThisPageCannotBeFound\Silex\Tests;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use ThisPageCannotBeFound\Silex\ActionControllerResolver;

/**
 * @author Abel de Beer <abel@thispagecannotbefound.com>
 */
class ActionControllerResolverTest extends \PHPUnit_Framework_TestCase {

	public function setup() {
		$this->mockResolver = $this->getMockBuilder('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface')
				->disableOriginalConstructor()
				->getMock();

		$this->app = new Application();
		$this->resolver = new ActionControllerResolver($this->mockResolver, $this->app);
	}

	public function testClosureServiceShouldResolveServiceController() {
		$this->app['some_service'] = function() {
					return new \stdClass();
				};

		$req = Request::create('/');
		$req->attributes->set('_controller', 'some_service:methodName');

		$this->assertEquals(
				array($this->app['some_service'], 'methodName'),
				$this->resolver->getController($req)
		);
	}

	/**
	 * @expectedException			InvalidArgumentException
	 * @expectedExceptionMessage	Service "some_service" does not exist.
	 */
	public function testShouldThrowIfServiceIsMissing() {
		$req = Request::create('/');
		$req->attributes->set('_controller', 'some_service:methodName');
		$this->resolver->getController($req);
	}

	/**
	 * @expectedException			\InvalidArgumentException
	 * @expectedExceptionMessage	Class "FooBar" does not exist.
	 */
	public function testClassNameDoesNotExistShouldThrow() {
		$this->app['some_service'] = 'FooBar';

		$req = Request::create('/');
		$req->attributes->set('_controller', 'some_service:example');

		$this->resolver->getController($req);
	}

	public function testClassNameServiceControllerShouldCreateInstanceOfClass() {
		$className = Support\ServiceController::__CLASS;

		$this->app['some_service'] = $className;

		$req = Request::create('/');
		$req->attributes->set('_controller', 'some_service:example');

		$resolved = $this->resolver->getController($req);

		$this->assertInstanceOf($className, reset($resolved));
		$this->assertEquals('example', next($resolved));
	}

	/**
	 * @expectedException			\InvalidArgumentException
	 * @expectedExceptionMessage	To route an action controller, make sure the route contains an "action" parameter.
	 */
	public function testActionControllerNoActionParamShouldThrow() {
		$this->app['some_service'] = Support\ServiceController::__CLASS;

		$req = Request::create('/');
		$req->attributes->set('_controller', 'some_service');

		$this->resolver->getController($req);
	}

	public function testActionControllerShouldCreateInstanceAndUseActionParam() {
		$className = Support\ServiceController::__CLASS;

		$this->app['some_service'] = $className;

		$req = Request::create('/');
		$req->attributes->set('_controller', 'some_service');
		$req->attributes->set('action', 'example');

		$resolved = $this->resolver->getController($req);

		$this->assertInstanceOf($className, reset($resolved));
		$this->assertEquals('example', next($resolved));
	}

}
