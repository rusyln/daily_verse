<?php

namespace Drupal\daily_verse\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Daily Verse routes.
 */
class DailyVerseController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
