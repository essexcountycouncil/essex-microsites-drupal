<?php

declare(strict_types = 1);

namespace Drupal\field_formatter_range\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Service to have better structured code for updates.
 */
class FieldFormatterRangeUpdater implements FieldFormatterRangeUpdaterInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler
  ) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function update9101(): void {
    $this->update9101ViewModes();
    if ($this->moduleHandler->moduleExists('layout_builder')) {
      $this->update9101LayoutBuilder();
      // Layout builder overrides. Into a hook_post_update.
    }
  }

  /**
   * Update 9101: view modes.
   */
  protected function update9101ViewModes(): void {
    foreach ($this->configFactory->listAll('core.entity_view_display.') as $view_display_name) {
      $view_display = $this->configFactory->getEditable($view_display_name);
      $changed = FALSE;

      $display_content = $view_display->get('content');
      if (\is_array($display_content)) {
        foreach ($display_content as $key => $field_config) {
          if (isset($field_config['third_party_settings']['field_formatter_range'])) {
            $field_config['third_party_settings']['field_formatter_range'] = $this->update9101PrepareNewSettings($field_config['third_party_settings']['field_formatter_range']);
            $view_display->set('content.' . $key, $field_config);
            $changed = TRUE;
          }
        }
      }

      if ($changed) {
        $view_display->save(TRUE);
      }
    }
  }

  /**
   * Update 9101: layout builder displays.
   */
  protected function update9101LayoutBuilder(): void {
    foreach ($this->configFactory->listAll('core.entity_view_display.') as $view_display_name) {
      $view_display = $this->configFactory->getEditable($view_display_name);
      $changed = FALSE;

      /** @var array $layout_builder_sections */
      $layout_builder_sections = $view_display->get('third_party_settings.layout_builder.sections');
      if (!$layout_builder_sections) {
        continue;
      }

      foreach ($layout_builder_sections as $key => $section) {
        foreach ($section['components'] as $uuid => $component_infos) {
          if (isset($component_infos['configuration']['formatter']['third_party_settings']['field_formatter_range'])) {
            $component_infos['configuration']['formatter']['third_party_settings']['field_formatter_range'] = $this->update9101PrepareNewSettings($component_infos['configuration']['formatter']['third_party_settings']['field_formatter_range']);
            $layout_builder_sections[$key]['components'][$uuid] = $component_infos;
            $changed = TRUE;
          }
        }
      }

      if ($changed) {
        $view_display->set('third_party_settings.layout_builder.sections', $layout_builder_sections);
        $view_display->save(TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function update9101PrepareNewSettings(array $fieldFormatterRangeSettings): array {
    $fieldFormatterRangeSettings['order'] = (int) $fieldFormatterRangeSettings['reverse'];
    unset($fieldFormatterRangeSettings['reverse']);

    return $fieldFormatterRangeSettings;
  }

}
