<?php

namespace Drupal\groupmedia\Plugin\MediaFinder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\groupmedia\MediaFinderInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Media Finder Base.
 *
 * @package Drupal\groupmedia\Plugin\MediaFinder
 */
abstract class MediaFinderBase extends PluginBase implements MediaFinderInterface, ContainerFactoryPluginInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * MediaFinderBase constructor.
   *
   * @param array $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin Id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service for alter hooks.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $moduleHandler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getApplicableFieldTypes() {
    return $this->pluginDefinition['field_types'] ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity) {
    if ($entity instanceof FieldableEntityInterface) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldBeAdded(MediaInterface $media, EntityInterface $entity, $field_name) {
    $return = [];
    $context = [
      'entity' => $entity,
      'field_name' => $field_name,
    ];
    $this->moduleHandler->alter('groupmedia_finder_add', $return, $media, $context);
    if (!is_array($return)) {
      return FALSE;
    }
    // If at least 1 module says "No", the media will not be attached.
    foreach ($return as $item) {
      if (!$item) {
        return FALSE;
      }
    }
    // Otherwise - process.
    return TRUE;
  }

}
