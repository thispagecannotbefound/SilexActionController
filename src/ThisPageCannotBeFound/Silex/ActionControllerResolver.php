<?php

namespace ThisPageCannotBeFound\Silex;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

/**
 * @author Abel de Beer <abel@thispagecannotbefound.com>
 */
class ActionControllerResolver implements ControllerResolverInterface {

	const SERVICE_PATTERN = "/^([\w\.\-]+)(:[a-z_\x7f-\xff][\w\x7f-\xff]*)?$/i";

	/**
	 * @var ControllerResolverInterface
	 */
	protected $resolver;

	/**
	 * @var Application
	 */
	protected $app;

	/**
	 * Constructor.
	 *
	 * @param ControllerResolverInterface $resolver A ControllerResolverInterface instance to delegate to
	 * @param Application                 $app      An Application instance
	 */
	public function __construct(ControllerResolverInterface $resolver,
			Application $app) {
		$this->resolver = $resolver;
		$this->app = $app;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getController(Request $request) {
		$controller = $request->attributes->get('_controller', null);

		if (!is_string($controller) || !preg_match(static::SERVICE_PATTERN,
						$controller, $matches)) {
			return $this->resolver->getController($request);
		}

		if (!empty($matches[2])) {
			// controller contains method - service controller
			list($service, $method) = explode(':', $controller, 2);
		} else {
			// controller does not contain method - action controller
			$service = $controller;

			if (!$request->attributes->has('action')) {
				throw new \InvalidArgumentException('To route an action controller, make sure the route contains an "action" parameter.');
			}

			// method is action parameter
			$method = $request->attributes->get('action');
		}

		if (!isset($this->app[$service])) {
			throw new \InvalidArgumentException(sprintf('Service "%s" does not exist.',
					$service));
		}

		$class = $this->app[$service];

		// create instance if value is class name
		if (!is_object($class)) {
			if (!class_exists($class)) {
				throw new \InvalidArgumentException(sprintf('Class "%s" does not exist.',
						$class));
			}

			$class = new $class;
		}

		// check if method exists
		if (!method_exists($class, $method) &&
				method_exists($class, $method . 'Action')) {
			$method .= 'Action';
		}

		if ($class instanceof ApplicationAwareInterface) {
			$class->setApplication($this->app);
		}

		return array($class, $method);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getArguments(Request $request, $controller) {
		return $this->resolver->getArguments($request, $controller);
	}

}
