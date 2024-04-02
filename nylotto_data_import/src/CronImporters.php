<?php

namespace Drupal\nylotto_data_import;

use Drupal\nylotto_drawing\Entity\Drawing;
use Drupal\node\Entity\Node;

/**
 * Class CronImporters.
 *
 * @package Drupal\nylotto_data_import
 */
class CronImporters {

  /**
   * The 'nylotto_data_import_cron_config.settings' config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;
  /**
   * The ftp_download.
   *
   * @var string
   */
  protected $ftp_download;
  /**
   * The retailer_import.
   *
   * @var string
   */
  protected $retailer_import;
  /**
   * The api_import.
   *
   * @var string
   */
  protected $api_import;
  /**
   * The cron_on_api_checkbox.
   *
   * @var string
   */
  protected $cron_on_api_checkbox;
  /**
   * The cron_on_api_time.
   *
   * @var string
   */
  protected $cron_on_api_time;
  /**
   * The cron_on_ftp_checkbox.
   *
   * @var string
   */
  protected $cron_on_ftp_checkbox;
  /**
   * The cron_on_ftp_time.
   *
   * @var string
   */
  protected $cron_on_ftp_time;
  /**
   * The cron_on_retailor_checkbox.
   *
   * @var string
   */
  protected $cron_on_retailor_checkbox;
  /**
   * The cron_on_retailor_time.
   *
   * @var string
   */
  protected $cron_on_retailor_time;
  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * CronImporters constructor.
   *   The language manager.
   *
   * @param \Drupal\Core\Config\Config $config
   */
  public function __construct() {
    $this->settings = \Drupal::config('nylotto_data_import_cron_config.settings');
    // Get configuration.
    $this->ftp_download = $this->settings->get('ftp_download');
    $this->retailer_import = $this->settings->get('retailer_import');
    $this->api_import = $this->settings->get('api_import');
    $this->cron_on_api_checkbox = $this->settings->get('cron_on_api_checkbox');
    $this->cron_on_api_time = $this->settings->get('cron_on_api_time');
    $this->cron_on_ftp_checkbox = $this->settings->get('cron_on_ftp_checkbox');
    $this->cron_on_ftp_time = $this->settings->get('cron_on_ftp_time');
    $this->cron_on_retailor_checkbox = $this->settings->get('cron_on_retailor_checkbox');
    $this->cron_on_retailor_time = $this->settings->get('cron_on_retailor_time');
  }

  /**
   *
   */
  public function cronAPIImport() {
    $config = \Drupal::service('config.factory')->getEditable('nylotto_importers_last_run_config.settings');
    $cron_on_api_checkbox = $this->cron_on_api_checkbox;
    $cron_on_api_time = $this->cron_on_api_time;
    // Current time.
    $current_time = date("Y-m-d H:i:s");
    $current = date("H:i:s");
    $cron_on_api_get = \Drupal::state()->get('cron_on_api');
    $config->set('cron_on_api', $cron_on_api_get)->save();

    if ($cron_on_api_checkbox == 1) {
      $new_cron_on_api = date('Y-m-d H:i:s', strtotime($cron_on_api_time, strtotime($cron_on_api_get)));
      if (empty($cron_on_api_get)) {
        if ($this->api_import == 1) {
          \Drupal::state()->set('cron_on_api', date("Y-m-d H:i:s"));
          $this->updateDrawGame();
        }
      }
      if ($this->api_import == 1 && $current_time > $new_cron_on_api) {
        \Drupal::state()->set('cron_on_api', date("Y-m-d H:i:s"));
        $this->updateDrawGame();
      }
    }
    else {
      $cron_time = date("H:i:s", strtotime($cron_on_api_time));
      $check_time = date("H:i:s", strtotime("+5 minutes", strtotime($cron_time)));
      if (empty($cron_time) && $this->api_import == 1) {
        \Drupal::state()->set('cron_on_api', date("Y-m-d H:i:s"));
        $this->updateDrawGame();
      }
      if ($this->api_import == 1 && $current_time > $check_time) {
        \Drupal::state()->set('cron_on_api', date("Y-m-d H:i:s"));
        $this->updateDrawGame();
      }
    }
  }

