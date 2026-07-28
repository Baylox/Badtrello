<?php

namespace App\Repository;

use App\Entity\Account;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Account>
 */
class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    /**
     * Persist an account. Single entry point for account writes.
     */
    public function save(Account $account, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($account);

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Remove an account.
     */
    public function remove(Account $account, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->remove($account);

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Find a single account by its email.
     */
    public function findOneByEmail(string $email): ?Account
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Autocomplete usernames emails starting
     *
     * @param string $userInput Partial username to match.
     * @return string[] Array of matching usernames (emails).
     */
    public function autocompleteUsernames(string $userInput): array
    {
        $queryBuilder = $this->createQueryBuilder('user');

        $results = $queryBuilder
            ->select('user.email')
            ->andWhere('user.email LIKE :pattern')
            ->setParameter('pattern', $userInput . '%')
            ->getQuery()
            ->getSingleColumnResult();

        return $results;
    }

    /**
     * QueryBuilder to list accounts, possibly filtered by role and/or search term.
     *
     * @param string|null $role Role to filter by, or null/empty for no filtering.
     * @param string|null $search Search term to filter by name, lastname or email.
     * @return QueryBuilder
     */
    public function qbByRoleAndSearch(?string $role, ?string $search): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a');

        if ($role !== null && $role !== '') {
            $qb->andWhere('a.role = :role')
            ->setParameter('role', $role);
        }

        if ($search !== null && $search !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(a.name)', ':search'),
                    $qb->expr()->like('LOWER(a.lastname)', ':search'),
                    $qb->expr()->like('LOWER(a.email)', ':search')
                )
            )
            ->setParameter('search', '%' . strtolower($search) . '%');
        }

        // default sorting
        return $qb->orderBy('a.id', 'DESC');
    }

    /**
     * List of distinct roles actually present in the database (to populate the <select>).
     * @return string[] List of unique roles, sorted alphabetically
     */
    public function distinctRoles(): array
    {
        return array_column(
            $this->createQueryBuilder('a')
                ->select('DISTINCT a.role AS role')
                ->orderBy('a.role', 'ASC')
                ->getQuery()
                ->getScalarResult(),
            'role'
        );
    }


}
