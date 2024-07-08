<?php

namespace Drupal\path_redirect_import;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\file\FileRepositoryInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use League\Csv\Writer;

/**
 * Service that manages the redirect export batch operations.
 */
class RedirectExport {
  use MigratePluginTrait;

  const MIGRATE_FOLDER = 'public://path_redirect_import/';
  const BATCH_SIZE = 50;
  const HEADERS = ['source', 'destination', 'language', 'status_code'];

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The file repository.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * Constructs a RedirectExport object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The plugin manager for config entity-based migrations.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file repository.
   */
  public function __construct(
    FileSystemInterface $file_system,
    EntityTypeManagerInterface $entity_type_manager,
    MigrationPluginManagerInterface $migration_plugin_manager,
    FileRepositoryInterface $file_repository
  ) {
    $this->fileSystem = $file_system;
    $this->entityTypeManager = $entity_type_manager;
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->fileRepository = $file_repository;
  }

  /**
   * Method description.
   */
  public function doSomething() {
    // @DCG place your code here.
  }

  /**
   * Creates the spreadsheet file to export entries to.
   *
   * @return \Drupal\file\FileInterface
   *   File.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function getFile() {
    $filename = 'export_' . time() . '.csv';
    $uri = self::MIGRATE_FOLDER . $filename;
    $directory = self::MIGRATE_FOLDER;
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    return $this->fileRepository->writeData('', $uri, FileSystemInterface::EXISTS_REPLACE);
  }

  /**
   * Get the CSV writer.
   *
   * @param string $file_path
   *   Path of the file.
   * @param string $mode
   *   Mode to open the file.
   * @param array $configuration
   *   Array with CSV configuration.
   *
   * @return \League\Csv\Writer
   *   The writer.
   *
   * @throws \Drupal\migrate\MigrateException
   * @throws \League\Csv\Exception
   */
  protected static function createWriter($file_path, $mode, array $configuration) {
    $writer = Writer::createFromPath($file_path, $mode);

    $writer->setDelimiter($configuration['delimiter']);
    $writer->setEnclosure($configuration['enclosure']);
    $writer->setEscape($configuration['escape']);

    return $writer;
  }

  /**
   * Load the same configuration of migrate for CSV=>Table import path.
   *
   * @return array
   *   Configuration for csv info, separators, etc.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getConfigurationFromPlugin() {
    $migration_plugin = $this->migrationPlugin();

    return $migration_plugin->getSourcePlugin()->getConfiguration();
  }

  /**
   * Returns the Export redirect batch operations.
   *
   * @return array
   *   The batch operations.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getBatchOperations() {
    // Create file, folder and prepare variables.
    $file = $this->getFile();
    $configuration = $this->getConfigurationFromPlugin();

    // Load a list of all ids to process.
    $ids = $this->entityTypeManager
      ->getStorage('redirect')
      ->getQuery()
      ->accessCheck(TRUE)
      ->execute();

    // Breakdown process into small batches.
    $operations = [];
    $item_start = 0;
    foreach (array_chunk($ids, self::BATCH_SIZE) as $batch_data) {
      $operations[] = [
        get_class($this) . '::batchProcessExport',
        [$file, $configuration, $batch_data, $item_start, count($ids)],
      ];
      $item_start += self::BATCH_SIZE;
    }

    return $operations;
  }

  /**
   * Export into the CSV per batches.
   *
   * @param \Drupal\file\entity\File $file
   *   File where information will be stored to.
   * @param array $configuration
   *   Configuration for CSV structure.
   * @param array $batch_data
   *   Ids of redirect entities to process.
   * @param int $start
   *   Next item to process.
   * @param int $total
   *   Total of items to process.
   * @param mixed $context
   *   Context array/iterable.
   */
  public static function batchProcessExport(File $file, array $configuration, array $batch_data, int $start, int $total, &$context) {
    $file_path = \Drupal::service('file_system')->realpath($file->getFileUri());

    $context['results']['failures'] = isset($context['results']['failures']) ?? 0;
    $context['results']['file'] = $file;
    // Write header in first iteration of first batch.
    if (empty($start)) {
      // Write header.
      $writer = self::createWriter($file_path, 'w', $configuration);
      $writer->insertOne(self::HEADERS);
    }

    $storage_handler = \Drupal::entityTypeManager()->getStorage('redirect');
    $entities = $storage_handler->loadMultiple($batch_data);

    if (empty($writer)) {
      $writer = self::createWriter($file_path, 'a', $configuration);
    }

    // Now export entities one by one, only with the fields we need.
    /** @var \Drupal\redirect\Entity\Redirect $redirect_entity */
    foreach ($entities as $redirect_entity) {
      try {
        $source = $redirect_entity->getSourceUrl();
        $redirect = $redirect_entity->getRedirectUrl();
        $lang = $redirect_entity->get('language')->value;
        $status_code = $redirect_entity->get('status_code')->value;
        $writer->insertOne([
          $source,
          $redirect->toString(),
          $lang,
          $status_code,
        ]);
      }
      catch (\Exception $e) {
        $context['results']['failures']++;
      }
      $start++;
    }

    $context['finished'] = 1;
    if ($start >= $total) {
      $context['results']['processed'] = $start;
    }
    else {
      $context['message'] = t('Exporting (@percent%).', [
        '@percent' => (int) (($start / $total) * 100),
      ]);
    }
  }

  /**
   * Finished callback for import batches.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   The value set in $context['results'] by callback_batch_operation().
   * @param array $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function batchFinishedExport($success, array $results, array $operations) {
    /** @var \Drupal\file\Entity\File $file */
    $file = !empty($results['file']) ? $results['file'] : NULL;
    if ($success && $file) {
      $uri = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
      $url = Url::fromUri($uri);
      $download = Link::fromTextAndUrl(t('link'), $url);
      $return['link'] = $url;
      \Drupal::messenger()->addStatus(t('Export process finished. You may download the file through this %link', ['%link' => $download->toString()]));

      if (isset($results['failures']) && isset($results['processed'])) {
        \Drupal::messenger()->addStatus(t('Processed @processed items. Exported: @correct, failures: @failures',
          [
            '@processed' => $results['processed'],
            '@correct' => $results['processed'] - $results['failures'],
            '@failures' => $results['failures'],
          ]));
      }
    }
    else {
      \Drupal::messenger()->addError(t('Export process failed. Please review existing redirections or contact an administrator.'));
    }
    // In any other case, set file as temporary so that cron deletes it.
    if ($file) {
      $file->setTemporary();
      $file->save();
    }
  }

}
