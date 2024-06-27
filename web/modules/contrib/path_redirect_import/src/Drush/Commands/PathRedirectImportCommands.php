<?php

namespace Drupal\path_redirect_import\Drush\Commands;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate_tools\Drush\Commands\MigrateToolsCommands;
use Drupal\path_redirect_import\Form\MigrateRedirectForm;
use Drupal\path_redirect_import\RedirectExport;
use Drush\Attributes as CLI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class PathRedirectImportCommands extends MigrateToolsCommands {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * The redirect export service.
   *
   * @var \Drupal\path_redirect_import\RedirectExport
   */
  protected $redirectExport;

  /**
   * PathRedirectImportCommands constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $migrationPluginManager
   *   Migration Plugin Manager service.
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   *   Date formatter service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $keyValue
   *   Key-value store service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   File System service.
   * @param \Drupal\path_redirect_import\RedirectExport $redirectExport
   *   The redirect export service.
   */
  public function __construct(MigrationPluginManager $migrationPluginManager, DateFormatter $dateFormatter, EntityTypeManagerInterface $entityTypeManager, KeyValueFactoryInterface $keyValue, FileSystemInterface $fileSystem, RedirectExport $redirectExport) {
    parent::__construct($migrationPluginManager, $dateFormatter, $entityTypeManager, $keyValue);
    $this->fileSystem = $fileSystem;
    $this->redirectExport = $redirectExport;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): MigrateToolsCommands {
    return new static(
      $container->get('plugin.manager.migration'),
      $container->get('date.formatter'),
      $container->get('entity_type.manager'),
      $container->get('keyvalue'),
      $container->get('file_system'),
      $container->get('path_redirect_import.redirect_export'),
    );
  }

  /**
   * Imports the redirects defined in the CSV file passed as argument.
   *
   * @param string $file
   *   The CSV file to import.
   *
   * @command path_redirect_import:import
   * @aliases prii
   */
  #[CLI\Command(name: 'path_redirect_import:import', aliases: ['prii'])]
  #[CLI\Argument(name: 'file', description: 'The CSV file to import.')]
  #[CLI\Topics(topics: ['path_redirect_import'])]
  #[CLI\ValidateModulesEnabled(modules: ['migrate_tools'])]
  public function importRedirects($file) {
    if (!file_exists($file)) {
      $this->logger()->error("File $file doesn't exist \n");
      exit;
    }

    $directory = dirname(MigrateRedirectForm::MIGRATE_FILE_PATH);
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $this->fileSystem->copy($file, MigrateRedirectForm::MIGRATE_FILE_PATH, FileSystemInterface::EXISTS_REPLACE);

    $this->resetStatus('path_redirect_import');

    $this->import('path_redirect_import', [
      'limit' => 0,
      'update' => TRUE,
      'force' => FALSE,
      'all' => FALSE,
      'group' => NULL,
      'tag' => NULL,
      'execute-dependencies' => FALSE,
      'sync' => FALSE,
      'skip-progress-bar' => FALSE,
      'continue-on-failure' => FALSE,
      'idlist' => NULL,
    ]);

    $this->logger()->success($this->t('Redirects imported.'));
  }

  /**
   * Exports the redirects.
   *
   * @command path_redirect_import:export
   * @aliases prie
   */
  #[CLI\Command(name: 'path_redirect_import:export', aliases: ['prie'])]
  #[CLI\Topics(topics: ['path_redirect_import'])]
  #[CLI\ValidateModulesEnabled(modules: ['migrate_tools'])]
  public function exportRedirects() {
    $operations = $this->redirectExport->getBatchOperations();

    $batchBuilder = new BatchBuilder();
    array_walk($operations, fn($operation) => $batchBuilder->addOperation($operation[0], $operation[1]));

    $batchBuilder
      ->setTitle($this->t('Exporting redirect entities to file'))
      ->setFinishCallback(RedirectExport::class . '::batchFinishedExport')
      ->setErrorMessage($this->t('An error occurred while exporting redirect entities.'));
    // 5. Add batch operations as new batch sets.
    batch_set($batchBuilder->toArray());
    // 6. Process the batch sets.
    drush_backend_batch_process();
  }

}
