<?php

namespace Drupal\likeit\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\likeit\Access\LikeItCsrfTokenGenerator;
use Drupal\likeit\Event\LikeItEvent;
use Drupal\likeit\Event\LikeItEvents;
use Drupal\likeit\LikeitHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Likeit module routes.
 */
class LikeItController extends ControllerBase {

  /**
   * Custom token generator service.
   *
   * @var \Drupal\likeit\Access\LikeItCsrfTokenGenerator
   */
  protected $csrfGenerator;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Likeit helper service.
   *
   * @var \Drupal\likeit\LikeitHelper
   */
  protected $helper;

  /**
   * LikeItController constructor.
   *
   * @param \Drupal\likeit\Access\LikeItCsrfTokenGenerator $token_generator
   *   The custom token generator.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\likeit\LikeitHelper $helper
   *   Likeit helper service.
   */
  public function __construct(LikeItCsrfTokenGenerator $token_generator,
                              RequestStack $request_stack,
                              EventDispatcherInterface $event_dispatcher,
                              RendererInterface $renderer,
                              LikeitHelper $helper) {
    $this->csrfGenerator = $token_generator;
    $this->requestStack = $request_stack;
    $this->eventDispatcher = $event_dispatcher;
    $this->renderer = $renderer;
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('likeit_csrf_token'),
      $container->get('request_stack'),
      $container->get('event_dispatcher'),
      $container->get('renderer'),
      $container->get('likeit.helper')
    );
  }

  /**
   * Like action.
   *
   * @param string $entity_type
   *   Target entity type.
   * @param string $id
   *   Target entity id.
   * @param string $html_id
   *   Link DOM id.
   * @param string $token
   *   Csrf token.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\JsonResponse
   *   Return response string.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function like(string $entity_type, string $id, string $html_id, string $token = '') {
    $session_id = NULL;
    $user = $this->currentUser();
    $object = $this->entityTypeManager()->getStorage($entity_type)->load($id);
    if ($object) {
      try {
        // Check CSRF token.
        if ($this->csrfGenerator->validate($token, $html_id)) {
          $session_id = $this->helper->doLike($object, $user);

          // Crete new like/unlike event.
          $event = new LikeItEvent($object);

          // Use the event dispatcher service to notify any event subscribers.
          $this->eventDispatcher->dispatch($event, LikeItEvents::LIKE);
        }
      }
      catch (\LogicException $e) {
        // Do nothing on fail.
      }
    }

    return $this->response($entity_type, $id, $session_id, $html_id);
  }

  /**
   * Unlike action.
   *
   * @param string $entity_type
   *   Target entity name.
   * @param string $id
   *   Target entity id.
   * @param string $html_id
   *   Link DOM id.
   * @param string $token
   *   Csrf token.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\JsonResponse
   *   Return response string.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function unlike(string $entity_type, string $id, string $html_id, string $token = '') {
    $session_id = '';
    $user = $this->currentUser();
    $object = $this->entityTypeManager()->getStorage($entity_type)->load($id);
    if ($object) {
      try {
        // Check CSRF token.
        if ($this->csrfGenerator->validate($token, $html_id)) {
          $session_id = $this->helper->doUnlike($object, $user);

          // Crete new like/unlike event.
          $event = new LikeItEvent($object);

          // Use the event dispatcher service to notify any event subscribers.
          $this->eventDispatcher->dispatch($event, LikeItEvents::UNLIKE);
        }
      }
      catch (\LogicException $e) {
        // Do nothing on fail.
      }
    }

    return $this->response($entity_type, $id, $session_id, $html_id);
  }

  /**
   * Provides response to the user.
   *
   * @param string $entity_type
   *   Target entity type.
   * @param string $id
   *   Target entity id.
   * @param string $session_id
   *   User session id.
   * @param string $html_id
   *   Element DOM id.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|\Symfony\Component\HttpFoundation\JsonResponse
   *   Response to the user.
   *
   * @throws \Exception
   */
  public function response(string $entity_type, string $id, string $session_id, string $html_id) {
    $account = $this->currentUser();
    if ($account->isAnonymous() && !$this->helper->getCookie()) {
      $this->helper->setCookie($session_id);
    }

    $element = $this->helper->getLink($entity_type, $id);
    $link_id = '#' . $html_id;

    if (empty($element['#content']['link'])) {
      if (!empty($element['#content']['view'])) {
        $content = $element['#content']['view'];
      }
      else {
        $content = $element['#content']['message'];
      }
    }
    else {
      $new_html_id = $element['#content']['link']['#attributes']['id'];
      $token = $this->csrfGenerator->get($new_html_id);
      $element['#content']['link']['#url']->setRouteParameter('token', $token);
      $content = $element['#content']['link'];
    }

    // Possible to retrieve json response if use 'output=json' query param.
    if ($this->requestStack->getCurrentRequest()->query->get('output') === 'json') {
      $response = new JsonResponse();
      $data = [
        'url' => !empty($element['#url']) ? $element['#url']->setAbsolute()->toString() : '',
        'action' => $element['#action'],
        'count' => $element['#count'] ?? '',
        'new_html_id' => $new_html_id ?? '',
      ];
      $response->setData($data);
    }
    else {
      $response = new AjaxResponse();
      // Update like/unlike link.
      $replace = new ReplaceCommand($link_id, $this->renderer->render($content));
      $response->addCommand($replace);
    }

    return $response;
  }

  /**
   * Check user permissions to like/unlike/view.
   *
   * @param string $action
   *   Action name.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   User account or null.
   *
   * @return bool
   *   Access grant status.
   */
  public static function checkAccess(string $action = 'like', AccountInterface $account = NULL) {
    if (!$account) {
      $account = \Drupal::currentUser();
    }

    switch ($action) {
      case 'like':
        return $account->hasPermission('likeit_like');

      case 'unlike':
        return $account->hasPermission('likeit_unlike');

      default:
        return $account->hasPermission('likeit_view');
    }
  }

}
