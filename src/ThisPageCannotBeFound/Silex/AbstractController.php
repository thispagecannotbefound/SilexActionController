<?php

namespace ThisPageCannotBeFound\Silex;

use Monolog\Logger;
use RuntimeException;
use Silex\Application;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Abel de Beer <abel@thispagecannotbefound.com>
 */
abstract class AbstractController implements ApplicationAwareInterface {

	/** @var Application */
	protected $app;

	/**
	 * Set the application.
	 *
	 * @param Application $app
	 */
	public function setApplication(Application $app) {
		$this->app = $app;
	}

	/* FORM */

	/**
	 * Creates and returns a form builder instance
	 *
	 * @param mixed $data    The initial data for the form
	 * @param array $options Options for the form
	 *
	 * @return FormBuilder
	 */
	public function form($data = null, array $options = array()) {
		return $this->app['form.factory']->createBuilder('form', $data, $options);
	}

	/* MONOLOG */

	/**
	 * Adds a log record.
	 *
	 * @param string  $message The log message
	 * @param array   $context The log context
	 * @param integer $level   The logging level
	 *
	 * @return boolean Whether the record has been processed
	 */
	public function log($message, array $context = array(), $level = Logger::INFO) {
		return $this->app['monolog']->addRecord($level, $message, $context);
	}

	/* SECURITY */

	/**
	 * Gets a user from the Security Context.
	 *
	 * @return mixed
	 *
	 * @see TokenInterface::getUser()
	 */
	public function user() {
		if (null === $token = $this->app['security']->getToken()) {
			return null;
		}

		if (!is_object($user = $token->getUser())) {
			return null;
		}

		return $user;
	}

	/**
	 * Encodes the raw password.
	 *
	 * @param UserInterface $user     A UserInterface instance
	 * @param string        $password The password to encode
	 *
	 * @return string The encoded password
	 *
	 * @throws \RuntimeException when no password encoder could be found for the user
	 */
	public function encodePassword(UserInterface $user, $password) {
		return $this->app['security.encoder_factory']->getEncoder($user)->encodePassword($password,
						$user->getSalt());
	}

	/* SWIFTMAILER */

	/**
	 * Sends an email.
	 *
	 * @param \Swift_Message $message          A \Swift_Message instance
	 * @param array          $failedRecipients An array of failures by-reference
	 *
	 * @return int The number of sent messages
	 */
	public function mail(\Swift_Message $message, &$failedRecipients = null) {
		return $this->app['mailer']->send($message, $failedRecipients);
	}

	/* TRANSLATION */

	/**
	 * Translates the given message.
	 *
	 * @param string $id         The message id
	 * @param array  $parameters An array of parameters for the message
	 * @param string $domain     The domain for the message
	 * @param string $locale     The locale
	 *
	 * @return string The translated string
	 */
	public function trans($id, array $parameters = array(), $domain = 'messages',
			$locale = null) {
		return $this->app['translator']->trans($id, $parameters, $domain, $locale);
	}

	/**
	 * Translates the given choice message by choosing a translation according to a number.
	 *
	 * @param string  $id         The message id
	 * @param integer $number     The number to use to find the indice of the message
	 * @param array   $parameters An array of parameters for the message
	 * @param string  $domain     The domain for the message
	 * @param string  $locale     The locale
	 *
	 * @return string The translated string
	 */
	public function transChoice($id, $number, array $parameters = array(),
			$domain = 'messages', $locale = null) {
		return $this->app['translator']->transChoice($id, $number, $parameters,
						$domain, $locale);
	}

	/* TWIG */

	/**
	 * Renders a view and returns a Response.
	 *
	 * To stream a view, pass an instance of StreamedResponse as a third argument.
	 *
	 * @param string   $view       The view name
	 * @param array    $parameters An array of parameters to pass to the view
	 * @param Response $response   A Response instance
	 *
	 * @return Response A Response instance
	 */
	public function render($view, array $parameters = array(),
			Response $response = null) {
		if (null === $response) {
			$response = new Response();
		}

		$twig = $this->app['twig'];

		if ($response instanceof StreamedResponse) {
			$response->setCallback(function () use ($twig, $view, $parameters) {
						$twig->display($view, $parameters);
					});
		} else {
			$response->setContent($twig->render($view, $parameters));
		}

		return $response;
	}

