<?php

namespace ThisPageCannotBeFound\Silex;

use Silex\Application;

/**
 * @author Abel de Beer <abel@thispagecannotbefound.com>
 */
interface ApplicationAwareInterface {

	/**
	 * Set the application.
	 *
	 * @param Application $app
	 */
	function setApplication(Application $app);

}
