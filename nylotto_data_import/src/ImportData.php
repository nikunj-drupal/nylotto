<?php

namespace Drupal\nylotto_data_import;

use FtpClient\FtpClient;
use Drupal\file\Entity\File;
use Drupal\nylotto_data_import\Entity\ImportFtpSourceInterface;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;

/**
 * Provides the class for handling imports.
 */
class ImportData {

  /**
   * Contains the plugin manager.
   *
   * @var DataImportManager
   */
  protected $DataTypeManager;

  /**
   * Constructs and initializes our service.
   */
  public function __construct(DataImportManager $dataManager) {
    $this->DataTypeManager = $dataManager;
  }

  /**
   * Helps validate a file prior to importing. We just need one to validate.
   */
  public function validFile(File $file) {
    $plugins = $this->DataTypeManager->getDefinitions();
    if (count($plugins) > 0) {
      foreach ($plugins as $plugin_id => $pluginDef) {
        $plugin = $this->DataTypeManager->createInstance($plugin_id, []);
        if ($plugin->validFile($file)) {
          return TRUE;
        }
      }
    }
    else {
      error_log("No plugins found");
    }

    return FALSE;
  }

  /**
   * Performs the import function.
   */
  public function importFile(File $file) {
    $plugins = $this->DataTypeManager->getDefinitions();
    foreach ($plugins as $plugin_id => $pluginDef) {
      $plugin = $this->DataTypeManager->createInstance($plugin_id, []);
      if ($plugin->validFile($file)) {
        error_log("Found plugin, starting import: " . $plugin_id);
        $plugin->importFile($file);
        return;
      }
    }
    error_log("No plugin found");
  }

