<?php

namespace Drupal\group_context_domain\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that each domain to group relationship is unique.
 *
 * @Constraint(
 *   id = "DomainGroupUnique",
 *   label = @Translation("Domain unique group", context = "Validation"),
 *   type = "entity:domain"
 * )
 */
class DomainGroupUnique extends Constraint {

  /**
   * The message to show when the relationship is not unique.
   *
   * @var string
   */
  public $message = 'The %group_label group is already tied to another domain.';

}
