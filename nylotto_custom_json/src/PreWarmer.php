<?php

namespace Drupal\nylotto_custom_json;

use Drupal\Core\Entity\EntityInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * This file contains the service used to pregenerate the Normalized array
 * for our content items, this helps in reducing the load on the front end rendering
 * of json.
 */
class PreWarmer {

  /**
   *
   * @return array
   */
  public function generateNormalizeArray(EntityInterface &$entity, $update = TRUE) {
    // Remoce to enable caching.
    $update = TRUE;
    switch ($entity->getEntityTypeId()) {
      case 'paragraph':
        return $this->generateNormalizedParagraph($entity, $update);

      case 'node':
        return $this->generateNormalizedNode($entity, $update);

      case 'drawing':
        return $this->generateNormalizedDrawData($entity, $update);
    }
  }

  /**
   * Generates the normalized response for a term.
   */
  public function generateNormalizedTerm(EntityInterface &$entity, $update = TRUE) {
    switch ($entity->vid->target_id) {
      case 'viewing_area':
        $data = $this->whereToWatch($entity);
        break;

      case 'customer_service_centers':
        $data = $this->endPoint($entity);
        break;
    }
    return $data;
  }

  /**
   *
   */
  public function whereToWatch(EntityInterface $entity) {
    // The entity here is the location, like Albany, a taxonomy term.
    $paragraphs = \Drupal::entityQuery('paragraph')
      ->condition('field_region', $entity->id())
      ->execute();

    // This map has game nids mapped to where to watch paragraph ids.
    $draw_game_map = $this->drawGameMap();

    $entities = Paragraph::loadMultiple($paragraphs);
    $stations = $entities;
    foreach ($entities as $paragraph) {
      if ($paragraph->field_channel->entity) {
        if (!isset($stations[$paragraph->field_channel->target_id]) && ($paragraph->field_channel->entity)) {
          $stations[$paragraph->field_channel->entity->id()] = [
              // Name is the station like WRGB (CBS6)
            'name' => $paragraph->field_channel->entity->label(),
            'games' => [],
          ];
        }

        // Something is wrong around here. It doesn't work.
        $parent = $paragraph->getParentEntity();

        foreach ($draw_game_map as $draw_game) {
          if (in_array($paragraph->id(), $draw_game_map[$parent->id()])) {
            $stations[$paragraph->field_channel->target_id]['games'][$parent->id()] = [
              'name' => $parent->label(),
              'logo' => $this->getStyles($parent->field_logo->entity->image->entity, $parent->field_logo->entity, 'image'),
            ];
          }
        }
      }
    }

    return [
      'id' => $entity->id(),
      'title' => $entity->label(),
      'stations' => $stations,
    ];
  }

  /**
   * @return array
   *   This function maps draw games to their actual paragraph ids for the where to watch paragraph.
   */
  public function drawGameMap() {
    // Let's load all drawgame nodes.
    $nids = \Drupal::entityQuery('node')->condition('type', 'game')->execute();
    $draw_game_nodes = Node::loadMultiple($nids);

    // Get the corresponding show_time paragraph ids for each node so we create a map.
    $draw_game_map = [];
    foreach ($draw_game_nodes as $node) {
      // $paragraphs = $node->field_where_to_watch->getValue();
      $paragraphs = $node->field_where_to_watch->referencedEntities();
      $show_time_ids = [];
      foreach ($paragraphs as $show_time) {
        $show_time_ids[] = $show_time->id();
      }
      $draw_game_map[$node->id()] = $show_time_ids;
    }

    return $draw_game_map;
  }

  /**
   *
   */
  public function endPoint(EntityInterface $entity) {
    return [
      'id' => $entity->id(),
      'title' => $entity->label(),
      'vid' => $entity->get('vid')->getString(),
      'description' => $entity->get('description')->getString(),
      'changed' => $entity->get('changed')->getString(),
      'weight' => $entity->get('weight')->getString(),
      'parent' => $entity->get('parent')->getString(),
      'langcode' => $entity->get('langcode')->getString(),
    ];
  }

  /**
   * Generates Drawing Information.
   */
  protected function generateNormalizedDrawData(EntityInterface &$entity, $update = TRUE) {
    /**
     * Get data for local winners.
     */
    $localWinners = [];
    foreach ($entity->field_winners->referencedEntities() as $paragraph) {
      $localWinners[] = $this->generateNormalizedParagraph($paragraph);
    }

    /**
     * Get data for national winners.
     */
    $nationalWinners = [];
    foreach ($entity->field_national_winners->referencedEntities() as $paragraph) {
      $nationalWinners[] = $this->generateNormalizedParagraph($paragraph);
    }
    /**
     * Get data for local winners.
     */
    $localMultiplierWinners = [];
    foreach ($entity->field_multiplier_local_winners->referencedEntities() as $paragraph) {
      $localMultiplierWinners[] = $this->generateNormalizedParagraph($paragraph);
    }

    /**
     * Get data for national winners.
     */
    $nationalMultiplierWinners = [];
    foreach ($entity->field_multiplier_national_winner->referencedEntities() as $paragraph) {
      $nationalMultiplierWinners[] = $this->generateNormalizedParagraph($paragraph);
    }
    $parent = $entity->game->entity;
    $gameOptions = [];
    foreach ($parent->field_game_options->getValue() as $value) {
      $gameOptions[] = $value['value'];
    }
    $data = [
      'id' => $entity->id(),
      'bundle' => $entity->bundle(),
      'game' => $parent->label(),
      'game_options' => $gameOptions,
      'bonus_number' => $this->getValue($entity, 'field_bonus_ball'),
      'alias' => $this->getNodeAlias($parent),
      'collect_time' => $this->getValue($entity, 'field_collect_time'),
      'date' => $this->getValue($entity, 'field_draw_date'),
      'draw_number' => $this->getValue($entity, 'field_draw_number'),
      'draw_time' => $this->getValue($entity, 'field_draw_time'),
      'jackpot' => $this->getValue($entity, 'field_jackpot'),
      'multiplier' => $this->getValue($entity, 'field_multiplier'),
      'next_draw_date' => $this->getValue($entity, 'field_next_draw_date'),
      'next_jackpot' => $this->getValue($entity, 'field_next_jackpot'),
      'total_prizes' => $this->getValue($entity, 'field_total_prizes'),
      'winning_numbers' => explode('|', $this->getValue($entity, 'field_winning_numbers_txt')),
      'local_winners' => $localWinners,
      'local_multiplier_winners' => $localMultiplierWinners,
      'national_winners' => $nationalWinners,
      'national_multiplier_winners' => $nationalMultiplierWinners,
      'multiplier_label' => $parent->field_multiplier_label->value,
        // MoneyDots.
    ];

    // If moneyDots append moneydots data.
    // if ($this->getValue($entity, 'field_secondary_game')) {.
    $data['secondary_prize_value'] = $this->processSecondaryValues($entity);
    // }
    return $data;
  }