  /**
   * Process row.
   */
  public function processRow($plugin_id, $data) {
    if ($plugin_id !== ''&& $plugin_id !== NULL) {
      if ($plugin = $this->DataTypeManager->createInstance($plugin_id, [])) {
        $plugin->processRow($data);
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Import using non-ssl method.
   */
  protected function ftpDownload(ImportFtpSourceInterface $ImportSource, $import_type = '') {
    // 1. Create the FTP Connection.
    $ftp = new FtpClient();
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('ny_ftp_file_queue');

    try {
      $ftp->connect($ImportSource->server, FALSE, $ImportSource->port);
      $ftp->login($ImportSource->user, $ImportSource->password);

      // 2. Download the files from the remote source... getContent
      $uri = "public://temp";
      $path = \Drupal::service('file_system')->realpath($uri);
      array_map('unlink', array_filter((array) glob("{$path}/*")));
      if (!file_exists($path)) {
        \Drupal::service('file_system')->prepareDirectory($uri, FILE_CREATE_DIRECTORY);
      }
      $importFolder = "public://import";
      foreach ($ftp->nlist($ImportSource->path) as $delta => $file) {
        $target = [
          'filename' => $file,
          'import_source' => $ImportSource->id(),
          'sftp' => FALSE,
        ];
        $queue->createItem($target);
      }
    }
    catch (\Exception $e) {
      watchdog_exception('nylotto_data_import', $e);
      return;
    }
  }

  /**
   * Import using ssl method for sftp.
   */
  protected function sftpDownload(ImportFtpSourceInterface $ImportSource, $private_key, $import_type = '') {
    $queue_factory = \Drupal::service('queue');
    $queue = $queue_factory->get('ny_ftp_file_queue');
    $sftp = new SFTP($ImportSource->server, $ImportSource->port);

    // Create new RSA key.
    $privateKey = new RSA();
    $privateKey->loadKey($private_key);
    // Echo "<pre>privateKey::";dump($privateKey);
    try {
      if ($sftp->login($ImportSource->user, $privateKey)) {
        // 2. Download the files from the remote source... getContent
        $uri = "public://import";
        $path = \Drupal::service('file_system')->realpath($uri);
        array_map('unlink', array_filter((array) glob("{$path}/*")));
        if (!file_exists($path)) {
          \Drupal::service('file_system')->prepareDirectory($uri, FILE_CREATE_DIRECTORY);
        }
        $importFolder = "public://import";
        $files = $sftp->nlist($ImportSource->path);
        $ny_ftp_file_queue_duplicate = [];
        $finish_login = date("d-m-Y H:i:s");
        \Drupal::logger('nylotto_ftp_login_finish')->warning('<pre><code>start_finish::' . print_r($finish_login, TRUE) . '</code></pre>');
        /** @var QueueInterface $queue */
        // $numberOfItems = $queue->numberOfItems();
        if ($files) {
          $start_checking = date("d-m-Y H:i:s");
          \Drupal::logger('nylotto_ftp_login')->warning('<pre><code>start_checking::' . print_r($start_checking, TRUE) . '</code></pre>');
          foreach ($files as $delta => $file) {
            if ($import_type == 'Cron') {
              // Database connection.
              $conn = Database::getConnection();
              $conn->insert('nylotto_import_log')->fields(
                [
                  'import_type' => 'Cron',
                  'import_source' => $file,
                  'import_time' => date('Y-m-d H:i:s'),
                ]
              )->execute();
            }
            // Check for a drawing data paragraph for this node.
            $config = \Drupal::config('nylotto_data_import_data_config.settings');
            $track_files = $config->get('track_files');
            if ($track_files == 'Yes') {
              $query = \Drupal::entityQuery('drawing')
                ->condition('field_file_name', $file);
              $pids = $query->execute();
              $pid = '';
              if (!empty($pids)) {
                $pid = array_shift($pids);
              }
              if (empty($pid) || $pid == '') {
                if ($file !== '.' && $file !== '..') {
                  $item = [
                    'filename' => $file,
                    'import_source' => $ImportSource->id(),
                    'sftp' => TRUE,
                  ];
                  $queue->createItem((object) $item);
                  // If ($queue->createItem((object) $item) === FALSE) {
                  //   $ny_ftp_file_queue_duplicate[] = $item;
                  // }.
                }
              }
            }
            else {
              if ($file !== '.' && $file !== '..') {
                $item = [
                  'filename' => $file,
                  'import_source' => $ImportSource->id(),
                  'sftp' => TRUE,
                ];
                $queue->createItem((object) $item);
                // If ($queue->createItem((object) $item) === FALSE) {
                //   $ny_ftp_file_queue_duplicate[] = $item;
                // }.
              }
            }
          }
          $completing_checking = date("d-m-Y H:i:s");
          \Drupal::logger('nylotto_ftp_login')->warning('<pre><code>completing_checking::' . print_r($completing_checking, TRUE) . '</code></pre>');
        }
      }
      else {
        $message = 'Unable to login in ftp. Please check with ftp source and private key configuration.';
        \Drupal::logger('nylotto_ftp_login')->error($message);
      }
    }
    catch (\Exception $e) {
      watchdog_exception('nylotto_data_import', $e);
      return;
    }
  }

  /**
   * Connect to remote server and download files for parsing.
   */
  public function downloadFTPFiles(ImportFtpSourceInterface $ImportSource = NULL, $import_type = '') {
    $config = \Drupal::config('nylotto_custom_json.ftp.settings');
    if ($private_key = $config->get('private_key')) {
      $start_login = date("d-m-Y H:i:s");
      \Drupal::logger('nylotto_ftp_login')->error("Proceeding with sftp protocol");
      \Drupal::logger('nylotto_ftp_login_start')->warning('<pre><code>start_time::' . print_r($start_login, TRUE) . '</code></pre>');
      $this->sftpDownload($ImportSource, $private_key, $import_type);
    }
    else {
      \Drupal::logger('nylotto_ftp_login')->error("Proceeding with ftp protocol");
      $this->ftpDownload($ImportSource, $import_type);
    }
  }

}
