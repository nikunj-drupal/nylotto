<?php

namespace Drupal\nylotto_data_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class CustomEndpoints.
 */
class CleanImportLogs extends ControllerBase {

  /**
   * Delete the queue 'exqueue_import'.
   */
  public function truncateLogs() {
    \Drupal::database()->truncate('nylotto_import_log')->execute();
    $response = new RedirectResponse(\Drupal::url('view.nylotto_import_logs.page_1'));
    $response->send();

  }

}
