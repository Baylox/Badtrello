<?php

namespace App\Repository;

use App\Entity\Board;
use DateTimeImmutable;
use App\Entity\BoardInvitation;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<BoardInvitation>
 */
class BoardInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BoardInvitation::class);
    }

    /**
     * Persist an invitation (and flush the current unit of work).
     */
    public function save(BoardInvitation $invitation, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->persist($invitation);

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Remove an invitation.
     */
    public function remove(BoardInvitation $invitation, bool $flush = true): void
    {
        $em = $this->getEntityManager();
        $em->remove($invitation);

        if ($flush) {
            $em->flush();
        }
    }

    /**
     * Find a valid invitation by token
     * An invitation is valid if it exists, is not accepted yet and not expired
     * @param string $token
     * @return BoardInvitation|null
     */
    public function findValidByToken(string $token): ?BoardInvitation
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.token = :token')
            ->andWhere('i.isAccepted = :false')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('token', $token)
            ->setParameter('false', false)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find pending invitations for a specific email and board
     * @param string $email
     * @param Board $board
     * @return BoardInvitation|null
     */
    public function findPendingByEmailAndBoard(string $email, Board $board): ?BoardInvitation
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.email = :email')
            ->andWhere('i.board = :board')
            ->andWhere('i.isAccepted = :false')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('email', mb_strtolower(trim($email)))
            ->setParameter('board', $board)
            ->setParameter('false', false)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all pending invitations for a board
     * @param Board $board
     * @return BoardInvitation[]
     */
    public function findPendingByBoard(Board $board): array
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.board = :board')
            ->andWhere('i.isAccepted = :false')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('board', $board)
            ->setParameter('false', false)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all pending invitations for an email
     * @param string $email
     * @return BoardInvitation[]
     */
    public function findPendingByEmail(string $email): array
    {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.board', 'b')->addSelect('b')
            ->andWhere('i.email = :email')
            ->andWhere('i.isAccepted = :false')
            ->andWhere('i.expiresAt > :now')
            ->setParameter('email', mb_strtolower(trim($email)))
            ->setParameter('false', false)
            ->setParameter('now', new DateTimeImmutable())
            ->orderBy('i.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete expired invitations
     * @return int Number of deleted invitations
     */
    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('i')
            ->delete()
            ->andWhere('i.expiresAt < :now')
            ->andWhere('i.isAccepted = :false')
            ->setParameter('now', new DateTimeImmutable())
            ->setParameter('false', false)
            ->getQuery()
            ->execute();
    }
}
