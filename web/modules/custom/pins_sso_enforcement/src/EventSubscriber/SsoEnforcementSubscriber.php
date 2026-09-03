<?php

namespace Drupal\pins_sso_enforcement\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\externalauth\AuthmapInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Enforces SSO-only authentication routes/flows when configured.
 */
class SsoEnforcementSubscriber implements EventSubscriberInterface {

  /**
   * Routes blocked for anonymous users (redirect to SSO login).
   */
  const ANONYMOUS_BLOCKED_ROUTES = [
    'user.login',
    'user.pass',
    'user.pass.http',
  ];

  /**
   * Routes blocked for authenticated SSO-linked users.
   */
  const SSO_USER_BLOCKED_ROUTES = [
    'user.pass',
    'user.pass.http',
    'entity.user.edit_form',
  ];

  /**
   * The external auth provider identifier used by the openid_connect client.
   *
   * TODO: confirm this matches the actual provider string used by
   * openid_connect once externalauth/openid_connect are enabled locally.
   * Historically openid_connect registers authmap entries under a provider
   * of the form 'openid_connect.<client_id>'.
   */
  const SSO_PROVIDER = 'openid_connect.library_open_id';

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected RouteMatchInterface $routeMatch,
    protected MessengerInterface $messenger,
    protected AccountProxyInterface $currentUser,
    protected AuthmapInterface $authmap,
  ) {}

  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onRequest', 28],
    ];
  }

  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $config = $this->configFactory->get('pins_sso_enforcement.settings');
    if (!$config->get('enforce_sso')) {
      return;
    }

    $route_name = $this->routeMatch->getRouteName();

    if ($this->currentUser->isAnonymous()) {
      if (in_array($route_name, self::ANONYMOUS_BLOCKED_ROUTES, TRUE)) {
        $this->redirectToSso($event, 'Local login is disabled. Please sign in using Single Sign-On.');
      }
      return;
    }

    // Authenticated from here on.
    if (!in_array($route_name, self::SSO_USER_BLOCKED_ROUTES, TRUE)) {
      return;
    }

    // Only intervene for the user's OWN account routes, not admins managing
    // other users via /admin/people.
    if ($route_name === 'entity.user.edit_form') {
      $edited_uid = (int) $this->routeMatch->getRawParameter('user');
      if ($edited_uid !== (int) $this->currentUser->id()) {
        // Editing someone else's account (e.g. via People admin) — leave
        // to normal permission checks, not this subscriber's concern.
        return;
      }
    }

    $is_sso_user = (bool) $this->authmap->get((int) $this->currentUser->id(), self::SSO_PROVIDER);

    if (!$is_sso_user) {
      // Local (non-SSO) account, e.g. break-glass admin — allow normal flow.
      return;
    }

    $this->messenger->addWarning('Your account is managed via Single Sign-On. Please manage your password and profile details through Entra.');
    $event->setResponse(new RedirectResponse(Url::fromRoute('entity.user.canonical', ['user' => $this->currentUser->id()])->toString()));
  }

  protected function redirectToSso(RequestEvent $event, string $message): void {
    $this->messenger->addWarning($message);
    $redirect_url = Url::fromRoute('openid_connect.login')->toString();
    $event->setResponse(new RedirectResponse($redirect_url));
  }

}