  /**
   * Process MoneyDots Supplemental results.
   * Returns array of supplemental results.
   */
  private function processMoneyDotsResults(EntityInterface &$entity) {
    $results = explode('|', $this->getValue($entity, 'field_supplemental_results_txt'));
    $processed_results = [];
    foreach ($results as $result) {
      $result_array = [];
      $result = explode(',', $result);
      $result_array['draw_number'] = $result[0];
      $result_array['prize_value'] = $result[1];
      if ($result[2]) {
        $result_array['is_winning_number'] = TRUE;
      }
      $processed_results[] = $result_array;
    }
    return $processed_results;
  }

  /**
   * Process MoneyDots Secondary values.
   */
  private function processSecondaryValues(EntityInterface &$entity) {
    $secondary_array = [];
    $secondary_array['money_dots_amount'] = $this->getValue($entity, 'field_secondary_prize_value');
    $secondary_array['money_dots_number'] = $this->getValue($entity, 'field_secondary_draw_number');
    $secondary_array['money_dots_prizes'] = $this->getValue($entity, 'field_md_amount');
    return $secondary_array;
  }

  /**
   * Generates a normalized paragraph array.
   */
  protected function generateNormalizedParagraph(EntityInterface &$entity, $update = TRUE) {
    global $base_url;

    switch ($entity->bundle()) {
      case 'downloadable_pdfs':
        $files = [];
        foreach ($entity->field_pdfs->referencedEntities() as $paragraph) {
          $file = $paragraph->field_pdf->entity;
          $files[] = [
            'uri' => file_create_url($file->getFileUri()),
            'title' => $this->getValue($paragraph, 'field_section_title'),
          ];
        }

        $data = [
          'body' => $this->getValue($entity, 'field_body'),
          'files' => $files,
        ];
        break;

      case 'prize_claim_centers':
        $data = [
          'uri' => '/api/prize_claim_center',
          'regions' => [
            'uri' => '/api/service_center',
          ],
        ];
        break;

      case 'paragraph_with_title':
        $data = [
          'title' => $this->getValue($entity, 'field_section_title'),
          'body' => $this->getValue($entity, 'field_body'),
        ];

        break;

      case 'paragraph_with_image':
        $data = [
          'title' => $this->getValue($entity, 'field_section_title'),
          'body' => $this->getValue($entity, 'field_body'),
          'image' => $this->getImages($entity, 'field_media'),
          'alignment' => $this->getValue($entity, 'field_image_alignment'),
        ];

        break;

      case 'collapsible_paragraph':
        $data = [
          'title' => $this->getValue($entity, 'field_section_title'),
          'body' => $this->getValue($entity, 'field_body'),
          'collapsed' => $this->getValue($entity, 'field_collapsed‎'),
        ];

        break;

      case 'cta_paragraph':
        $buttons = [];
        foreach ($entity->field_buttons->referencedEntities() as $paragraph) {
          $buttons[] = $this->generateNormalizedParagraph($paragraph);
        }
        $data = [
          'title' => $this->getValue($entity, 'field_section_title'),
          'body' => $this->getValue($entity, 'field_body'),
          'image' => $this->getImages($entity, 'field_media'),
          'alignment' => $this->getValue($entity, 'field_image_alignment'),
          'buttons' => $buttons,
        ];

        break;

      case 'button':
        $data = [
          'link' => $this->getValue($entity, 'field_link', 'uri'),
          'title' => $this->getValue($entity, 'field_link', 'title'),
          'options' => $this->getValue($entity, 'field_link', 'options'),
          'breakpoint' => $this->getValue($entity, 'field_breakpoint'),
        ];
        break;

      case 'slideshow':
        $slides = [];
        foreach ($entity->field_slides->referencedEntities() as $paragraph) {
          $slides[] = $this->generateNormalizedParagraph($paragraph);
        }
        $data = [
          'slides' => $slides,
        ];
        break;

      case 'slide':
        $data = [
          'title' => $this->getValue($entity, 'field_section_title'),
          'subtitle' => $this->getValue($entity, 'field_subtitle'),
          'image' => $this->getImages($entity, 'field_media'),
          'alignment' => $this->getValue($entity, 'field_image_alignment'),
          'description' => $this->getValue($entity, 'field_body'),
        ];

        break;

      case 'embedded_video':
        $data = [
          'video' => $this->getValue($entity, 'field_video_url‎'),
        ];
        break;

      case 'county_breakdown':
        $data = ['counties' => []];
        foreach ($entity->field_counties->referencedEntities() as $county) {
          $data['counties'][] = [
            'county' => $this->getValue($county, 'title'),
            'aid' => $this->getValue($county, 'field_aid_to_education'),
            'prizes' => $this->getValue($county, 'field_prizes'),
          ];
        }

        break;

      case 'winner_hero':
        $data = [
          'amount' => $this->getValue($entity, 'field_prizes'),
          'date_won' => $this->getValue($entity, 'field_prizes'),
          'logo' => $this->getImages($entity, 'field_logo'),
          'image' => $this->getImages($entity, 'field_media'),
          'name' => $this->getValue($entity, 'field_player_name'),
          'location' => $this->getValue($entity, 'field_winning_location'),
        ];

        break;

      case 'faq_content':
        $data = [
          'title' => $this->getValue($entity, 'field_section_title'),
          'questions' => [],
        ];
        foreach ($entity->field_questions->referencedEntities() as $question) {
          $data['questions'][] = [
            'question' => $question->get('field_section_title')->getString(),
            'answer' => $question->get('field_body')->value,
          ];
        }

        break;

      case 'contact_form':
        $data = [];
        $data['subjects'] = $entity->field_email_subject->getValue();

        break;

      case 'press_release':
        $data['uri'] = '/api/press';
        return $data;

      break;
      case 'two_col_section':
        $data = [
          'type' => $entity->bundle(),
          'title' => $this->getValue($entity, 'field_section_title'),
          'background' => [
            'color' => $this->getValue($entity, 'field_background_color'),
            'image' => ($section->field_media) ? $this->getImages($section, 'field_media') : [],
            'parallax' => $this->getValue($entity, 'field_parallax'),
          ],
          'content' => [
            'left' => [],
            'right' => [],
          ],
        ];

        // Get the various paragraph content types.
        foreach ($entity->field_content->referencedEntities() as $paragraph) {
          $data['content']['left'][] = $this->generateNormalizedParagraph($paragraph);
        }

        // Get the various paragraph content types.
        foreach ($entity->field_right_content->referencedEntities() as $paragraph) {
          $data['content']['right'][] = $this->generateNormalizedParagraph($paragraph);
        }

        break;

      case 'one_col_section':
        $data = [
          'type' => $entity->bundle(),
          'title' => $this->getValue($entity, 'field_section_title'),
          'background' => [
            'color' => $this->getValue($entity, 'field_background_color'),
            'image' => ($entity->field_media) ? $this->getImages($entity, 'field_media') : [],
            'parallax' => $this->getValue($entity, 'field_parallax'),
          ],
          'content' => [
            'left' => [],
          ],
        ];
        // Get the various paragraph content types.
        foreach ($entity->field_content->referencedEntities() as $paragraph) {
          $data['content']['left'][] = $this->generateNormalizedParagraph($paragraph);
        }

        break;

      case 'odds_and_prizes_alt':
        $data[] = [
          'title' => $this->getValue($entity, 'field_section_title'),
          'type' => $entity->bundle(),
          'description' => $this->getValue($entity, 'field_body'),
          'body' => $this->getValue($entity, 'field_free_form_text'),
        ];

        break;

      case 'odds_and_prizes':
        // Get any attached fields.
        $pdfFiles = [];
        if ($entity->hasField('field_pdf')) {
          foreach ($entity->field_pdf->referencedEntities() as $file) {
            $pdfFiles[] = ['uri' => file_create_url($file->getFileUri())];
          }
        }

        $last_updated = new DrupalDateTime($this->getValue($entity, 'field_last_updated'), 'UTC');
        $last_updated_formatted = \Drupal::service('date.formatter')->format($last_updated->getTimestamp(), 'custom', 'Y-m-d H:i:s', 'America/New_York');

        $data = [
          'title' => $this->getValue($entity, 'field_section_title'),
          'type' => $entity->bundle(),
          'last_updated' => $last_updated_formatted,
          'win_combination' => $this->getValue($entity, 'field_win_combination'),
          'with_powerball' => $this->getValue($entity, 'field_with_powerball'),
            // '3_play_prize' => $this->getValue($entity, 'field_3_play_prize'),
            // '6_play_prize' => $this->getValue($entity, 'field_6_play_prize'),
          'ways_to_win' => $this->getValue($entity, 'field_ways_to_win'),
          'description' => $this->getValue($entity, 'field_body'),
          'pdf' => $pdfFiles,

            // This is needed for the Scratch of details of the odds/prizes sections.
          'overall_odds' => $this->getValue($entity, 'field_odds'),
          'prize_amount' => $this->getValue($entity, 'field_3_play_prize'),
          'prizes_paid_out' => $this->getValue($entity, 'field_prizes_paid_out'),
          'prizes_remaining' => $this->getValue($entity, 'field_prizes_remaining'),
          'sections' => [],
        ];

        // Loop through and get the sections of content for this odd and prize section.
        foreach ($entity->field_sections->referencedEntities() as $paragraph) {
          $data['sections'][] = $this->generateNormalizedParagraph($paragraph);
        }

        break;

      case 'feature':
        $data = [
          'title' => $this->getValue($entity, 'field_section_title'),
          'description' => $this->getValue($entity, 'field_body'),
          'image' => $this->getImages($entity, 'field_media', 'image'),
        ];

        break;

      case 'game_feature':
        $data = [
          'title' => $this->getValue($entity, 'field_section_title'),
          'features' => [],
        ];
        foreach ($entity->field_features->referencedEntities() as $paragraph) {
          $data['features'][] = $this->generateNormalizedParagraph($paragraph);
        }

        break;

      case 'play_rules':
        $data = [
          'title' => $this->getValue($entity, 'field_section_title'),
          'description' => $this->getValue($entity, 'field_body'),
        ];
        $html = $this->getValue($entity, 'field_body');
        preg_match('@src="([^"]+)"@', $html, $match);
        $src = array_pop($match);
        if ($src) {
          $description_img = $base_url . '' . $src;
          $description = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
          $data['description'] = preg_replace('(src="(.*?)")', 'src="' . $description_img . '" data-echo="$1"', $description);
        }

        break;

      case 'how_to_play':
        $steps = [];
        foreach ($entity->field_steps->referencedEntities() as $paragraph) {
          $steps[] = $this->generateNormalizedParagraph($paragraph);
        }
        $data = [
          'title' => $this->getValue($entity, 'field_section_title'),
          'description' => $this->getValue($entity, 'field_body'),
          'play_slip' => $this->getImages($entity, 'field_media'),
          'play_slip_cropped' => $this->getImages($entity, 'field_media', 'image', 'play_slip_cropped'),
          'ticket_art' => $this->getImages($entity, 'field_ticket_art'),
          'steps' => $steps,
        ];

        break;

      case 'winning_location':
        $locationData = [];
        $winners_data_parentID = $entity->get('parent_id')->getString();
        $winners_data = entity_load('paragraph', $winners_data_parentID);
        $parentID = $winners_data->get('parent_id')->getString();
        $parent = entity_load('drawing', $parentID);
        $gameID = $parent->get('game')->getString();
        $database = \Drupal::database();
        $query = $database->query("SELECT `field_feature_approved_value` FROM `node__field_feature_approved` WHERE `entity_id` = '" . $gameID . "'");
        $result = $query->fetchAll();
        $feature_approved = '';
        if ($result) {
          $feature_approved_obj = current($result);
          $feature_approved = $feature_approved_obj->field_feature_approved_value;
        }
        $retailer_verified = $entity->get('field_retailer_verified')->getString();
        if (($retailer_verified == 1 && $feature_approved == 1)) {
          $data = [
            'retailer_address' => $this->getValue($entity, 'field_retailer_address'),
            'retailer_city' => $this->getValue($entity, 'field_retailer_city'),
            'retailer_name' => $this->getValue($entity, 'field_retailer_name'),
            'ticket_type' => $this->getValue($entity, 'field_ticket_type'),
            'winning_county' => $this->getValue($entity, 'field_winning_county'),
          ];
        }
        elseif ($feature_approved == 0 || $feature_approved == NULL) {
          $data = [
            'retailer_address' => $this->getValue($entity, 'field_retailer_address'),
            'retailer_city' => $this->getValue($entity, 'field_retailer_city'),
            'retailer_name' => $this->getValue($entity, 'field_retailer_name'),
            'ticket_type' => $this->getValue($entity, 'field_ticket_type'),
            'winning_county' => $this->getValue($entity, 'field_winning_county'),
          ];
        }
        else {
          $data = [];
        }

        break;

      case 'winners_data':
        $locationData = [];
        $parentID = $entity->get('parent_id')->getString();
        $parent = entity_load('drawing', $parentID);
        $gameID = $parent->get('game')->getString();
        $database = \Drupal::database();
        $query = $database->query("SELECT `field_feature_approved_value` FROM `node__field_feature_approved` WHERE `entity_id` = '" . $gameID . "'");
        $result = $query->fetchAll();
        $feature_approved = '';
        if ($result) {
          $feature_approved_obj = current($result);
          $feature_approved = $feature_approved_obj->field_feature_approved_value;
        }
        $retailer_verified = '';
        foreach ($entity->field_winning_locations->referencedEntities() as $paragraph) {
          $retailer_verified = $paragraph->get('field_retailer_verified')->getString();
          if ($retailer_verified == 1 && $feature_approved == 1) {
            $locationData[] = $this->generateNormalizedParagraph($paragraph);
          }
          if ($feature_approved == 0 || $feature_approved == NULL) {
            $locationData[] = $this->generateNormalizedParagraph($paragraph);
          }
        }

        $states = [];
        foreach ($entity->field_state->referencedEntities() as $state) {
          $states[] = $state->label();
        }

        $data = [
          'prize_levels' => $this->getValue($entity, 'field_prize_label'),
          'prize_amount' => $this->getValue($entity, 'field_prize_amount'),
          'prize_winners' => intval($this->getValue($entity, 'field_prize_winners')),
          'location_data' => $locationData,
          'state' => $states,
          'wager_type' => $this->getValue($entity, 'field_wager_type'),
        ];
        return $data;

      break;
      case 'drawing_data':
        /**
         * Get data for local winners.
         */
        $localWinners = [];
        foreach ($entity->field_winners->referencedEntities() as $paragraph) {
          $localWinners[] = $this->generateNormalizedParagraph($paragraph);
        }

        /**
         * Get data for national winners.
         */
        $nationalWinners = [
          'prize_levels' => [],
          'prize_amount' => [],
          'prize_winners' => [],
        ];
        foreach ($entity->field_national_winners->referencedEntities() as $paragraph) {
          $nationalWinners[] = $this->generateNormalizedParagraph($paragraph);
        }

        /**
         * Get data for local winners.
         */
        $localMultiplierWinners = [];
        foreach ($entity->field_multiplier_local_winners->referencedEntities() as $paragraph) {
          $localMultiplierWinners[] = $this->generateNormalizedParagraph($paragraph);
        }

        /**
         * Get data for national winners.
         */
        $nationalMultiplierWinners = [];
        foreach ($entity->field_multiplier_national_winner->referencedEntities() as $paragraph) {
          $nationalMultiplierWinners[] = $this->generateNormalizedParagraph($paragraph);
        }
        $parent = $entity->getParentEntity();
        $gameOptions = [];
        foreach ($parent->field_game_options->getValue() as $value) {
          $gameOptions[] = $value['value'];
        }
        $data = [
          'id' => $entity->id(),
          'bundle' => $entity->bundle(),
          'game' => $parent->label(),
          'game_options' => $gameOptions,
          'bonus_number' => $this->getValue($entity, 'field_bonus_ball'),
          'alias' => $this->getNodeAlias($parent),
          'collect_time' => $this->getValue($entity, 'field_collect_time'),
          'date' => $this->getValue($entity, 'field_draw_date'),
          'draw_number' => $this->getValue($entity, 'field_draw_number'),
          'draw_time' => $this->getValue($entity, 'field_draw_time'),
          'jackpot' => $this->getValue($entity, 'field_jackpot'),
          'multiplier' => $this->getValue($entity, 'field_multiplier'),
          'next_draw_date' => $this->getValue($entity, 'field_next_draw_date'),
          'next_jackpot' => $this->getValue($entity, 'field_next_jackpot'),
          'total_prizes' => $this->getValue($entity, 'field_total_prizes'),
          'winning_numbers' => explode('|', $this->getValue($entity, 'field_winning_numbers_txt')),
          'local_winners' => $localWinners,
          'local_multiplier_winners' => $localMultiplierWinners,
          'national_winners' => $nationalWinners,
          'national_multiplier_winners' => $nationalMultiplierWinners,
          'multiplier_label' => $parent->field_multiplier_label->value,
        ];
        break;

      case 'region_report_':
        $data = [
          'uri' => '/api/regional_reports/:term_id',
          'filters' => [
            'start_date' => 'datetime',
          ],
          'regions' => [
            'uri' => '/api/regions',
          ],
        ];
        break;

      default:
        $data = [];
    }
    $data['type'] = $entity->bundle();
    $entity->set('normalized', ['data' => $data]);
    $entity->save();
    return $data;
  }

