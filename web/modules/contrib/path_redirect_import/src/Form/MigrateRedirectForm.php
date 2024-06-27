<?php

namespace Drupal\path_redirect_import\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\file\FileRepositoryInterface;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Drupal\path_redirect_import\MigratePluginTrait;
use Drupal\redirect\Entity\Redirect;
use League\Csv\Reader;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for migrating redirects.
 *
 * @ingroup path_redirect_import
 */
class MigrateRedirectForm extends FormBase {
  use MigratePluginTrait;
  use SampleCsvFormTrait;

  const MIGRATE_FILE_PATH = 'temporary://path_redirect_import/migrate.csv';

  /**
   * The tempstore object.
   *
   * @var \Drupal\Core\TempStore\SharedTempStore
   */
  protected $privateTempStore;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * Constructs a MigrateRedirectForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The plugin manager for config entity-based migrations.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file repository.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    MigrationPluginManagerInterface $migration_plugin_manager,
    PrivateTempStoreFactory $temp_store_factory,
    AccountInterface $current_user,
    FileRepositoryInterface $file_repository
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->migrationPluginManager = $migration_plugin_manager;
    $this->privateTempStore = $temp_store_factory->get('redirect_multiple_delete_confirm');
    $this->currentUser = $current_user;
    $this->fileRepository = $file_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.migration'),
      $container->get('tempstore.private'),
      $container->get('current_user'),
      $container->get('file.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_redirect_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['table'] = $this->getSampleCsvTable($this->t('Please upload a CSV file following this pattern to migrate the redirect data:'));

    $form['spreadsheet'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('CSV File'),
      '#description' => $this->t('The CSV containing the redirect data in the expected format'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#upload_location' => 'temporary://path_redirect_import',
      '#weight' => 1,
    ];

    $form['delete'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete redirects defined in the spreadsheet'),
      '#description' => $this->t('This action can be undone'),
      '#weight' => 2,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#weight' => 100,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Migrate data'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $input = $form_state->getUserInput();
    if (isset($input['op'])) {
      $fid = reset($form_state->getValue('spreadsheet'));
      if (!$fid) {
        $form_state->setErrorByName('spreadsheet', $this->t('Unable to load the file. Please upload it again'));
        return;
      }
      /** @var \Drupal\file\Entity\File $file */
      $file = $this->entityTypeManager->getStorage('file')->load($fid);
      $reader = Reader::createFromPath($file->getFileUri(), 'r')->setHeaderOffset(0);
      $removeFile = FALSE;

      foreach ($reader as $key => $record) {
        $csvLine = $key + 1;
        // Validate if the headers in the CSV file have the correct label.
        if (!isset($record['source']) || !isset($record['destination'])
         || !isset($record['language']) || !isset($record['status_code'])) {
          $form_state->setErrorByName($key, 'Header line should follow the following labels: source,destination,language,status_code');
          $file->delete();
          return;
        }
        // Validate character encoding.
        if (mb_check_encoding($record['source'], 'UTF-8') === FALSE
          || mb_check_encoding($record['destination'], 'UTF-8') === FALSE
          || mb_check_encoding($record['language'], 'UTF-8') === FALSE
          || mb_check_encoding($record['status_code'], 'UTF-8') === FALSE) {
          $removeFile = TRUE;
          $csvHtml = $this->t('Line @line in @label contains wrong character(s)', [
            '@line' => $csvLine,
            '@label' => $file->label(),
          ]);
          $csvHtml .= '<br/>' . print_r($record, TRUE);
          $form_state->setErrorByName($key, $csvHtml);
        }

        if (in_array(NULL, $record, TRUE) || in_array('', $record, TRUE)) {
          $removeFile = TRUE;
          $csvHtml = $this->t('Line @line in @label contains empty/null value(s)', [
            '@line' => $csvLine,
            '@label' => $file->label(),
          ]);
          $csvHtml .= '<br/>' . print_r($record, TRUE);
          $form_state->setErrorByName($key, $csvHtml);
        }

        // Use ltrim to compare url with /url.
        if (trim($record['source']) === ltrim(trim($record['destination']), '/')) {
          $removeFile = TRUE;
          $csvHtml = $this->t('Line @line in @label contains the same URL destination as source', [
            '@line' => $csvLine,
            '@label' => $file->label(),
          ]);
          $csvHtml .= '<br/>' . print_r($record, TRUE);
          $form_state->setErrorByName($key, $csvHtml);
        }
      }
      if ($removeFile) {
        $file->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fid = $form_state->getValue(['spreadsheet', 0]);
    $file = $this->processSpreadsheet($fid);
    if ($file) {
      if ($form_state->getValue('delete') === 1) {
        $this->deleteRedirectData($form_state);
      }
      else {
        $this->migrateRedirectData();
      }
    }
    else {
      $this->messenger()->addError('Unable to parse the file. Please try again.');
    }
  }

  /**
   * Imports the redirect data using Migrate.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function migrateRedirectData() {
    $migration = $this->migrationPlugin();
    $migration->setStatus(MigrationInterface::STATUS_IDLE);
    $migrateMessage = new MigrateMessage();
    $options = [
      'limit' => 0,
      'update' => 1,
      'force' => 0,
    ];

    $executable = new MigrateBatchExecutable($migration, $migrateMessage, $options);
    $executable->batchImport();
  }

  /**
   * Loads the Redirects to delete and redirects to the Deletion confirm form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \Drupal\migrate\MigrateException
   * @throws \League\Csv\Exception
   */
  protected function deleteRedirectData(FormStateInterface $form_state) {
    $redirects = $this->redirectsToDelete();
    $this->privateTempStore->set($this->currentUser->id(), $redirects);
    $options = [
      'query' => $this->getDestinationArray(),
    ];
    $form_state->setRedirect('entity.redirect.multiple_delete_confirm', [], $options);
  }

  /**
   * Process the uploaded spreadsheet to convert it to a Migrate ready format.
   *
   * @param int $fid
   *   The spreadsheet file ID.
   *
   * @return \Drupal\file\FileInterface|false
   *   Resulting file entity for success, or false in the event of an error.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function processSpreadsheet(int $fid) {
    /** @var \Drupal\file\Entity\File $file */
    $file = $this->entityTypeManager->getStorage('file')->load($fid);
    return $this->fileRepository->move($file, self::MIGRATE_FILE_PATH, FileSystemInterface::EXISTS_REPLACE);
  }

  /**
   * Iterates over the CSV file to load the list of redirects to delete.
   *
   * @return \Drupal\redirect\Entity\Redirect[]
   *   Array containing the Redirect entities to delete.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\migrate\MigrateException
   * @throws \League\Csv\Exception
   */
  protected function redirectsToDelete() {
    $redirects_to_delete = [];
    $storage = $this->entityTypeManager->getStorage('redirect');
    $header = $this->getReader()->getHeader();
    $records = $this->getReader()->getRecords($header);

    foreach ($records as $record) {
      $parsed_url = UrlHelper::parse(urldecode($record['source']));
      $path = $parsed_url['path'] ?? NULL;
      $query = $parsed_url['query'] ?? NULL;
      $hash = Redirect::generateHash($path, $query, $record['language']);

      // Search for redirects by hash.
      $redirects = $storage->loadByProperties(['hash' => $hash]);
      $redirects_to_delete = array_merge($redirects_to_delete, $redirects);
    }
    return $redirects_to_delete;
  }

  /**
   * Get the CSV reader.
   *
   * @return \League\Csv\Reader
   *   The reader.
   *
   * @throws \Drupal\migrate\MigrateException
   * @throws \League\Csv\Exception
   */
  protected function getReader() {
    $migration_plugin = $this->migrationPlugin();
    $configuration = $migration_plugin->getSourcePlugin()->getConfiguration();
    $reader = $this->createReader();
    $reader->setDelimiter($configuration['delimiter']);
    $reader->setEnclosure($configuration['enclosure']);
    $reader->setEscape($configuration['escape']);
    $reader->setHeaderOffset($configuration['header_offset']);
    return $reader;
  }

  /**
   * Construct a new CSV reader.
   *
   * @return \League\Csv\Reader
   *   The reader.
   */
  protected function createReader() {
    return Reader::createFromStream(fopen(self::MIGRATE_FILE_PATH, 'r'));
  }

}
