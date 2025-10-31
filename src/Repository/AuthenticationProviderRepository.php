<?php

namespace Tourze\RealNameAuthenticationBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\RealNameAuthenticationBundle\Entity\AuthenticationProvider;
use Tourze\RealNameAuthenticationBundle\Enum\AuthenticationMethod;
use Tourze\RealNameAuthenticationBundle\Enum\ProviderType;

/**
 * 认证提供商Repository实现
 *
 * @extends ServiceEntityRepository<AuthenticationProvider>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: AuthenticationProvider::class)]
class AuthenticationProviderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthenticationProvider::class);
    }

    /**
     * 查询所有活跃的提供商
     *
     * @return array<int, AuthenticationProvider>
     *
     * @phpstan-return list<AuthenticationProvider>
     */
    public function findActiveProviders(): array
    {
        $result = $this->createQueryBuilder('p')
            ->andWhere('p.active = true')
            ->andWhere('p.valid = true')
            ->orderBy('p.priority', 'DESC')
            ->addOrderBy('p.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));
        /** @var list<AuthenticationProvider> $result */
        return $result;
    }

    /**
     * 根据认证方式查询提供商
     *
     * @return array<int, AuthenticationProvider>
     *
     * @phpstan-return list<AuthenticationProvider>
     */
    public function findByMethod(AuthenticationMethod $method): array
    {
        $result = $this->createQueryBuilder('p')
            ->andWhere('p.active = true')
            ->andWhere('p.valid = true')
            ->andWhere('p.supportedMethods LIKE :method')
            ->setParameter('method', '%"' . $method->value . '"%')
            ->orderBy('p.priority', 'DESC')
            ->addOrderBy('p.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));
        /** @var list<AuthenticationProvider> $result */
        return $result;
    }

    /**
     * 根据类型和认证方式查询提供商
     *
     * @return array<int, AuthenticationProvider>
     *
     * @phpstan-return list<AuthenticationProvider>
     */
    public function findByTypeAndMethod(ProviderType $type, AuthenticationMethod $method): array
    {
        $result = $this->createQueryBuilder('p')
            ->andWhere('p.type = :type')
            ->andWhere('p.active = true')
            ->andWhere('p.valid = true')
            ->andWhere('p.supportedMethods LIKE :method')
            ->setParameter('type', $type)
            ->setParameter('method', '%"' . $method->value . '"%')
            ->orderBy('p.priority', 'DESC')
            ->addOrderBy('p.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));
        /** @var list<AuthenticationProvider> $result */
        return $result;
    }

    /**
     * 按优先级排序查询提供商
     *
     * @return array<int, AuthenticationProvider>
     *
     * @phpstan-return list<AuthenticationProvider>
     */
    public function findByPriority(): array
    {
        $result = $this->createQueryBuilder('p')
            ->andWhere('p.active = true')
            ->andWhere('p.valid = true')
            ->orderBy('p.priority', 'DESC')
            ->addOrderBy('p.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));
        /** @var list<AuthenticationProvider> $result */
        return $result;
    }

    /**
     * 根据代码查询提供商
     *
     * @phpstan-return AuthenticationProvider|null
     */
    public function findByCode(string $code): ?AuthenticationProvider
    {
        $result = $this->createQueryBuilder('p')
            ->andWhere('p.code = :code')
            ->andWhere('p.valid = true')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof AuthenticationProvider ? $result : null;
    }

    /**
     * 查询支持指定认证方式的最佳提供商
     *
     * @phpstan-return AuthenticationProvider|null
     */
    public function findBestProviderForMethod(AuthenticationMethod $method): ?AuthenticationProvider
    {
        $result = $this->createQueryBuilder('p')
            ->andWhere('p.active = true')
            ->andWhere('p.valid = true')
            ->andWhere('p.supportedMethods LIKE :method')
            ->setParameter('method', '%"' . $method->value . '"%')
            ->orderBy('p.priority', 'DESC')
            ->addOrderBy('p.createTime', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof AuthenticationProvider ? $result : null;
    }

    /**
     * 根据类型查询活跃的提供商
     *
     * @return array<int, AuthenticationProvider>
     *
     * @phpstan-return list<AuthenticationProvider>
     */
    public function findActiveByType(ProviderType $type): array
    {
        $result = $this->createQueryBuilder('p')
            ->andWhere('p.type = :type')
            ->andWhere('p.active = true')
            ->andWhere('p.valid = true')
            ->setParameter('type', $type)
            ->orderBy('p.priority', 'DESC')
            ->addOrderBy('p.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));
        /** @var list<AuthenticationProvider> $result */
        return $result;
    }

    /**
     * 根据代码查询活跃的提供商
     *
     * @phpstan-return AuthenticationProvider|null
     */
    public function findByCodeAndActive(string $code): ?AuthenticationProvider
    {
        $result = $this->createQueryBuilder('p')
            ->andWhere('p.code = :code')
            ->andWhere('p.active = true')
            ->andWhere('p.valid = true')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result instanceof AuthenticationProvider ? $result : null;
    }

    /**
     * 查询所有启用的提供商
     *
     * @return array<int, AuthenticationProvider>
     *
     * @phpstan-return list<AuthenticationProvider>
     */
    public function findEnabledProviders(): array
    {
        $result = $this->createQueryBuilder('p')
            ->andWhere('p.active = true')
            ->andWhere('p.valid = true')
            ->orderBy('p.priority', 'DESC')
            ->addOrderBy('p.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        assert(is_array($result));
        /** @var list<AuthenticationProvider> $result */
        return $result;
    }

    public function save(AuthenticationProvider $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AuthenticationProvider $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