  /**
   * Generates a normalized node array.
   */
  protected function generateNormalizedNode(EntityInterface &$entity, $update = TRUE) {
    if ($update == FALSE) {
      $value = $entity->get('normalized')->getValue();
      if (isset($value[0]['data'])) {
        return $value[0]['data'];
      }
    }

    switch ($entity->bundle()) {
      case 'game':
        $data = $this->drawingGameData($entity);
        break;

      case 'scratch_off':
        $data = $this->scratchOffGameData($entity);
        break;

      case 'second_chance':
        $data = $this->secondChanceGameData($entity);
        break;

      case 'players_club':
        $data = $this->playersClub($entity);
        break;

      case 'collect_and_win':
        $data = $this->collectAndWin($entity);
        break;

      case 'marquee':
        $data = $this->marquee($entity);
        break;

      case 'prize_claim_center':
        error_log('called switch');
        $data = $this->prizeClaimCenter($entity);
        break;

      case 'prize_component':
        $data = $this->prizeComponent($entity);
        break;

      case 'recent_winners':
        $data = $this->recentWinners($entity);
        break;

      case 'retailer':
        $data = $this->retailer($entity);
        break;

      case 'page':
      case 'press_release':
        $data = $this->page($entity);
        break;

      case 'reginoal_reports':
        $data = $this->regionalReports($entity);
        break;

      case 'alert':
        $data = $this->baseNode($entity);
        break;

      default:
        return [];
    }

    $entity->set('normalized', ['data' => $data]);
    return $data;
  }

