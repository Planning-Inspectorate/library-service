<?php declare(strict_types = 1);

namespace Drupal\pins_kl_import\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @todo Add description for this subscriber.
 */
final class PinsKlImportSubscriber implements EventSubscriberInterface {

  /**
   * Kernel request event handler.
   */
  public function onKernelRequest(RequestEvent $event): void {
    // @todo Place your code here.
  }

  /**
   * Kernel response event handler.
   */
  public function onKernelResponse(ResponseEvent $event): void {
    // @todo Place your code here.
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
      KernelEvents::RESPONSE => ['onKernelResponse'],
    ];
  }

}
