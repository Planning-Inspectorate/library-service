<?php

declare(strict_types=1);

namespace Drupal\pins_horizon_redirect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Resolves Horizon IDs to Drupal node URLs.
 */
final class HorizonRedirectController extends ControllerBase {

  /**
   * The entity type manager.
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs the controller.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Resolves Horizon ID and redirects to the matching node.
   *
   * @param int $horizon_id
   *   Horizon document ID.
   * @param int|null $version
   *   Optional document version.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response containing node ID and URL.
   */
  public function resolve(int $horizon_id, ?int $version = NULL): JsonResponse {

    $query = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', NodeInterface::PUBLISHED)
      ->condition('field_kl_doc_id', $horizon_id);

    if ($version !== NULL) {
      $query->condition('field_vernum', (int) $version);
    }
    else {
      // Return the latest version when no version is specified.
      $query->sort('field_vernum', 'DESC');
    }

    $nids = $query
      ->range(0, 1)
      ->execute();

    if (empty($nids)) {
      throw new NotFoundHttpException(sprintf(
        'No document found for Horizon ID %d.',
        $horizon_id
      ));
    }

    /** @var \Drupal\node\NodeInterface|null $node */
    $node = $this->entityTypeManager
      ->getStorage('node')
      ->load(reset($nids));

    if (!$node instanceof NodeInterface) {
      throw new NotFoundHttpException('Document not found.');
    }

    return new JsonResponse([
      'node_id' => $node->id(),
      'url' => $node->toUrl()->setAbsolute()->toString(),
    ]);
  }

}