  /**
   * Gets the alias for the given node if it exists.
   */
  protected function getNodeAlias(EntityInterface $entity) {
    return \Drupal::service('path.alias_manager')
      ->getAliasByPath('/node/' . $entity->id());
  }

  /**
   *
   */
  protected function getInstantGameImage($title) {
    $data = [];
    $id = \Drupal::entityQuery('node')
      ->condition('title', trim($title))
      ->condition('type', 'scratch_off')
      ->execute();

    if ($entity = entity_load('node', array_shift($id))) {
      $data = [
        'logo' => $this->getStyles($entity->field_logo->entity->image->entity, $entity->field_logo->entity, 'image'),
        'art' => $this->getStyles($entity->field_ticket_art->entity->image->entity, $entity->field_ticket_art->entity, 'image'),
        'cropped_art' => $this->getStyles($entity->field_image->entity->image->entity, $entity->field_image->entity, 'image'),
        'nid' => $entity->id(),
        'title' => $entity->label(),
        'alias' => $this->getNodeAlias($entity),
      ];
    }
    return $data;
  }

  /**
   *
   */
  public function regionalReports(EntityInterface $entity) {
    $counties = [];
    if ($entity->field_region->entity->field_counties) {
      foreach ($entity->field_region->entity->field_counties->referencedEntities() as $id => $term) {
        $counties[] = $term->label();
      }
    }

    $entity_id = $entity->id();

    $data = [
      'nid' => $entity_id,
      'region' => ($entity->field_region->entity) ? $entity->field_region->entity->label() : '',
      'counties' => $counties,
      'aid_education' => ($entity->field_earned_for_education) ? floatval($entity->field_earned_for_education->value) : 0,
      'prizes' => ($entity->field_prizes_won) ? floatval($entity->field_prizes_won->value) : 0,
      'powerball' => ($entity->field_powerball_prizes_won) ? floatval($entity->field_powerball_prizes_won->value) : 0,
      'mega_millions' => ($entity->field_mega_millions_prizes_won) ? floatval($entity->field_mega_millions_prizes_won->value) : 0,
      'money_dots' => ($entity->field_money_dots_prizes_won) ? floatval($entity->field_money_dots_prizes_won->value) : 0,
      'lotto' => ($entity->field_lotto_prizes_won) ? floatval($entity->field_lotto_prizes_won->value) : 0,
      'cash4life' => ($entity->field_cash_4_life_prizes_won) ? floatval($entity->field_cash_4_life_prizes_won->value) : 0,
      'quickdraw' => ($entity->field_quick_draw_prizes_won) ? floatval($entity->field_quick_draw_prizes_won->value) : 0,
      'take5' => ($entity->field_take_five_prizes_won) ? floatval($entity->field_take_five_prizes_won->value) : 0,
      'win4' => ($entity->field_win4_prizes_won) ? floatval($entity->field_win4_prizes_won->value) : 0,
      'numbers' => ($entity->field_numbers_prizes_won) ? floatval($entity->field_numbers_prizes_won->value) : 0,
      'pick10' => ($entity->field_pick_10_prizes_won) ? floatval($entity->field_pick_10_prizes_won->value) : 0,
      'scratch_off_games' => ($entity->field_instants_prizes_won) ? floatval($entity->field_instants_prizes_won->value) : 0,
      'top_prize' => $this->getInstantGameImage($entity->field_top_instant_game->value),
      'second_prize' => $this->getInstantGameImage($entity->field_2nd_instant_game->value),
      'thirdPrize' => $this->getInstantGameImage($entity->field_3rd_instant_game->value),
      'start_date' => $entity->field_start_date->value,
      'end_date' => $entity->field_end_date->value,
      'total' => 0,
    ];

    $data['total'] = $data['prizes'];

    return $data;
  }

