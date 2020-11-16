<?php declare(strict_types=1);

namespace Drupal\membership;

use Drupal\Core\Entity\EntityRepository;
use Drupal\Core\Session\AccountInterface;

/**
 * Repository for memberships.
 */
class MembershipRepository extends EntityRepository implements MembershipRepositoryInterface {

  /**
   * {@inheritDoc}
   */
  public function loadMultipleForAccount(AccountInterface $account): array {
    return $this->entityTypeManager->getStorage('membership')
      ->loadByProperties(['user_id' => $account->id()]);
  }

}
