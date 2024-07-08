<?php

namespace Drupal\path_redirect_import_test\Form;

use Drupal\path_redirect_import\Form\MigrateRedirectForm as MigrateRedirectFormBase;
use League\Csv\Reader;

/**
 * Provides a mock form for testing purposes.
 *
 * @ingroup path_redirect_import_test
 */
class MigrateRedirectForm extends MigrateRedirectFormBase {

  /**
   * The reader path to use.
   *
   * @var string
   */
  protected string $path;

  /**
   * Sets the reader path value.
   *
   * @param string $path
   *   The reader path value.
   */
  public function setReaderPath(string $path) {
    $this->path = $path;
  }

  /**
   * {@inheritdoc}
   */
  protected function createReader() {
    if (!isset($this->path)) {
      $path = \Drupal::service('extension.list.module')->getPath('path_redirect_import_test');
      $this->path = $path . '/artifacts/redirect.csv';
    }
    return Reader::createFromStream(fopen($this->path, 'r'));
  }

  /**
   * {@inheritdoc}
   */
  public function redirectsToDeletePublic() {
    return parent::redirectsToDelete();
  }

}