  /**
   *
   */
  public function cronFTPImport() {
    $config = \Drupal::service('config.factory')->getEditable('nylotto_importers_last_run_config.settings');
    $cron_on_ftp_checkbox = $this->cron_on_ftp_checkbox;
    $cron_on_ftp_time = $this->cron_on_ftp_time;
    // Current time.
    $current_time = date("Y-m-d H:i:s");
    $cron_on_ftp_get = \Drupal::state()->get('cron_on_ftp');
    $config->set('cron_on_ftp', $cron_on_ftp_get)->save();

    if ($cron_on_ftp_checkbox == 1) {
      $new_cron_on_ftp = date('Y-m-d H:i:s', strtotime($cron_on_ftp_time, strtotime($cron_on_ftp_get)));
      if (empty($cron_on_ftp_get) && $this->ftp_download == 1) {
        \Drupal::state()->set('cron_on_ftp', date("Y-m-d H:i:s"));
        $this->downloadFTP();
      }
      if ($this->ftp_download == 1 && $current_time > $new_cron_on_ftp) {
        \Drupal::state()->set('cron_on_ftp', date("Y-m-d H:i:s"));
        $this->downloadFTP();
      }
    }
    else {
      $cron_time = \Drupal::state()->get('cron_on_ftp');
      $check_time = date("Y-m-d H:i:s", strtotime("+5 minutes", strtotime($cron_time)));
      if (empty($cron_time) && $this->ftp_download == 1) {
        \Drupal::state()->set('cron_on_ftp', date("Y-m-d H:i:s"));
        $this->downloadFTP();
      }
      if ($this->ftp_download == 1 && $current_time > $check_time) {
        \Drupal::state()->set('cron_on_ftp', date("Y-m-d H:i:s"));
        $this->downloadFTP();
      }
    }
  }

  /**
   *
   */
  public function cronRetailorImport() {
    $config = \Drupal::service('config.factory')->getEditable('nylotto_importers_last_run_config.settings');
    $cron_on_retailor_checkbox = $this->cron_on_retailor_checkbox;
    $cron_on_retailor_time = $this->cron_on_retailor_time;
    // Current time.
    $current_time = date("Y-m-d H:i:s");
    $current = date("H:i:s");
    $cron_on_retailor_get = \Drupal::state()->get('cron_on_retailor');
    $config->set('cron_on_retailor', $cron_on_retailor_get)->save();
    if ($cron_on_retailor_checkbox == 1) {

      $new_cron_on_retailor = date('Y-m-d H:i:s', strtotime($cron_on_retailor_time, strtotime($cron_on_retailor_get)));
      if (empty($cron_on_retailor_get) && $this->retailer_import == 1) {
        \Drupal::state()->set('cron_on_retailor', date("Y-m-d H:i:s"));
        $this->retailerImport();
      }
      if ($this->retailer_import == 1 && $current_time > $new_cron_on_retailor) {
        \Drupal::state()->set('cron_on_retailor', date("Y-m-d H:i:s"));
        $this->retailerImport();
      }
    }
    else {
      $cron_time = \Drupal::state()->get('cron_on_retailor');
      $check_time = date("H:i:s", strtotime("+5 minutes", strtotime($cron_time)));
      if (empty($cron_time) && $this->retailer_import == 1) {
        \Drupal::state()->set('cron_on_retailor', date("Y-m-d H:i:s"));
        $this->retailerImport();
      }
      if ($this->retailer_import == 1 && $current_time > $check_time) {
        \Drupal::state()->set('cron_on_retailor', date("Y-m-d H:i:s"));
        $this->retailerImport();
      }
    }
  }

