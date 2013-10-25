<?php

namespace ThisPageCannotBeFound\Silex\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use ThisPageCannotBeFound\Silex\ActionControllerResolver;

/**
 * @author Abel de Beer <abel@thispagecannotbefound.com>
 */
class ActionControllerServiceProvider implements ServiceProviderInterface {

	public function register(Application $app) {
		$app['resolver'] = $app->share($app->extend('resolver',
						function ($resolver, $app) {
							return new ActionControllerResolver($resolver, $app);
						}));
	}

	public function boot(Application $app) {
		// noop
	}

}
