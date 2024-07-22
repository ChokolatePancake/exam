<?php

namespace Drupal\extrafield_views_integration\lib;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface ExtrafieldCallbackInterface.
 *
 * @package Drupal\extrafield_views_integration
 */
interface ExtrafieldRenderClassInterface {

  /**
   * Render function will be called from views.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array|string
   *   Render array or string.
   */
  public static function render(EntityInterface $entity);

}