  /**
   *
   */
  public function updateDrawGame() {
    // Update draw gmames, based on check NYL API field.
    $ids = \Drupal::entityQuery('node')
      ->condition('type', 'game')
      ->condition('field_check_nyl_api', 1)
      ->execute();
    $gamesUpdate = Node::loadMultiple($ids);
    \Drupal::logger('nylotto_api_import')->warning('api_import_start');
    foreach ($gamesUpdate as $gamekey => $gamevalue) {
      $gameName = $gamevalue->get('field_game_id')->getString();
      if (!empty($gameName)) {
        $gamesData = $this->getGamesData($gameName);
        if (is_array($gamesData)) {
          $gamesApi = $gamesData['data'];
          $drawingData = $gamesApi['draws'];
          foreach ($drawingData as $drawingKey => $drawingValue) {
            $gameId = $gamevalue->id();
            $resultDate = $drawingdate = $drawingtime = '';
            $resultDate = $drawingValue['resultDate'] / 1000;
            $winners_numbers = '';
            if (isset($drawingValue['results'])) {
              $primary_data = $drawingValue['results'][0]['primary'];
              if ($gameName == 'quickdraw') {
                sort($primary_data);
              }
              $winners_numbers = implode('|', $primary_data);
            }
            $drawingdate = \Drupal::service('date.formatter')->format($resultDate, 'custom', 'Y-m-d');
            $drawingtime = \Drupal::service('date.formatter')->format($resultDate, 'custom', 'H:i:s');
            if ($gameName == 'numbers' || $gameName == 'win4') {
              $drawingtime = '';
              $time = \Drupal::service('date.formatter')->format($resultDate, 'custom', 'H');
              if ($time >= 8 && $time <= 13) {
                $drawingtime = 'Midday';
              }
              else {
                $drawingtime = 'Evening';
              }
            }
            $dataDraw['date'] = $drawingdate;
            $dataDraw['gameName'] = $gameName;
            $dataDraw['time'] = $drawingtime;
            $dataDraw['drawNumber'] = $drawingValue['drawNumber'];
            $dataDraw['multiplier'] = isset($drawingValue['results'][0]['multiplier']) ? $drawingValue['results'][0]['multiplier'] : '';
            $dataDraw['winningNumbers'] = $winners_numbers;
            $dataDraw['bonus'] = isset($drawingValue['results'][0]['secondary'][0]) ? $drawingValue['results'][0]['secondary'][0] : '';

            $includegames = ["win4", "numbers", "quickdraw"];
            if (!in_array($gameName, $includegames)) {
              // Check for a drawing data paragraph for this node.
              $query = \Drupal::entityQuery('drawing')
                ->condition('game', $gameId)
                ->condition('field_draw_date', $drawingdate);
              $pid = $query->execute();
            }
            else {
              // Check for a drawing data paragraph for this node.
              $query = \Drupal::entityQuery('drawing')
                ->condition('game', $gameId)
                ->condition('field_draw_date', $drawingdate)
                ->condition('field_draw_time', $dataDraw['time']);
              $pid = $query->execute();
            }
            if (empty($pid)) {
              $this->createDrawingData($gamevalue, $dataDraw);
            }
            else {
              $entity = entity_load('drawing', array_shift($pid));
              $this->updateDrawingData($gamevalue, $entity, $dataDraw);
            }
          }
        }
      }
    }
  }

  /**
   * Generate an array of objects from RestApi.
   *
   * @return array|bool
   *   Return an array or false
   */
  public function getGamesData($gameName) {
    $headers = [
      'headers' => [
        'x-api-key' => 'kAA3paUZQQvebd0Fws6e44s4FQ2vbGc74piJfJC2',
      ],
    ];
    $url = 'https://api.nylservices.net/games/' . $gameName . '/draws';
    $client = \Drupal::httpClient();
    try {
      $request = $client->get($url, $headers);
      $contentsData = $request->getBody()->getContents();
      $gamesData = json_decode($contentsData, TRUE);
      return $gamesData;
    }
    catch (RequestException $e) {
      watchdog_exception('nylotto_api_draw_game_update', $e);
      return FALSE;
    }
    exit;
  }

