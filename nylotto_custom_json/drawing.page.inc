<?php

/**
 * @file
 * Contains drawing.page.inc.
 *
 * Page callback for Drawing entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Drawing templates.
 *
 * Default template: drawing.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_drawing(array &$variables) {
  // Fetch Drawing Entity Object.
  $drawing = $variables['elements']['#drawing'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
