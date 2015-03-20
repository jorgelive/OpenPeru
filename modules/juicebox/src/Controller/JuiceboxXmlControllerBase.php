<?php

/**
 * @file
 * Controller routines for Juicebox XML.
 */

namespace Drupal\juicebox\Controller;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Controller routines for Juicebox XML.
 */
abstract class JuiceboxXmlControllerBase implements ContainerInjectionInterface {

  protected $configFactory;
  protected $request;
  protected $httpKernel;
  protected $settings = array();

  /**
   * Constructor
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal config factory that can be used to derive global Juicebox
   *   settings.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The Symfony request stack from which to extract the current request.
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The Symfony http kernel service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, HttpKernelInterface $http_kernel) {
    $this->configFactory = $config_factory;
    // Fetch and store the Juicebox-specific global settings.
    $this->settings = $config_factory->get('juicebox.settings')->get();
    $this->request = $request_stack->getCurrentRequest();
    $this->httpKernel = $http_kernel;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Create the actual controller instance.
    return new static($container->get('config.factory'), $container->get('request_stack'), $container->get('http_kernel'));
  }

  /**
   * Common controller for the Juicebox XML.
   *
   * @param string $args
   *   The arguments passed from the router.
   * @return Response $xml
   *   A Symfony response object containing the XML information.
   */
  public function xmlController($args = '') {
    $xml = '';
    // If we have xml-source query parameters this indicates that the XML can
    // probably not be generated here from scratch. Instead we must depend on a
    // sub-request to another Drupal path (e.g., the gallery page) and search
    // for embedded XML there. This is an experimental method for special cases.
    $query = $this->request->query->all();
    if (isset($query['xml-source-path']) && isset($query['xml-source-id'])) {
      $xml = $this->fetchXmlSubRequest($query['xml-source-path'], $query['xml-source-id']);
    }
    // If a sub-request XML lookup does not apply then we build the gallery and
    // its XML from scratch. This is the more common and preferred method.
    if (empty($xml)) {
      try {
        // Initialize data and test access.
        $this->init($args);
        if (!$this->access()) {
          throw new AccessDeniedHttpException();
        }
        $gallery = $this->getGallery();
        $xml = $gallery->renderXml();
      }
      catch (\Exception $e) {
        // An access denied exception should just be re-thrown.
        if ($e instanceof AccessDeniedHttpException) {
          throw $e;
        }
        // Otherwise we have an error. Log it and trigger a general 404
        // response.
        $message = 'Exception building Juicebox XML: !message in %function (line %line of %file).';
        watchdog_exception('juicebox', $e, $message);
        throw new NotFoundHttpException();
      }
    }
    // Calculate headers.
    $headers = array(
      'Content-Type' => 'application/xml; charset=utf-8',
      'X-Robots-Tag' => 'noindex',
    );
    if ($this->settings['enable_cors']) {
      $headers['Access-Control-Allow-Origin'] = '*';
    }
    // Also capture relevant Drupal cache tags that may aid in cache cleanup.
    $headers['X-Drupal-Cache-Tags'] = implode(' ', $this->calculateXmlCacheTags());
    // Package the XML as a Response object.
    return new Response($xml, 200, $headers);
  }

  /**
   * Attempt to fetch the gallery's XML via a sub-request to another page.
   *
   * This assumes that the gallery XML has already been embedded within a normal
   * HTML page, at the given path, within a <script> block.
   *
   * @param string $path
   *   The Drupal path to use for the sub-request.
   * @param string $id
   *   The id to search for within the sub-request content that will contain
   *   the embedded XML.
   * @return string
   *   The embedded XML if found or an empty string.
   */
  protected function fetchXmlSubRequest($path, $id) {
    $xml = '';
    // We want to pass-through all details of the master request, but for some
    // reason the sub-request may fail with a 406 if some server params unique
    // to an XMLHttpRequest are used. So we reset those to generic values by
    // just removing them from the request details passed-through.
    $server = $this->request->server;
    $server->remove('HTTP_ACCEPT');
    $server->remove('HTTP_X_REQUESTED_WITH');
    $subRequest = Request::create($this->request->getBaseUrl() . '/' . $path, 'GET', $this->request->query->all(), $this->request->cookies->all(), $this->request->files->all(), $server->all());
    // @todo: See if this session check is needed.
    $session = $this->request->getSession();
    if ($session) {
      $subRequest->setSession($session);
    }
    $subResponse = $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    // Search for the XML within the sub-request markup. We could parse the
    // DOM for this with DOMDocument, but a regex lookup is more lightweight.
    $matches = array();
    preg_match('/<script[^>]*id=\"' . $id . '\"[^>]*>(.*)<\/script>/simU', $subResponse->getContent(), $matches);
    if (!empty($matches[1]) && strpos($matches[1], '<?xml') === 0) {
      $xml = $matches[1];
    }
    return $xml;
  }

  /**
   * Calculate any cache tags that should be applied to the XML.
   *
   * @return array
   *   An indexed array of cache tags.
   */
  protected function calculateXmlCacheTags() {
    // At a minimum we can tag everything as Juicebox related. Other controllers
    // that extend this one may add other tags based on args, etc.
    return array('juicebox_gallery');
  }

  /**
   * Initialize the controller based on the passed arg data.
   *
   * @param string $args
   *   The arguments passed from the router.
   */
  abstract protected function init($args);

  /**
   * Check access to the Drupal data that will be used to build the gallery.
   *
   * @return boolean
   *   Returns TRUE if access is allowed for the current user and FALSE if not.
   *   Can also return NULL if access cannot be determined.
   */
  abstract protected function access();

  /**
   * Get the Juicebox gallery object.
   *
   * @return Drupal\juicebox\JuiceboxGalleryInterface
   *   A Juicebox gallery object.
   */
  abstract protected function getGallery();

}
