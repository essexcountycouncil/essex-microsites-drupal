<?php

namespace Drupal\domain_path_pathauto;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\pathauto\PathautoGenerator;

/**
 * Provides methods for generating domain path aliases.
 * @see \Drupal\pathauto\PathautoGenerator
 *
 * For now, only op "return" is supported.
 */
class DomainPathautoGenerator extends PathautoGenerator {


  /**
   * {@inheritdoc}
   */
  public function createEntityAlias(EntityInterface $entity, $op, $domain_id = '') {
    // Retrieve and apply the pattern for this content type.
    $pattern = $this->getPatternByEntity($entity);
    if (empty($pattern)) {
      // No pattern? Do nothing (otherwise we may blow away existing aliases...)
      return NULL;
    }

    try {
      $internalPath = $entity->toUrl()->getInternalPath();
    }
      // @todo convert to multi-exception handling in PHP 7.1.
    catch (EntityMalformedException $exception) {
      return NULL;
    }
    catch (UndefinedLinkTemplateException $exception) {
      return NULL;
    }
    catch (\UnexpectedValueException $exception) {
      return NULL;
    }

    $source = '/' . $internalPath;
    $config = $this->configFactory->get('pathauto.settings');
    $langcode = $entity->language()->getId();

    // Core does not handle aliases with language Not Applicable.
    if ($langcode == LanguageInterface::LANGCODE_NOT_APPLICABLE) {
      $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED;
    }

    // Build token data.
    $data = [
      $this->tokenEntityMapper->getTokenTypeForEntityType($entity->getEntityTypeId()) => $entity,
    ];

    // Allow other modules to alter the pattern.
    $context = [
      'module' => $entity->getEntityType()->getProvider(),
      'op' => $op,
      'source' => $source,
      'data' => $data,
      'bundle' => $entity->bundle(),
      'language' => &$langcode,
      'domain_id' => $domain_id,
    ];
    $pattern_original = $pattern->getPattern();
    $this->moduleHandler->alter('pathauto_pattern', $pattern, $context);
    $pattern_altered = $pattern->getPattern();

    // Special handling when updating an item which is already aliased.
    $existing_alias = NULL;
    if ($op == 'update' || $op == 'bulkupdate') {
      if ($existing_alias = $this->aliasStorageHelper->loadBySource($source, $langcode)) {
        switch ($config->get('update_action')) {
          case PathautoGeneratorInterface::UPDATE_ACTION_NO_NEW:
            // If an alias already exists,
            // and the update action is set to do nothing,
            // then gosh-darn it, do nothing.
            return NULL;
        }
      }
    }

    // Replace any tokens in the pattern.
    // Uses callback option to clean replacements. No sanitization.
    // Pass empty BubbleableMetadata object to explicitly ignore cacheablity,
    // as the result is never rendered.
    $alias = $this->token->replace($pattern->getPattern(), $data, [
      'clear' => TRUE,
      'callback' => [$this->aliasCleaner, 'cleanTokenValues'],
      'langcode' => $langcode,
      'pathauto' => TRUE,
    ], new BubbleableMetadata());

    // Check if the token replacement has not actually replaced any values. If
    // that is the case, then stop because we should not generate an alias.
    // @see token_scan()
    $pattern_tokens_removed = preg_replace('/\[[^\s\]:]*:[^\s\]]*\]/', '', $pattern->getPattern());
    if ($alias === $pattern_tokens_removed) {
      return NULL;
    }

    $alias = $this->aliasCleaner->cleanAlias($alias);

    // Allow other modules to alter the alias.
    $context['source'] = &$source;
    $context['pattern'] = $pattern;
    $this->moduleHandler->alter('pathauto_alias', $alias, $context);

    // If we have arrived at an empty string, discontinue.
    if (!mb_strlen($alias)) {
      return NULL;
    }

    // If the alias already exists, generate a new, hopefully unique, variant.
    $original_alias = $alias;
    $this->aliasUniquifier->uniquify($alias, $source, $langcode, $domain_id);
    if ($original_alias != $alias) {
      // Alert the user why this happened.
      $this->pathautoMessenger->addMessage($this->t('The automatically generated alias %original_alias conflicted with an existing alias. Alias changed to %alias.', [
        '%original_alias' => $original_alias,
        '%alias' => $alias,
      ]), $op);
    }

    // Return the generated alias if requested.
    if ($op == 'return') {
      return $alias;
    }

    // Build the new path alias array and send it off to be created.
    $path = [
      'source' => $source,
      'alias' => $alias,
      'language' => $langcode,
    ];

    // TODO: allow
    $return = $this->aliasStorageHelper->save($path, $existing_alias, $op);

    // Because there is no way to set an altered pattern to not be cached,
    // change it back to the original value.
    if ($pattern_altered !== $pattern_original) {
      $pattern->setPattern($pattern_original);
    }

    return $return;
  }

  /**
   * Check if "Generate URL alias" is enabled for entity/domain combination.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $domain_id
   *
   * @return boolean
   *   Return TRUE or FALSE.
   */
  public function domainPathPathautoGenerationIsEnabled(EntityInterface $entity, $domain_id) {
    $collection = $this->getDomainPathPathautoStateCollection($entity, $domain_id);
    $value = \Drupal::keyValue($collection)->get($entity->id());
    if($value === NULL) {
      return FALSE;
    }
    return $value;
  }

  /**
   * Returns the key value collection for the given entity/domain combination.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $domain_id
   *
   * @return string
   */
  protected function getDomainPathPathautoStateCollection(EntityInterface $entity, $domain_id) {
    return 'domain_path_pathauto_state.' . $domain_id . '.' . $entity->getEntityTypeId();
  }

  /**
   * Set the stored state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $domain_id
   */
  public function setDomainPathPathautoState($entity, $domain_id, $value) {
    $collection = $this->getDomainPathPathautoStateCollection($entity, $domain_id);
    \Drupal::keyValue($collection)->set($entity->id(), $value);
  }

  /**
   * Deletes the stored state.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $domain_id
   */
  public function deleteDomainPathPathautoState($entity, $domain_id) {
    $collection = $this->getDomainPathPathautoStateCollection($entity, $domain_id);
    \Drupal::keyValue($collection)->delete($entity->id());
  }

}
