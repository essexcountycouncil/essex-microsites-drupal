<?php

namespace Drupal\group_context_domain\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\domain\DomainInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks the validity of a group role's scope.
 */
class DomainGroupUniqueValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs a DomainGroupUniqueValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The entity repository.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityRepositoryInterface $entityRepository,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($domain, Constraint $constraint) {
    if (!isset($domain)) {
      return;
    }
    assert($domain instanceof DomainInterface);
    assert($constraint instanceof DomainGroupUnique);

    $group_uuid = $domain->getThirdPartySetting('group_context_domain', 'group_uuid');
    if (empty($group_uuid)) {
      return;
    }

    $query = $this->entityTypeManager->getStorage('domain')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('third_party_settings.group_context_domain.group_uuid', $group_uuid, '=')
      ->count();

    if (!$domain->isNew()) {
      $query->condition('id', $domain->id(), '<>');
    }

    if ($query->execute() > 0) {
      $group = $this->entityRepository->loadEntityByUuid('group', $group_uuid);
      $this->context->buildViolation($constraint->message)
        ->setParameter('%group_label', $group->label())
        ->atPath('third_party_settings.group_context_domain.group_uuid')
        ->addViolation();
    }
  }

}
