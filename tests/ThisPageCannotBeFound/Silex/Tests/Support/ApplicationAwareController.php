<?php

namespace ThisPageCannotBeFound\Silex\Tests\Support;

use ThisPageCannotBeFound\Silex\ApplicationAwareInterface;

/**
 * @author Abel de Beer <abel@thispagecannotbefound.com>
 */
class ApplicationAwareController implements ApplicationAwareInterface {

	const __CLASS = __CLASS__;

	public $app;

	public function setApplication(\Silex\Application $app) {
		$this->app = $app;
	}

}
