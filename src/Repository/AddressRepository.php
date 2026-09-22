<?php

namespace App\Repository;

use App\Entity\Address;
use App\Type\ValueObject\Coordinates;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Address>
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    public function findByAllParameters(array $params): ?Address
    {
        return $this->createQueryBuilder('a')
            ->addSelect('c', 'p', 'co')
            ->join('a.city', 'c')
            ->join('c.province', 'p')
            ->join('p.country', 'co')
            ->where('a.address = :address')
            ->setParameter('address', $params['address'])
            ->andWhere('c.name = :city')
            ->setParameter('city', $params['city'])
            ->andWhere('co.symbol = :country')
            ->setParameter('country', $params['country'])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByCoordinates(Coordinates $coordinates): ?Address
    {
        return $this->createQueryBuilder('a')
            ->addSelect('c', 'p', 'co')
            ->join('a.city', 'c')
            ->join('c.province', 'p')
            ->join('p.country', 'co')
            ->where('a.coordinates = :point')
            ->setParameter('point', $coordinates, 'geography')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
