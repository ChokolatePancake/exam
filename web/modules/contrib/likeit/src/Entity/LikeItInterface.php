<?php

namespace Drupal\likeit\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Likeit entities.
 */
interface LikeItInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Likeit creation timestamp.
   *
   * @return string
   *   Creation timestamp of the Likeit.
   */
  public function getCreatedTime();

  /**
   * Sets the Likeit creation timestamp.
   *
   * @param string $timestamp
   *   The Likeit creation timestamp.
   *
   * @return \Drupal\likeit\Entity\LikeItInterface
   *   The called Likeit entity.
   */
  public function setCreatedTime(string $timestamp);

  /**
   * Gets the Likeit target entity id.
   *
   * @return string
   *   Target entity id.
   */
  public function getTargetEntityId();

  /**
   * Sets the Likeit target entity id.
   *
   * @param string $target_entity_id
   *   Target entity id.
   *
   * @return \Drupal\likeit\Entity\LikeItInterface
   *   The called Likeit entity.
   */
  public function setTargetEntityId(string $target_entity_id);

  /**
   * Gets the Likeit target entity type.
   *
   * @return string
   *   Target entity type.
   */
  public function getTargetEntityType();

  /**
   * Sets the Likeit target entity type.
   *
   * @param string $target_entity_type
   *   Target entity type.
   *
   * @return \Drupal\likeit\Entity\LikeItInterface
   *   The called Likeit entity.
   */
  public function setTargetEntityType(string $target_entity_type);

  /**
   * Gets the Likeit target entity.
   *
   * @return \Drupal\Core\Entity\EntityBase
   *   Target entity.
   */
  public function getTargetEntity();

  /**
   * Gets the Likeit owner session id.
   *
   * @return string
   *   Session id.
   */
  public function getSessionId();

  /**
   * Sets the Likeit owner session id.
   *
   * @param string $id
   *   Session id.
   *
   * @return \Drupal\likeit\Entity\LikeItInterface
   *   The called Likeit entity.
   */
  public function setSessionId(string $id);

}
