<?php

namespace Tourze\RealNameAuthenticationBundle\Repository;

use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\RealNameAuthenticationBundle\Entity\RealNameAuthentication;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationStatus;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationType;

/**
 * 实名认证Repository实现
 * 
 * @method RealNameAuthentication|null find($id, $lockMode = null, $lockVersion = null)
 * @method RealNameAuthentication|null findOneBy(array $criteria, array $orderBy = null)
 * @method RealNameAuthentication[] findAll()
 * @method RealNameAuthentication[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RealNameAuthenticationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RealNameAuthentication::class);
    }

    /**
     * 根据用户查询认证记录
     */
    public function findByUser(UserInterface $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据用户标识符查询认证记录
     */
    public function findByUserIdentifier(string $userIdentifier): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->andWhere('u.userIdentifier = :userIdentifier')
            ->setParameter('userIdentifier', $userIdentifier)
            ->orderBy('r.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据状态查询认证记录
     */
    public function findByStatus(AuthenticationStatus $status): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', $status)
            ->orderBy('r.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 根据用户和认证类型查询认证记录
     */
    public function findByUserAndType(UserInterface $user, AuthenticationType $type): ?RealNameAuthentication
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->orderBy('r.createTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 查询已过期的认证记录
     */
    public function findExpiredAuthentications(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.expireTime IS NOT NULL')
            ->andWhere('r.expireTime < :now')
            ->andWhere('r.status = :approved')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('approved', AuthenticationStatus::APPROVED)
            ->getQuery()
            ->getResult();
    }

    /**
     * 统计指定状态和时间范围内的认证记录数量
     */
    public function countByStatusAndDateRange(
        AuthenticationStatus $status,
        DateTimeInterface $start,
        DateTimeInterface $end
    ): int {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.status = :status')
            ->andWhere('r.createTime >= :start')
            ->andWhere('r.createTime <= :end')
            ->setParameter('status', $status)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * 查询用户最新的认证记录
     */
    public function findLatestByUser(UserInterface $user): ?RealNameAuthentication
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 查询用户指定类型的有效认证记录
     */
    public function findValidByUserAndType(UserInterface $user, AuthenticationType $type): ?RealNameAuthentication
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.type = :type')
            ->andWhere('r.status = :approved')
            ->andWhere('(r.expireTime IS NULL OR r.expireTime > :now)')
            ->andWhere('r.valid = true')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->setParameter('approved', AuthenticationStatus::APPROVED)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('r.createTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * 分页查询认证记录
     */
    public function findWithPagination(int $page, int $size, array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('r');

        // 应用筛选条件
        if (isset($criteria['status'])) {
            $qb->andWhere('r.status = :status')
               ->setParameter('status', $criteria['status']);
        }

        if (isset($criteria['type'])) {
            $qb->andWhere('r.type = :type')
               ->setParameter('type', $criteria['type']);
        }

        if (isset($criteria['user'])) {
            $qb->andWhere('r.user = :user')
               ->setParameter('user', $criteria['user']);
        }

        if (isset($criteria['userIdentifier'])) {
            $qb->join('r.user', 'u')
               ->andWhere('u.userIdentifier = :userIdentifier')
               ->setParameter('userIdentifier', $criteria['userIdentifier']);
        }

        if (isset($criteria['method'])) {
            $qb->andWhere('r.method = :method')
               ->setParameter('method', $criteria['method']);
        }

        if (isset($criteria['createTimeStart'])) {
            $qb->andWhere('r.createTime >= :createTimeStart')
               ->setParameter('createTimeStart', $criteria['createTimeStart']);
        }

        if (isset($criteria['createTimeEnd'])) {
            $qb->andWhere('r.createTime <= :createTimeEnd')
               ->setParameter('createTimeEnd', $criteria['createTimeEnd']);
        }

        $offset = ($page - 1) * $size;

        return $qb->orderBy('r.createTime', 'DESC')
                  ->setFirstResult($offset)
                  ->setMaxResults($size)
                  ->getQuery()
                  ->getResult();
    }
} 