  /**
   * Get the page data.
   */
  public function page(EntityInterface $entity) {
    $sections = [];
    foreach ($entity->field_sections->referencedEntities() as $paragraph) {
      $sections[] = $this->generateNormalizedParagraph($paragraph);
    }
    $data = [
      'nid' => $entity->id(),
      'title' => $entity->label(),
      'sections' => $sections,
      'date' => $this->getValue($entity, 'field_date'),
      'alias' => $this->getNodeAlias($entity),
    ];
    return $data;
  }

  /**
   * Return the data for the retailer.
   */
  public function retailer(EntityInterface $entity) {
    $data = [
      'name' => $entity->label(),
      'address' => $this->getValue($entity, 'field_address'),
      'location' => $this->getValue($entity, 'field_geofield'),
      'internal_id' => $this->getValue($entity, 'field_internal_id'),
      'isqd' => $this->getValue($entity, 'field_isqd'),
    ];
    return $data;
  }

  /**
   * Return the data for recent winner.
   */
  public function recentWinners(EntityInterface $entity) {
    $data = [
      'nid' => $entity->id(),
      'alias' => $this->getNodeAlias($entity),
      'logo' => $this->getStyles($entity->field_logo->entity->image->entity, $entity->field_logo->entity, 'image'),
      'image' => $this->getStyles($entity->field_image->entity->image->entity, $entity->field_image->entity, 'image'),
      'prize' => $this->getValue($entity, 'field_prize_amount'),
      'prize_type' => $this->getValue($entity, 'field_prize_label'),
      'quote' => $this->getValue($entity, 'field_quote'),
      'name' => $entity->label(),
      'title' => $this->getValue($entity, 'field_title'),
      'date' => $this->getValue($entity, 'field_date'),
      'location' => $this->getValue($entity, 'field_location'),
      'promoted' => $this->getValue($entity, 'promoted'),
      'body' => $this->getValue($entity, 'field_pbody'),
    ];
    return $data;
  }