  /**
   * Create Drawing Data Paragraph plugin.
   */
  public function createDrawingData($node, $data) {
    $entity = Drawing::create([
      'type' => 'drawing_data',
      'field_draw_date' => $data['date'],
      'field_draw_time' => $data['time'],
      'field_winning_numbers_txt' => $data['winningNumbers'],
      'field_multiplier' => $data['multiplier'],
      'field_draw_number' => $data['drawNumber'],
      'field_bonus_ball' => $data['bonus'],
      'game' => [['target_id' => $node->id()]],
    ]);
    $entity->save();
    return $entity;
  }

  /**
   * Update Drawing Data Paragraph plugin.
   */
  public function updateDrawingData($node, $drawingEntity, $data) {
    $drawingEntity->set('field_winning_numbers_txt', $data['winningNumbers']);
    $drawingEntity->set('field_bonus_ball', $data['bonus']);
    $drawingEntity->set('field_multiplier', $data['multiplier']);
    $drawingEntity->set('field_draw_date', $data['date']);
    $drawingEntity->set('field_draw_number', $data['drawNumber']);
    $drawingEntity->set('field_draw_time', $data['time']);
    $drawingEntity->save();

    $node->save();

    return $drawingEntity;
  }

  /**
   *
   */
  public function downloadFTP() {
    $sources = entity_load_multiple('import_ftp_source');
    $service = \Drupal::service('nylotto.data');
    $dayofweek = date('w');
    $state = \Drupal::state();
    foreach ($sources as $id => $source) {
      $parts = explode(' ', $source->import_schedule);
      if ($parts[1] == $dayofweek || $source->cron_type == 'daily' || TRUE) {
        if (strtotime($state->get('id')) > strtotime('-20 hours') || TRUE) {
          $datestring = strtotime(date('m/d/y') . " {$parts[0]}");
          if ($datestring > strtotime("-30 minutes") || $datestring < strtotime("+1 hour")) {
            $service->downloadFTPFiles($source, 'Cron');
            $state->set($id, time());
          }
        }
      }
    }
  }

  /**
   *
   */
  public function retailerImport() {
    // Approx a 1 day of interval.
    $interval = 24 * 60 * 60;

    $next_execution = \Drupal::state()->get('nylotto_data_import.next_execution');
    $next_execution = !empty($next_execution) ? $next_execution : 0;
    if (REQUEST_TIME >= $next_execution) {
      try {
        \Drupal::state()->set('nylotto_data_import.next_execution', time() + $interval);
        $this->getRetailerData();
      }
      catch (\Exception $e) {
        watchdog_exception('nylotto_data_import_retailer_cron', $e);
      }
    }
  }

  /**
   * Callback function.
   */
  public function getRetailerData() {
    $queueFactory = \Drupal::service('queue');
    $queue1 = $queueFactory->get('exqueue_import');
    $items = $this->downloadRetailerData();
    \Drupal::logger('nylotto_retailer_import')->warning('Retailes_import_start');
    if ($items) {
      // $chunks = array_chunk($data, 1000);
      // $num_chunks = count($chunks);
      // // Now resave all nodes chunk by chunk.
      // $operations = [];
      // for ($i = 0; $i < $num_chunks; $i++) {
      //   $operations[] = [
      //     '\Drupal\nylotto_data_import\Batch\RetailerAllBatch::batchOperation',
      //     [$chunks[$i]],
      //   ];
      // }
      // $batch = [
      //   'title' => t('Resaving nodes'),
      //   'progress_message' => t('Completed @current out of @total chunks.'),
      //   'finished' => '\Drupal\nylotto_data_import\Batch\RetailerAllBatch::batchFinished',
      //   'operations' => $operations,
      // ];
      // batch_set($batch);
      $start_checking = date("d-m-Y H:i:s");
      \Drupal::logger('nylotto_retailer_import')->warning('<pre><code>start_checking::' . print_r($start_checking, TRUE) . '</code></pre>');

      foreach ($items as $data) {
        $item = new \stdClass();
        if (!empty($data[0])) {
          foreach ($data as $delta => $col) {
            $data[$delta] = trim($col, "\n");
          }
          $ids = \Drupal::entityQuery('node')
            ->condition('type', 'retailer')
            ->condition('field_internal_id', $data[0])
            ->execute();

          if (empty($ids)) {
            // Create an object.
            $item = new \stdClass();
            $item->internalid = $data[0];
            $item->name = $data[1];
            $item->street = $data[2];
            $item->city = $data[3];
            $item->state = $data[4];
            $item->zip = $data[5];
            $item->isqd = $data[6];
            $item->latitude = $data[7];
            $item->longitude = $data[8];
            $queue1->createItem($item);
          }
        }
      }
      $completing_checking = date("d-m-Y H:i:s");
      \Drupal::logger('nylotto_retailer_import')->warning('<pre><code>completing_checking::' . print_r($completing_checking, TRUE) . '</code></pre>');
    }
  }

