<?php

namespace Drupal\localgov_microsites_group\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\localgov_microsites_group\Entity\MicrositeGroupInterface;

/**
 * Negotiate the current theme based on theme_switcher_rules rules.
 */
class ThemeSwitcherNegotiator implements ThemeNegotiatorInterface {

  /**
   * System theme configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $systemTheme;

  /**
   * ThemeSwitcherNegotiator constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->systemTheme = $config_factory->get('system.theme');
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getParameter('group') instanceof MicrositeGroupInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->systemTheme->get('admin');
  }

}