  /**
   * Gets the data for the Prize Component.
   */
  public function prizeComponent(EntityInterface $entity) {
    $pdf_upload = '';
    if ($entity->field_pdf_upload) {
      $file = $entity->field_pdf_upload->entity->field_document->entity;
      if ($file) {
        $pdf_upload = file_create_url($file->getFileUri());
      }
    }
    $buttons = $entity->field_cta_button->getValue();

    return [
      'nid' => $entity->id(),
      'title' => $entity->label(),
      'image' => $this->getStyles($entity->field_image->entity->image->entity, $entity->field_image->entity, 'image'),
      'bg_image' => $this->getStyles($entity->field_bg_image->entity->image->entity, $entity->field_bg_image->entity, 'image'),
      'pdf' => $pdf_upload,
      'description' => $this->getValue($entity, 'body'),
      'cta_button' => $buttons,
      'alias' => $this->getNodeAlias($entity),
    ];
  }

  /**
   * Gets the data for the Prize Claim Center.
   */
  public function prizeClaimCenter(EntityInterface $entity) {
    $data = [
      'nid' => $entity->id(),
      'bundle' => $entity->bundle(),
      'alias' => $this->getNodeAlias($entity),
      'details' => $entity->body->value,
      'region' => ($entity->field_region) ? $entity->field_region->entity->label() : '',
      'region_id' => ($entity->field_region) ? $entity->field_region->target_id : '',
    ];

    return $data;
  }

