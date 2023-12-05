<?php

namespace Drupal\localgov_microsites_group;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\domain\DomainInterface;
use Drupal\domain\DomainStorageInterface;
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

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The domain entity storage.
   *
   * @var \Drupal\domain\DomainStorageInterface
   */
  protected $domainStorage;

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
   * @param Drupal\domain\DomainStorageInterface $domain_storage
   *   The domain entity storage.
   * @param Drupal\language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, DomainStorageInterface $domain_storage, LanguageManagerInterface $language_manager) {
    $this->configFactory = $config_factory;
    $this->domainStorage = $domain_storage;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('domain'),
      $container->get('language_manager')
    );
  }

  /**
   * Handle the favicon file field.
   */
  public function faviconField(GroupInterface $group, FileFieldItemList $favicon_field) {
    if ($domain = $this->getGroupDomain($group)) {
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
   * Get the Domain for a Group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   *
   * @return \Drupal\Domain\DomainInterface
   *   The related domain.
   */
  private function getGroupDomain(GroupInterface $group): ?DomainInterface {
    return $this->domainStorage->load('group_' . $group->id());
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
