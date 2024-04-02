<?php

namespace Drupal\nylotto_custom_json\Normalizer;

use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Converts Drupal entity structure for Term{Game Category} into an array.
 */
class ServiceCenter extends BaseNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\taxonomy\Entity\Term';

  /**
   * Provides the entity query function.
   *
   * @var entityQuery
   */
  protected $entityQuery;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $query) {
    parent::__construct($entity_type_manager);
    $this->entityQuery = $entity_type_manager->getStorage('paragraph')->getQuery();
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    if (!is_object($data) && !in_array($format, $this->format)) {
      return FALSE;
    }
    if ($data instanceof Term) {
      if ($data->vid->target_id == 'customer_service_centers') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, $context = []) {
    $service = \Drupal::service('ny_lotto.prewarm_normalizer');
    $data = $service->generateNormalizedTerm($entity, FALSE);
    return $data;
  }

}
