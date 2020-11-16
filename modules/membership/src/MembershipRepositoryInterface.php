<?php declare(strict_types=1);

namespace Drupal\membership;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Session\AccountInterface;

interface MembershipRepositoryInterface extends EntityRepositoryInterface {

  /**
   * Load memberships for a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account.
   *
   * @return \Drupal\membership\Entity\MembershipInterface[]
   *   Memberships. May or may not be active.
   */
  public function loadMultipleForAccount(AccountInterface $account): array;

}
