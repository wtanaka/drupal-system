<?php

/**
 * Implementation of hook_cron().
 *
 * Remove older rows from flood and batch table. Remove old temporary files.
 */
function _real_system_cron() {
  // Cleanup the flood.
  db_query('DELETE FROM {flood} WHERE timestamp < %d', time() - 3600);
  // Cleanup the batch table.
  db_query('DELETE FROM {batch} WHERE timestamp < %d', time() - 864000);

  // Remove temporary files that are older than DRUPAL_MAXIMUM_TEMP_FILE_AGE.
  $result = db_query('SELECT * FROM {files} WHERE status = %d and timestamp < %d', FILE_STATUS_TEMPORARY, time() - DRUPAL_MAXIMUM_TEMP_FILE_AGE);
  while ($file = db_fetch_object($result)) {
    if (file_exists($file->filepath)) {
      // If files that exist cannot be deleted, continue so the database remains
      // consistent.
      if (!file_delete($file->filepath)) {
        watchdog('file system', 'Could not delete temporary file "%path" during garbage collection', array('%path' => $file->filepath), 'error');
        continue;
      }
    }
    db_query('DELETE FROM {files} WHERE fid = %d', $file->fid);
  }
  $core = array('cache', 'cache_block', 'cache_filter', 'cache_page', 'cache_form', 'cache_menu');
  $cache_tables = array_merge(module_invoke_all('flush_caches'), $core);
  foreach ($cache_tables as $table) {
    cache_clear_all(NULL, $table);
  }
}
