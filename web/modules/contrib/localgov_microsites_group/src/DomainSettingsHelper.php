<?php

namespace Drupal\localgov_microsites_group;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\domain\DomainInterface;
use Drupal\file\FileInterface;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Associate field data with Domain Settings.
 *
 * Most settings are configured using the DomainGroupSettings plugin system.
 * See Plugin\DomainGroupSettings.
 * This class helps for values that are saved on the Group entity itself.
 */
class DomainSettingsHelper implements ContainerInjectionInterface {

  use DomainFromGroupTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * DomainSettings Helper constructor.
   *
   * @param Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The domain entity storage.
   * @param Drupal\language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * Handle the favicon file field.
   */
  public function faviconField(GroupInterface $group, FileFieldItemList $favicon_field) {
    if ($domain = $this->getDomainFromGroup($group)) {
      $theme = $this->getDomainTheme($domain);
      if ($favicon_field->isEmpty()) {
        $this->unsetFavicon($domain, $theme);
      }
      else {
        $this->setFavicon($domain, $theme, $favicon_field->entity);
      }
    }
  }

  /**
   * Remove any custom favicon override.
   *
   * @param \Drupal\Domain\DomainInterface $domain
   *   The Domain to remove it from.
   * @param string $theme
   *   The machine name of the theme.
   */
  public function unsetFavicon(DomainInterface $domain, $theme): void {
    $config_override = $this->loadConfigOverride($domain, $theme . '.settings');
    $data = $config_override->getRawData();
    unset($data['favicon']);
    if (empty($data)) {
      $config_override->delete();
    }
    else {
      $config_override->setData($data);
      $config_override->save();
    }
  }

  /**
   * Set a favicon override.
   *
   * @param \Drupal\Domain\DomainInterface $domain
   *   The Domain to add it for.
   * @param string $theme
   *   The machine name of the theme.
   * @param \Drupal\file\FileInterface $favicon_entity
   *   The favicon file entity.
   */
  public function setFavicon(DomainInterface $domain, $theme, FileInterface $favicon_entity) {
    $config_override = $this->loadConfigOverride($domain, $theme . '.settings');
    $data = $config_override->getRawData();
    $data['favicon'] = [
      'use_default' => 0,
      'path' => $favicon_entity->getFileUri(),
      'mimetype' => $favicon_entity->getMimeType(),
    ];
    $config_override->setData($data);
    $config_override->save();
  }

  /**
   * Get the current theme for the Domain.
   *
   * @param \Drupal\Domain\DomainInterface $domain
   *   The domain.
   *
   * @return string
   *   The machine name of the domain's current theme.
   */
  private function getDomainTheme(DomainInterface $domain): string {
    $config_override = $this->loadConfigOverride($domain, 'system.theme');
    if (!$config_override->isNew() && ($theme = $config_override->get('default'))) {
      return $theme;
    }
    else {
      $site_config = $this->configFactory->get('system.theme');
      return $site_config->get('default');
    }
  }

  /**
   * Load, or create, domain config override - for language.
   *
   * @param \Drupal\domain\DomainInterface $domain
   *   The domain site configuration being overriden.
   * @param string $original_config_id
   *   The configuration to retrieve.
   *
   * @return \Drupal\Core\Config\Config
   *   Editable configuaration.
   */
  private function loadConfigOverride(DomainInterface $domain, string $original_config_id) {
    if ($this->languageManager->getConfigOverrideLanguage() == $this->languageManager->getDefaultLanguage()) {
      $config_id = 'domain.config.' . $domain->id() . '.' . $original_config_id;
    }
    else {
      $config_id = 'domain.config.' . $domain->id() . '.' . $this->languageManager->getConfigOverrideLanguage() . '.' . $original_config_id;
    }
    $config_override = $this->configFactory->getEditable($config_id);

    return $config_override;
  }

}
