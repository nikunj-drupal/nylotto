<?php

namespace Drupal\nylotto_custom_json\Normalizer;

use Drupal\serialization\Normalizer\ContentEntityNormalizer;
use Drupal\node\Entity\Node;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides the base class for other custom normalizers.
 */
class BaseNormalizer extends ContentEntityNormalizer {

  /**
   * Provides the allowed formats.
   *
   * @var array
   */
  public $format = ['json', 'api_json'];
  protected $pathAlias;

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, $context = []) {
    $service = \Drupal::service('ny_lotto.prewarm_normalizer');
    $data = $service->generateNormalizeArray($entity, FALSE);
    return $data;
  }

  /**
   * Returns the CTA buttons for a content type.
   */
  public function getCTAButtons($entity) {
    $data = [];

    if ($entity->field_cta_buttons_adv) {
      foreach ($entity->field_cta_buttons_adv->referencedEntities() as $button) {
        $data[] = [
          'link' => $this->getValue($button, 'field_link', 'uri'),
          'title' => $this->getValue($button, 'field_link', 'title'),
          'options' => $this->getValue($button, 'field_link', 'options'),
          'breakpoint' => $this->getValue($button, 'field_breakpoint'),
        ];
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function sections($entity) {
    $data = [];
    foreach ($entity->field_sections->referencedEntities() as $section) {
      $data[] = $this->getSection($section);
    }
    return $data;
  }

  /**
   * Returns this section entry.
   */
  public function getSection($entity) {
    $data = [
      'title' => $this->getValue($entity, 'field_title'),
      'sections' => [],
      'body' => $this->getValue($entity, 'field_body'),
      'background_image' => $this->getImages($entity, 'field_background_image'),
      'background_color' => $this->field_background_color->getValue()[0],
      'icon' => $this->getImages($entity, 'field_media'),
      'collapsible' => $this->getValue($entity, 'field_collapsible'),
      'collapsed' => $this->getValue($entity, 'field_collapsed'),
    ];

    foreach ($entity->field_sections->referencedEntities() as $section) {
      $data['sections'][] = $this->getSection($section);
    }

    return $data;
  }

  /**
   * Allow generation of references to image styles for a image.
   */
  public function getStyles($file, $entity, $field, $style = 'optimized') {
    $imageStyleUrls = [];
    if ($file) {
      if ($style) {
        $imageStyleUrls = [
          'uri' => ImageStyle::load($style)->buildUrl($file->getFileUri()),
        ];
      }
      elseif ($entity->{$field}) {
        $imageStyleUrls = [
          'uri' => file_create_url($file->getFileUri()),
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
    foreach ($entity->get($field)->referencedEntities() as $media) {
      $images[] = $this->getStyles($media->image->entity, $entity->{$field}->entity, $media_type, $style);
    }
    return $images;
  }

  /**
   * Creates an array of draw dates.
   */
  public function getDrawDays(Node $entity) {
    $days = [];
    foreach ($entity->field_draw_dates as $value) {
      $paragraph = entity_load('paragraph', $value->getValue()['target_id']);
      $days[] = [
        'date' => $this->getValue($paragraph, 'field_draw_date'),
        'winning_numbers' => ($paragraph->field_winning_numbers) ? $paragraph->field_winning_numbers->getValue() : [],
      ];
    }
    return $days;
  }

  /**
   * Returns the next drawing date.
   */
  public function getNextDate(Node $entity) {
    $next_date = '';
    foreach ($entity->field_draw_dates as $value) {
      $paragraph = entity_load('paragraph', $value->getValue()['target_id']);
      $next_date = $this->getValue($paragraph, 'field_draw_date');
    }
    return $next_date;
  }

  /**
   * Returns the How To Play annotation sections.
   */
  public function howToPlay($entity) {
    $data = [];
    foreach ($entity->field_play_rules->referencedEntities() as $paragraph) {
      $row = [
        'title' => $this->getValue($paragraph, 'field_section_title'),
        'description' => $this->getValue($paragraph, 'field_body'),
        'play_slip' => $this->getImages($paragraph, 'field_media'),
        'play_slip_cropped' => $this->getImages($paragraph, 'field_media', 'image', 'play_slip_cropped'),
        'ticket_art' => $this->getImages($paragraph, 'field_ticket_art'),
        'steps' => $this->getSteps($paragraph),
      ];
      $data[] = $row;
    }
    return $data;
  }

  /**
   * Returns the steps for a how to play section.
   */
  public function getSteps($entity) {
    $data = [];

    foreach ($entity->field_steps->referencedEntities() as $paragraph) {
      $data[] = [
        'title' => $this->getValue($paragraph, 'field_section_title'),
        'description' => $this->getValue($paragraph, 'field_body'),
      ];
    }
    return $data;
  }

  /**
   * Returns the Game features annotation sections.
   */
  public function gameFeatures($entity) {
    $data = [];
    foreach ($entity->field_game_features->referencedEntities() as $paragraph) {
      $row = [
        'title' => $this->getValue($paragraph, 'field_section_title'),
        'features' => [],
      ];

      $features = [];
      foreach ($paragraph->field_features->referencedEntities() as $p) {
        $features[] = [
          'title' => $this->getValue($p, 'field_section_title'),
          'description' => $this->getValue($p, 'field_body'),
          'image' => $this->getImages($p, 'field_media', 'image'),
        ];
      }
      $row['features'] = $features;
      $data[] = $row;
    }

    return $data;
  }

  /**
   * Returns the odds and prizes annotation sections.
   */
  public function oddsAndPrizes($entity) {
    $data = [];
    foreach ($entity->field_odds->referencedEntities() as $paragraph) {
      $pdfFiles = [];
      if ($paragraph->hasFIeld('field_pdf')) {
        foreach ($paragraph->field_pdf->referencedEntities() as $file) {
          $pdfFiles[] = ['uri' => file_create_url($file->getFileUri())];
        }
      }

      switch ($paragraph->bundle()) {
        case 'odds_and_prizes':
          $data[] = [
            'title' => $this->getValue($paragraph, 'field_section_title'),
            'type' => $paragraph->bundle(),
            'last_updated' => $this->getValue($paragraph, 'field_last_updated'),
            'win_combination' => $this->getValue($paragraph, 'field_win_combination'),
            'with_powerball' => $this->getValue($paragraph, 'field_with_powerball'),
            '3_play_prize' => $this->getValue($paragraph, 'field_3_play_prize'),
            '6_play_prize' => $this->getValue($paragraph, 'field_6_play_prize'),
            'ways_to_win' => $this->getValue($paragraph, 'field_ways_to_win'),
            'description' => $this->getValue($paragraph, 'field_body'),
            'pdf' => $pdfFiles,

                        // This is needed for the Scratch of details of the odds/prizes sections.
            'overall_odds' => $this->getValue($paragraph, 'field_odds'),
            'prize_amount' => $this->getValue($paragraph, 'field_prize_amount'),
            'prizes_paid_out' => $this->getValue($paragraph, 'field_prizes_paid_out'),
            'prizes_remaining' => $this->getValue($paragraph, 'field_prizes_remaining'),
            'sections' => [],
          ];

          // Loop through and get the sections of content for this odd and prize section.
          foreach ($paragraph->field_sections->referencedEntities() as $section) {
            $data['sections'][] = $this->getSection($section);
          }

          break;

        case 'odds_and_prizes_alt':
          $data[] = [
            'title' => $this->getValue($paragraph, 'field_section_title'),
            'type' => $paragraph->bundle(),
            'description' => $this->getValue($paragraph, 'field_body'),
            'body' => $this->getValue($paragraph, 'field_free_form_text'),
          ];
          break;
      }
    }
    return $data;
  }

  /**
   *
   */
  protected function getValue($entity, $field, $property = 'value') {
    return isset($entity->{$field}) ? $entity->{$field}->{$property} : '';
  }

}