  /**
   * Implements Collect and win.
   */
  public function collectAndWin(EntityInterface $entity) {
    $data = [
      'title' => $entity->label(),
      'nid' => $entity->id(),
      'alias' => $this->getNodeAlias($entity),
      'type' => $entity->bundle(),
      'cta' => $entity->field_cta_button->getValue(),
    ];
    return $data;
  }

  /**
   * Normalize the Draw game.
   */
  public function drawingGameData(EntityInterface $entity) {
    $drawingData = [];
    $locationData = [];
    $nationalDrawingData = [];
    $nationalWinnerData = [];

    // Generate the location Data.
    $drawDays = [];
    foreach ($entity->field_draw_days->getValue() as $value) {
      $drawDays[] = $value['value'];
    }

    // Get How to play data.
    $howToPlay = [];
    foreach ($entity->field_play_rules->referencedEntities() as $paragraph) {
      $howToPlay[] = $this->generateNormalizedParagraph($paragraph);
    }

    // Get Game Features data.
    $gameFeatures = [];
    foreach ($entity->field_game_features->referencedEntities() as $paragraph) {
      $gameFeatures[] = $this->generateNormalizedParagraph($paragraph);
    }

    // Odds and Prizes.
    $oddsAndPrizes = [];
    foreach ($entity->field_odds->referencedEntities() as $paragraph) {
      $oddsAndPrizes[] = $this->generateNormalizedParagraph($paragraph);
    }

    $gameOptions = [];
    foreach ($entity->field_game_options->getValue() as $value) {
      $gameOptions[] = $value['value'];
    }

    $pdf_upload = '';
    if ($entity->field_pdf_upload) {
      $file = $entity->field_pdf_upload->entity->field_document->entity;
      if ($file) {
        $pdf_upload = file_create_url($file->getFileUri());
      }
    }

    $data = [
      'nid' => $entity->id(),
      'title' => $entity->label(),
      'description' => $entity->body->value,
      'pricing_information' => $entity->field_pricing_information->value,
      'alias' => $this->getNodeAlias($entity),
      'bundle' => $entity->bundle(),
      'position' => $entity->get('field_position')->getString(),
      'promoted' => $entity->get('promote')->getString(),
      'nyGameKey' => $entity->get('field_game_key')->getString(),
      'gameId' => $entity->get('field_game_id')->getString(),
      'logo' => $this->getImages($entity, 'field_logo'),
      'draw_days' => $drawDays,
      'draw_time' => $entity->get('field_draw_time')->getString(),
      'close_time' => $entity->get('field_close_time')->getString(),
      'description' => $entity->get('field_pbody')->getString(),
      'overall_odds' => $entity->get('field_overall_odds')->getString(),
      'winning_data' => [
        'drawing_data' => $drawingData,
        'location_data' => $locationData,
        'national_drawing_data' => $nationalDrawingData,
        'national_winning_data' => $nationalWinnerData,
      ],
      'how_to_play' => $howToPlay,
      'game_features' => $gameFeatures,
      'odds_prizes' => $oddsAndPrizes,
      'odds_prizes_pdf' => $pdf_upload,
      'game_options' => $gameOptions,
    ];
    return $data;
  }

  /**
   * Normalize the Players Club game.
   */
  public function playersClub(EntityInterface $entity) {
    $data = [
      'nid' => $entity->id(),
      'type' => $entity->bundle(),
      'alias' => $this->getNodeAlias($entity),
      'logo' => $this->getImages($entity, 'field_logo'),
      'description' => $this->getValue($entity, 'body'),
    ];

    return $data;
  }

  /**
   * Normalize the Scratch Off game.
   */
  public function scratchOffGameData(EntityInterface $entity) {
    $buttons = [];
    foreach ($entity->field_cta_buttons_adv->referencedEntities() as $paragraph) {
      $this->generateNormalizedParagraph($paragraph);
    }

    // Get How to play data.
    $howToPlay = [];
    foreach ($entity->field_play_rules->referencedEntities() as $paragraph) {
      $howToPlay[] = $this->generateNormalizedParagraph($paragraph);
    }

    // Get Game Features data.
    $gameFeatures = [];
    foreach ($entity->field_game_features->referencedEntities() as $paragraph) {
      $gameFeatures[] = $this->generateNormalizedParagraph($paragraph);
    }

    // Odds and Prizes.
    $oddsAndPrizes = [];
    foreach ($entity->field_odds->referencedEntities() as $paragraph) {
      $oddsAndPrizes[] = $this->generateNormalizedParagraph($paragraph);
    }

    $weeklyReportUri = '';
    if ($file = $entity->field_weekly_report->entity) {
      $weeklyReportUri = file_create_url($file->getFileUri);
    }

    $data = [
      'nid' => $entity->id(),
      'title' => $entity->label(),
      'alias' => $this->getNodeAlias($entity),
      'last_updated' => $entity->changed->value,
      'position' => $this->getValue($entity, 'field_position'),
      'promoted' => $this->getValue($entity, 'promoted'),
      'release_date' => $this->getValue($entity, 'field_release_date'),
      'logo' => $this->getStyles($entity->field_logo->entity->image->entity, $entity->field_logo->entity, 'image'),
      'art' => $this->getStyles($entity->field_ticket_art->entity->image->entity, $entity->field_ticket_art->entity, 'image'),
      'cropped_art' => $this->getStyles($entity->field_image->entity->image->entity, $entity->field_image->entity, 'image'),
      'description' => $this->getValue($entity, 'field_pbody'),
      'ticket_price' => $this->getValue($entity, 'field_ticket_price'),
      'game_number' => $this->getValue($entity, 'field_game_number'),
      'price_point' => $this->getValue($entity, 'field_price_point'),
      'top_prize_amount' => $this->getValue($entity, 'field_top_prize_amount'),
      'top_prize_remaining' => $this->getValue($entity, 'field_top_prize_remaining'),
      'prizes_thru_date' => $this->getValue($entity, 'field_prizes_thru_date'),
      'prizes_paid_out' => $this->getValue($entity, 'field_prizes_paid_out'),
      'cta_button' => $buttons,
      'overall_odds' => $this->getValue($entity, 'field_overall_odds'),
      'how_to_play' => $howToPlay,
      'game_features' => $gameFeatures,
      'odds_prizes' => $oddsAndPrizes,
      'weekly_report' => $weeklyReportUri,
    ];

    return $data;
  }

