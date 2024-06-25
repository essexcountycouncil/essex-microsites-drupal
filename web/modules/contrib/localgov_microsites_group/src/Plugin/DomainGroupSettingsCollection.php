<?php

namespace Drupal\localgov_microsites_group\Plugin;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of domain group settings plugins.
 */
class DomainGroupSettingsCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   *
   * Sorts plugins by provider.
   */
  public function sortHelper($aID, $bID) {
    $a = $this->get($aID);
    $b = $this->get($bID);

    if ($a->getProvider() != $b->getProvider()) {
      return strnatcasecmp($a->getProvider(), $b->getProvider());
    }

    return parent::sortHelper($aID, $bID);
  }

}
