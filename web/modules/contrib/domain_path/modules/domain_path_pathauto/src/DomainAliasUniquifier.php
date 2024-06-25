<?php

namespace Drupal\domain_path_pathauto;

use Drupal\Core\Language\LanguageInterface;
use Drupal\pathauto\AliasUniquifier;
use Drupal\Component\Utility\Unicode;

/**
 * Provides a utility for creating a unique domain path alias.
 */
class DomainAliasUniquifier extends AliasUniquifier {

  /**
   * {@inheritdoc}
   */
  public function uniquify(&$alias, $source, $langcode, $domain_id = '') {
    $config = $this->configFactory->get('pathauto.settings');

    if (!$this->isReserved($alias, $source, $langcode, $domain_id)) {
      return;
    }

    // If the alias already exists, generate a new, hopefully unique, variant.
    $maxlength = min($config->get('max_length'), $this->aliasStorageHelper->getAliasSchemaMaxlength());
    $separator = $config->get('separator');
    $original_alias = $alias;

    $i = 0;
    do {
      // Append an incrementing numeric suffix until we find a unique alias.
      $unique_suffix = $separator . $i;
      $alias = Unicode::truncate($original_alias, $maxlength - mb_strlen($unique_suffix), TRUE) . $unique_suffix;
      $i++;
    } while ($this->isReserved($alias, $source, $langcode, $domain_id));
  }

  /**
   * {@inheritdoc}
   */
  public function isReserved($alias, $source, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED, $domain_id = '') {

    // Check if this domain alias already exists.
    $query = \Drupal::database()->select('domain_path', 'domain_path')
      ->fields('domain_path', ['language', 'source', 'alias'])
      ->condition('domain_id', $domain_id)
      ->condition('alias', $alias);
    $result = $query->execute()->fetchAssoc();

    if(isset($result['source'])) {
      $existing_source = $result["source"];
      if ($existing_source != $alias) {
        // If it is an alias for the provided source, it is allowed to keep using
        // it. If not, then it is reserved.
        return $existing_source != $source;
      }
    }

    // Then check if there is a route with the same path.
    if ($this->isRoute($alias)) {
      return TRUE;
    }

    // Finally check if any other modules have reserved the alias.
    $return_value = FALSE;
    $this->moduleHandler->invokeAllWith('pathauto_is_alias_reserved', function (callable $hook) use ($alias, $source, $langcode, &$return_value) {
      if ($return_value) {
        // As soon as the first module says that an alias is in fact reserved,
        // then there is no point in checking the rest of the modules.
        return;
      }
      $result = $hook($alias, $source, $langcode);
      $return_value = !empty($result);
    });

    return $return_value;
  }

}