  /**
   * Second Chance games.
   */
  public function secondChanceGameData(EntityInterface $entity) {
    $buttons = [];
    foreach ($entity->field_cta_buttons_adv->referencedEntities() as $paragraph) {
      $this->generateNormalizedParagraph($paragraph);
    }

    $data = [
      'nid' => $entity->id(),
      'title' => $entity->label(),
      'alias' => $this->getNodeAlias($entity),
      'logo' => $this->getStyles($entity->field_logo->entity->image->entity, $entity->field_logo->entity, 'image'),
      'description' => $this->getValue($entity, 'field_pbody'),
      'cta_button' => $buttons,
    ];

    return $data;
  }

  /**
   * Marquee.
   */
  public function marquee(EntityInterface $entity) {
    $image = isset($entity->field_image->entity->image->entity) ? $this->getStyles($entity->field_image->entity->image->entity, $entity->field_image->entity, 'image') : [];
    $mobile = isset($entity->field_media_mobile->entity->image->entity) ? $this->getStyles($entity->field_media_mobile->entity->image->entity, $entity->field_media_mobile->entity, 'image') : [];
    $tablet = isset($entity->field_media_tablet->entity->image->entity) ? $this->getStyles($entity->field_media_tablet->entity->image->entity, $entity->field_media_tablet->entity, 'image') : [];
    $wide = isset($entity->field_media_wide->entity->image->entity) ? $this->getStyles($entity->field_media_wide->entity->image->entity, $entity->field_media_wide->entity, 'image') : [];

    $game = $entity->field_game->entity;
    $gamedata = [];
    if ($game) {
      $gamedata = [
        'logo' => $image,
        'title' => $game->label(),
        'id' => $game->id(),
      ];
    }

    $cta_button = $entity->get('field_cta_button_marquee')->getValue();

    $data = [
      'image' => $image,
      'mobile' => $mobile,
      'tablet' => $tablet,
      'wide' => $wide,
      'game' => $gamedata,
      'position' => $this->getValue($entity, 'field_position'),
      'cta_button' => $cta_button,
    ];

    return $data;
  }

  /**
   * Return minimum data provide by node entity.
   *
   * Used by alert.
   */
  public function baseNode(EntityInterface $entity) {
    $data = [
      'nid' => $entity->id(),
      'alias' => $this->getNodeAlias($entity),
      'name' => $entity->label(),
      'description' => $this->getValue($entity, 'body'),
    ];
    return $data;
  }

  /**
   * Helper function for returning field values, without creating warnings.
   */
  private function getValue($entity, $field_name, $field_column = 'value') {
    if ($field_name == 'body' || $field_name == 'field_pbody' || $field_name == 'field_body' || $field_name == 'field_description') {
      return isset($entity->{$field_name}) ? ($entity->{$field_name}->{$field_column}) !== NULL ? $this->pathCheck($entity->{$field_name}->{$field_column}) : '' : '';
    }
    return isset($entity->{$field_name}) ? ($entity->{$field_name}->{$field_column}) !== NULL ? $entity->{$field_name}->{$field_column} : '' : '';
  }

  /**
   * Allow generation of references to image styles for a image.
   */
  public function getStyles($file, $entity, $field, $style = 'optimized') {
    $imageStyleUrls = [];
    if ($file) {
      if ($style) {
        $loaded_style = ImageStyle::load($style);
        if ($loaded_style) {
          $imageStyleUrls = [
            'uri' => $loaded_style->buildUrl($file->getFileUri()),
          ];
        }
      }
      else {
        $url = file_create_url($file->getFileUri());
        $url = str_replace('http:', 'https:', $url);
        $imageStyleUrls = [
          'uri' => $url,
        ] + $entity->{$field}->first()->getProperties();
      }
    }

    return $imageStyleUrls;
  }

  /**
   * Returns images.
   */
  public function getImages($entity, $field, $media_type = 'image', $style = '') {
    $images = [];
    if ($entity) {
      foreach ($entity->get($field)->referencedEntities() as $media) {
        $images[] = $this->getStyles($media->image->entity, $entity->{$field}->entity, $media_type, $style);
      }
    }
    return $images;
  }

  /**
   *
   */
  public function pathCheck($text) {
    return preg_replace_callback('~ (href|src|action|longdesc)="([^"]+)~i', '_pathologic_replace', $text);
  }

}
