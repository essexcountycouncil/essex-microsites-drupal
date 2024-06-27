<?php

namespace Drupal\path_redirect_import\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Class CSVRedirect. Reimplements source CSV.
 *
 * @MigrateSource(
 *   id = "csv_redirect",
 *   source_module = "path_redirect_import"
 * )
 */
class CSVRedirect extends CSV {

  /**
   * {@inheritdoc}
   */
  public function next() {
    $this->currentSourceIds = NULL;
    $this->currentRow = NULL;

    // In order to find the next row we want to process, we ask the source
    // plugin for the next possible row.
    while (!isset($this->currentRow) && $this->getIterator()->valid()) {

      $row_data = $this->getIterator()->current() + $this->configuration;
      $this->fetchNextRow();
      $row = new Row($row_data, $this->getIds());

      // Customized cleanup of the row.
      $this->prepareRowIds($row);

      // Populate the source key for this row.
      $this->currentSourceIds = $row->getSourceIdValues();

      // Pick up the existing map row, if any, unless fetchNextRow() did it.
      if (!$this->mapRowAdded && ($id_map = $this->idMap->getRowBySource($this->currentSourceIds))) {
        $row->setIdMap($id_map);
      }

      // Clear any previous messages for this row before potentially adding
      // new ones.
      if (!empty($this->currentSourceIds)) {
        $this->idMap->delete($this->currentSourceIds, TRUE);
      }

      // Preparing the row gives source plugins the chance to skip.
      if ($this->prepareRow($row) === FALSE) {
        continue;
      }

      // Check whether the row needs processing.
      // 1. This row has not been imported yet.
      // 2. Explicitly set to update.
      // 3. The row is newer than the current highwater mark.
      // 4. If no such property exists then try by checking the hash of the row.
      if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
        $this->currentRow = $row->freezeSource();
      }

      if ($this->getHighWaterProperty()) {
        $this->saveHighWater($row->getSourceProperty($this->highWaterProperty['name']));
      }
    }
  }

  /**
   * Prepare Row to have clean ids before calculation.
   *
   * @param \Drupal\migrate\Row $row
   *   Row to migrate.
   */
  private function prepareRowIds(Row $row) {
    try {
      if ($row->hasSourceProperty('source')) {
        $row->setSourceProperty('source', ltrim($row->getSourceProperty('source'), '/'));
      }
    }
    catch (\Exception $e) {
    }
  }

}
