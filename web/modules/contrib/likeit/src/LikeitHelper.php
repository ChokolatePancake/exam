<?php

namespace Drupal\likeit;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\likeit\Access\LikeItCsrfTokenGenerator;
use Drupal\likeit\Controller\LikeItController;
use Drupal\user\UserInterface;

/**
 * Service for like/unlike functionality.
 */
class LikeitHelper {

  use StringTranslationTrait;

  /**
   * Widget type id.
   *
   * @var string
   */
  protected $widgetType;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The likeit storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $likeitStorage;

  /**
   * The custom token generator.
   *
   * @var \Drupal\likeit\Access\LikeItCsrfTokenGenerator
   */
  protected $csrfTokenGenerator;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor for LikeitHelper.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\likeit\Access\LikeItCsrfTokenGenerator $csrf_token_generator
   *   The custom token generator.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(AccountInterface $current_user,
                              EntityTypeManagerInterface $entity_type_manager,
                              LikeItCsrfTokenGenerator $csrf_token_generator,
                              RendererInterface $renderer,
                              ConfigFactoryInterface $config_factory) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->likeitStorage = $entity_type_manager->getStorage('likeit');
    $this->csrfTokenGenerator = $csrf_token_generator;
    $this->renderer = $renderer;
    $this->configFactory = $config_factory;
    $this->widgetType = $this->configFactory->get('likeit.settings')
      ->get('widget_type') ?? 'likeit';
  }

  /**
   * Add need theme to render array.
   *
   * @param array $variables
   *   Render array with variables.
   *
   * @return array
   *   Render array with theme.
   */
  protected function theme(array $variables) {
    $variables['#theme'] = $this->widgetType;
    return $variables;
  }

  /**
   * Get link to like/unlike or count info.
   *
   * @param string $entity_type
   *   Target entity type.
   * @param string $id
   *   Target bundle id.
   *
   * @return array
   *   Render or empty array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getLink(string $entity_type, string $id) {
    $user = $this->currentUser;
    $action = $this->getLinkActionType($entity_type, $id);

    if (LikeItController::checkAccess($action, $user)) {
      return $this->buildLink($entity_type, $id, $action);
    }
    elseif (LikeItController::checkAccess('view', $user)) {
      return $this->buildView($entity_type, $id);
    }

    return [];
  }

  /**
   * Get likes count.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Target entity.
   *
   * @return int
   *   Likes count for the entity.
   */
  public function getCount(EntityInterface $entity) {
    $like_count = 0;

    $likes = $this->likeitStorage->getQuery()
      ->condition('target_entity_id', $entity->id())
      ->condition('target_entity_type', $entity->getEntityType()->id())
      ->execute();

    if (!empty($likes)) {
      $like_count = count($likes);
    }

    return $like_count;
  }

  /**
   * Build content likes view.
   *
   * @param string $entity_type
   *   Target entity type.
   * @param string $id
   *   Target entity id.
   *
   * @return array
   *   Render or empty array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildView(string $entity_type, string $id) {
    $entity = $this->entityTypeManager->getStorage($entity_type)->load($id);

    if (!$entity) {
      return [];
    }

    $count = $this->getCount($entity);

    $view = [
      '#type' => 'markup',
      '#markup' => $this->formatPlural($count, '1 like', '@count likes'),
      '#prefix' => '<span class="likeit-count">',
      '#suffix' => '</span>',
    ];

    return $this->theme([
      '#content' => [
        'view' => $view,
      ],
      '#action' => 'View',
      '#count' => $count,
    ]);
  }

  /**
   * Build the link widget.
   *
   * @param string $entity_type
   *   Target entity type.
   * @param string $id
   *   Target entity id.
   * @param string $action
   *   (Optional) Action name.
   *
   * @return array
   *   Like/unlike render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildLink(string $entity_type, string $id, string $action = 'like') {
    $user = $this->currentUser;
    $count = '';

    $entity = $this->entityTypeManager->getStorage($entity_type)->load($id);
    $html_id = uniqid('likeit-id-' . $id);
    $html_id = Html::getId($html_id);

    $token = $this->csrfTokenGenerator->get($html_id);
    $parameters = [
      'entity_type' => $entity_type,
      'id' => $entity->id(),
      'html_id' => $html_id,
      'token' => $token,
    ];

    $title = $this->t('Like');
    $class = '';

    // Check action type to get like/unlike url.
    if ($action == 'unlike') {
      $url = Url::fromRoute('likeit.likeit_controller_unlike', $parameters);
      $title = $this->t('Unlike');
      $class = 'liked';
    }
    else {
      $url = Url::fromRoute('likeit.likeit_controller_like', $parameters);
    }

    $link = [
      '#type' => 'link',
      '#title' => $title,
      '#url' => $url,
    ];
    $link['#attributes']['title'] = $title;
    $link['#attributes']['class'] = ['use-ajax', 'likeit-wrapper', $class];
    $link['#attributes']['id'] = $html_id;

    // Check if user can view likes count and add it to render array.
    if (LikeItController::checkAccess('view', $user)) {
      $count = $this->getCount($entity);

      $markup = [
        '#type' => 'markup',
        '#markup' => '<span class="likeit-title">' . $title . '</span><span class="likeit-count"> ' . $count . '</span>',
      ];

      $link['#title'] = $this->renderer->render($markup);
    }

    return $this->theme([
      '#content' => [
        'link' => $link,
      ],
      '#url' => $url->setAbsolute(),
      '#html_id' => $html_id,
      '#count' => $count,
      '#action' => ucfirst($action),
    ]);
  }

  /**
   * Get user session ID.
   *
   * @return string
   *   The session id.
   */
  protected function getUserSessionId() {
    $session_id = uniqid();
    $user = $this->currentUser;
    if ($user->isAnonymous()) {
      $cookie = $this->getCookie();
      if (!empty($cookie)) {
        $session_id = $cookie;
      }
      else {
        $this->setCookie($session_id);
      }
    }

    return $session_id;
  }

  /**
   * Do like.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Target entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (Optional) User account.
   *
   * @return string|null
   *   Session id or null.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function doLike(EntityInterface $entity, AccountInterface $account = NULL) {
    if (empty($account)) {
      $account = $this->currentUser;
    }

    if ($this->statusCheck($entity, $account)) {
      // Entity was liked.
      return NULL;
    }

    $session_id = $this->getCookie();

    if (!$session_id) {
      $session_id = $this->getUserSessionId();
    }

    $entity_type = $entity->getEntityType()->id();

    $values = [
      'user_id' => $account->id(),
      'target_entity_type' => $entity_type,
      'target_entity_id' => $entity->id(),
      'session_id' => $session_id,
    ];

    // Create new likeit entity.
    $like = $this->likeitStorage->create($values);
    $like->save();

    // Reset entity cache.
    $this->entityTypeManager
      ->getViewBuilder($entity->getEntityTypeId())
      ->resetCache([$entity]);

    return $session_id;
  }

  /**
   * Do unlike.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Target entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) User account.
   *
   * @return string|null
   *   Session id or null.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function doUnlike(EntityInterface $entity, AccountInterface $account = NULL) {
    if (empty($account)) {
      $account = $this->currentUser;
    }

    if (!$this->statusCheck($entity, $account)) {
      return NULL;
    }

    $session_id = $this->getCookie();
    if (!$session_id) {
      $session_id = $this->getUserSessionId();
    }

    $entity_type = $entity->getEntityType()->id();
    $query = $this->likeitStorage->getQuery()
      ->condition('target_entity_type', $entity_type)
      ->condition('target_entity_id', $entity->id())
      ->condition('user_id', $account->id());

    // Set session id query parameter for anonymous.
    if ($account->isAnonymous()) {
      $cookie = $this->getCookie();
      if (!$cookie) {
        return NULL;
      }
      $query = $query->condition('session_id', $cookie);
    }

    $likes = $query->execute();

    $entities = $this->likeitStorage->loadMultiple($likes);
    $this->likeitStorage->delete($entities);

    // Reset entity cache.
    $this->entityTypeManager
      ->getViewBuilder($entity->getEntityTypeId())
      ->resetCache([$entity]);

    return $session_id;
  }

  /**
   * Set cookie.
   *
   * @param string $session_id
   *   User session id.
   */
  public function setCookie(string $session_id) {
    setcookie('likeit_session', $session_id, time() + (86400 * 7), '/');

    // Likeit uses Ajax and page isn't reloading.
    // That is why we manually set $_COOKIE
    // because once the cookies have been set,
    // they can be accessed only on the next page load.
    $_COOKIE['likeit_session'] = $session_id;
  }

  /**
   * Get cookie.
   *
   * @return string|bool
   *   Cookie id or False.
   */
  public function getCookie() {
    return !empty($_COOKIE['likeit_session']) ? $_COOKIE['likeit_session'] : FALSE;
  }

  /**
   * Get link action type.
   *
   * @param string $entity_type
   *   Target entity type.
   * @param string $id
   *   Target entity id.
   *
   * @return string|null
   *   Action name or null.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getLinkActionType(string $entity_type, string $id) {
    $action = 'like';
    $user = $this->currentUser;

    $entity = $this->entityTypeManager->getStorage($entity_type)->load($id);
    if (!$entity) {
      return NULL;
    }

    $query = $this->likeitStorage->getQuery()
      ->condition('target_entity_type', $entity_type)
      ->condition('target_entity_id', $entity->id())
      ->condition('user_id', $user->id());

    // Set session id query parameter for anonymous.
    if ($user->isAnonymous()) {
      $cookie = $this->getCookie();
      if (!$cookie) {
        return $action;
      }
      $query = $query->condition('session_id', $cookie);
    }

    $likes = $query->execute();

    if (!empty($likes)) {
      // User has liked this content.
      $action = 'unlike';
    }

    return $action;
  }

  /**
   * Check like status.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Target entity.
   * @param \Drupal\Core\Session\AccountInterface|null $user
   *   (optional) User account.
   *
   * @return bool
   *   User like status. TRUE if entity was already liked.
   */
  public function statusCheck(EntityInterface $entity, AccountInterface $user = NULL) {
    if (empty($user)) {
      $user = $this->currentUser;
    }

    $query = $this->likeitStorage->getQuery()
      ->condition('target_entity_type', $entity->getEntityTypeId())
      ->condition('target_entity_id', $entity->id())
      ->condition('user_id', $user->id());

    // Set session id query parameter for anonymous.
    if ($user->isAnonymous()) {
      $cookie = $this->getCookie();
      if (!$cookie) {
        return FALSE;
      }
      $query = $query->condition('session_id', $cookie);
    }

    $likes = $query->execute();

    if (!empty($likes)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Remove all Likeit entities from user account.
   *
   * @param \Drupal\user\UserInterface $account
   *   User account.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeFromUser(UserInterface $account) {
    $likes = $this->likeitStorage->getQuery()
      ->condition('user_id', $account->id())
      ->execute();

    if (!empty($likes)) {
      $action = $this->configFactory->get('likeit.settings')
        ->get('after_owner_deletion');
      $entities = $this->likeitStorage->loadMultiple($likes);

      // Set owner to anonymous.
      if ($action == 'set_to_anonymous') {
        foreach ($entities as $likeit) {
          $likeit->setOwnerId(0)->save();
        }
      }
      else {
        // Delete Likeit content.
        $this->likeitStorage->delete($entities);
      }
    }
  }

  /**
   * Delete Likeit entities with target entity deletion.
   *
   * @param string $type
   *   Target entity type.
   * @param string $id
   *   Target entity id.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function removeFromEntity(string $type, string $id) {
    $likes = $this->likeitStorage->getQuery()
      ->condition('target_entity_type', $type)
      ->condition('target_entity_id', $id)
      ->execute();

    $entities = $this->likeitStorage->loadMultiple($likes);
    $this->likeitStorage->delete($entities);
  }

}
