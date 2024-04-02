<?php

namespace Drupal\nylotto_custom_json\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Use this to alter the response headers for views.
 * Add the total rows and current page to the header.
 */
class ViewsResponseHeader implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse'];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function onResponse(FilterResponseEvent $event) {
  }

}