	/**
	 * Renders a view.
	 *
	 * @param string $view       The view name
	 * @param array  $parameters An array of parameters to pass to the view
	 *
	 * @return Response A Response instance
	 */
	public function renderView($view, array $parameters = array()) {
		return $this->app['twig']->render($view, $parameters);
	}

	/* URL GENERATOR */

	/**
	 * Generates a path from the given parameters.
	 *
	 * @param string $route      The name of the route
	 * @param mixed  $parameters An array of parameters
	 *
	 * @return string The generated path
	 */
	public function path($route, $parameters = array()) {
		return $this->app['url_generator']->generate($route, $parameters, false);
	}

	/**
	 * Generates an absolute URL from the given parameters.
	 *
	 * @param string $route      The name of the route
	 * @param mixed  $parameters An array of parameters
	 *
	 * @return string The generated URL
	 */
	public function url($route, $parameters = array()) {
		return $this->app['url_generator']->generate($route, $parameters, true);
	}

	/* APPLICATION CONTROLLER FUNCTIONALITY */

	/**
	 * Aborts the current request by sending a proper HTTP error.
	 *
	 * @param integer $statusCode The HTTP status code
	 * @param string  $message    The status message
	 * @param array   $headers    An array of HTTP headers
	 */
	public function abort($statusCode, $message = '', array $headers = array()) {
		throw new HttpException($statusCode, $message, null, $headers);
	}

	/**
	 * Redirects the user to another URL.
	 *
	 * @param string  $url    The URL to redirect to
	 * @param integer $status The status code (302 by default)
	 *
	 * @return RedirectResponse
	 */
	public function redirect($url, $status = 302) {
		return new RedirectResponse($url, $status);
	}

	/**
	 * Creates a streaming response.
	 *
	 * @param mixed   $callback A valid PHP callback
	 * @param integer $status   The response status code
	 * @param array   $headers  An array of response headers
	 *
	 * @return StreamedResponse
	 */
	public function stream($callback = null, $status = 200, $headers = array()) {
		return new StreamedResponse($callback, $status, $headers);
	}

	/**
	 * Escapes a text for HTML.
	 *
	 * @param string  $text         The input text to be escaped
	 * @param integer $flags        The flags (@see htmlspecialchars)
	 * @param string  $charset      The charset
	 * @param boolean $doubleEncode Whether to try to avoid double escaping or not
	 *
	 * @return string Escaped text
	 */
	public function escape($text, $flags = ENT_COMPAT, $charset = null,
			$doubleEncode = true) {
		return htmlspecialchars($text, $flags, $charset ? : $this->app['charset'],
				$doubleEncode);
	}

	/**
	 * Convert some data into a JSON response.
	 *
	 * @param mixed   $data    The response data
	 * @param integer $status  The response status code
	 * @param array   $headers An array of response headers
	 *
	 * @return JsonResponse
	 */
	public function json($data = array(), $status = 200, $headers = array()) {
		return new JsonResponse($data, $status, $headers);
	}

	/**
	 * Sends a file.
	 *
	 * @param \SplFileInfo|string $file               The file to stream
	 * @param integer             $status             The response status code
	 * @param array               $headers            An array of response headers
	 * @param null|string         $contentDisposition The type of Content-Disposition to set automatically with the filename
	 *
	 * @return BinaryFileResponse
	 *
	 * @throws RuntimeException When the feature is not supported, before http-foundation v2.2
	 */
	public function sendFile($file, $status = 200, $headers = array(),
			$contentDisposition = null) {
		return new BinaryFileResponse($file, $status, $headers, true,
				$contentDisposition);
	}

}