  /**
   * Generate an array of objects from RestApi.
   *
   * @return array|bool
   *   Return an array or false
   */
  public function downloadRetailerData() {
    $client = \Drupal::httpClient();
    // 1. Try to get the data form the RestApi.
    $uri = 'https://api.nylservices.net/retailers/all';
    try {
      $response = $client->get($uri, [
        'headers' => [
          'x-api-key' => 'kAA3paUZQQvebd0Fws6e44s4FQ2vbGc74piJfJC2',
        ],
        [
          'access-token' => 'eyJraWQiOiI4SXJKaFpycVhXXC9LNXlSTUZJNnorYzk2NURPY3hBZGFHTXBcL2NobkRzeWc9IiwiYWxnIjoiUlMyNTYifQ.eyJzdWIiOiI0YzFiNTAzNy0wMTQxLTQxMDYtYmRlNC04YjhlOGZhN2I2NDIiLCJldmVudF9pZCI6ImExZGI1OTQyLTQ4MjItNDA2NC05ZjExLTE4ZmY0MmExNmYzNCIsInRva2VuX3VzZSI6ImFjY2VzcyIsInNjb3BlIjoiYXdzLmNvZ25pdG8uc2lnbmluLnVzZXIuYWRtaW4iLCJhdXRoX3RpbWUiOjE1NjA5ODA2NzIsImlzcyI6Imh0dHBzOlwvXC9jb2duaXRvLWlkcC51cy1lYXN0LTEuYW1hem9uYXdzLmNvbVwvdXMtZWFzdC0xX1ZRMUJzZjFhbiIsImV4cCI6MTU2MDk4NDI3MiwiaWF0IjoxNTYwOTgwNjcyLCJqdGkiOiI5YTA2MzNlZS0zNmNmLTRmODItYTRlYS1mZGJiNTkyZDg3M2IiLCJjbGllbnRfaWQiOiI1dGQycW02aGE3ZDB1aHFpZmlxbjZzNDlkYyIsInVzZXJuYW1lIjoiNGMxYjUwMzctMDE0MS00MTA2LWJkZTQtOGI4ZThmYTdiNjQyIn0.XEp1ArpVbKFlAYw0mUpfZYUSYhe3QNgoysys72UtF4_QEc105QzZTHoa0YxgiB7RVgdVdBii69FJu6KQGlTmun4xgvQu4xp8fCF4si0EVuBl44sV5RNgrmnkcFvAPPaCHY2eg4MrXs2D6jJ3P3-uHh7cdaMhkle3xVJKpcrzK7o3XmDpOgMW-yz-1wBNPaObI52Ol747wijq_j6Xm2wqQ84LONn0-9XYvENDZ95he4E26vFxuFSL0ELZKTtYpKG9K9dKTI9knjoE_6Mn8E6Ca6jJdoJaBELbSkaGrjIklJgE6LUcYq5aYT3-nkE1enkgjgNJ3fal7HPKQYonpI3YxA',
        ], [
          'client-id' => '32hktrve04jqbd8jg677s2fska',
        ],
      ]);
      $raw = $response->getBody();
      $data = explode("\r\n", $raw);
      if (empty($data)) {
        throw new \Exception("Could not get data from Retailer API");
      }
      $contents = array_map('str_getcsv', $data);
      return $contents;
    }
    catch (Exception $e) {
      watchdog_exception('nylotto_data_import', $e);
    }
  }

}
