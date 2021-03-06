<?php

/**
 * @file
 * Contains \Drupal\page_manager\Entity\PageAccess.
 */

namespace Drupal\page_manager\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Condition\ConditionAccessResolverTrait;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the page entity type.
 */
class PageAccess extends EntityAccessControlHandler implements EntityHandlerInterface {

  use ConditionAccessResolverTrait;

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * Constructs an access control handler instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   *   The context handler.
   */
  public function __construct(EntityTypeInterface $entity_type, ContextHandlerInterface $context_handler) {
    parent::__construct($entity_type);
    $this->contextHandler = $context_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('context.handler')
    );
  }

  /**
   * Wraps the context handler.
   *
   * @return \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected function contextHandler() {
    return $this->contextHandler;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    /** @var $entity \Drupal\page_manager\PageInterface */
    if ($operation == 'view') {
      if (!$entity->status()) {
        return AccessResult::forbidden()->cacheUntilEntityChanges($entity);
      }

      $conditions = $entity->getAccessConditions();
      $contexts = $entity->getExecutable()->getContexts();
      foreach ($conditions as $condition) {
        if ($condition instanceof ContextAwarePluginInterface) {
          $this->contextHandler()->applyContextMapping($condition, $contexts);
        }
      }
      return AccessResult::allowedIf($this->resolveConditions($conditions, $entity->getAccessLogic(), $contexts));
    }
    if ($operation == 'delete' && $entity->isFallbackPage()) {
      return AccessResult::forbidden()->cacheUntilEntityChanges($entity);
    }
    return parent::checkAccess($entity, $operation, $langcode, $account);
  }

}
