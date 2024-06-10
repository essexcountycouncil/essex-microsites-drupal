<?php

namespace Drupal\domain_path;

/**
 * Provides an interface for domain paths.
 */
interface DomainPathInterface {

  /**
   * Get source for domain_path.
   *
   * @return string
   *   Returns domain path source.
   */
  public function getSource();

  /**
   * Get alias for domain_path.
   *
   * @return string
   *   Returns domain path alias.
   */
  public function getAlias();

  /**
   * Get language code for domain_path.
   *
   * @return string
   *   Returns domain path language code.
   */
  public function getLanguageCode();

  /**
   * Get domain id for domain_path.
   *
   * @return string
   *   Returns domain path domain id.
   */
  public function getDomainId();

}
