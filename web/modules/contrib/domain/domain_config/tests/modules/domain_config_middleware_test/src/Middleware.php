<?php

namespace Drupal\domain_config_middleware_test;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

$version = (int) explode('.', \Drupal::VERSION)[0];

if ($version >= 10) {
  /**
   * Middleware for the domain_config_test module.
   */
  class Middleware implements HttpKernelInterface {

    /**
     * The request type.
     *
     * @var int
     */
    public const MAIN_REQUEST = 1;

    /**
     * The decorated kernel.
     *
     * @var \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    protected $httpKernel;

    /**
     * The config factory.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * Constructs a Middleware object.
     *
     * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
     *   The decorated kernel.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The configuration factory.
     */
    public function __construct(HttpKernelInterface $http_kernel, ConfigFactoryInterface $config_factory) {
      $this->httpKernel = $http_kernel;
      $this->configFactory = $config_factory;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = TRUE): Response {
      // This line should break hooks in our code.
      // @see https://www.drupal.org/node/2896434.
      $config = $this->configFactory->get('domain_config_middleware_test.settings');
      return $this->httpKernel->handle($request, $type, $catch);
    }
  }
}
else {

  /**
   * Drupal 9 compatible layer.
   */
  class Middleware implements HttpKernelInterface {

    /**
     * The request type.
     *
     * @var int
     */
    public const MASTER_REQUEST = 1;

    /**
     * The decorated kernel.
     *
     * @var \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    protected $httpKernel;

    /**
     * The config factory.
     *
     * @var \Drupal\Core\Config\ConfigFactoryInterface
     */
    protected $configFactory;

    /**
     * Constructs a Middleware object.
     *
     * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
     *   The decorated kernel.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The configuration factory.
     */
    public function __construct(HttpKernelInterface $http_kernel, ConfigFactoryInterface $config_factory) {
      $this->httpKernel = $http_kernel;
      $this->configFactory = $config_factory;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE): Response {
      // This line should break hooks in our code.
      // @see https://www.drupal.org/node/2896434.
      $config = $this->configFactory->get('domain_config_middleware_test.settings');
      return $this->httpKernel->handle($request, $type, $catch);
    }
  }

}


