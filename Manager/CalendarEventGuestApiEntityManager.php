<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class CalendarEventGuestApiEntityManager extends ApiEntityManager
{
    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var AssociationManager */
    protected $associationManager;

    /**
     * @param ObjectManager      $om
     * @param EntityNameResolver $resolver
     * @param AssociationManager $am
     */
    public function __construct(ObjectManager $om, EntityNameResolver $resolver, AssociationManager $am)
    {
        parent::__construct('Oro\Bundle\CalendarBundle\Entity\CalendarEvent', $om);
        $this->entityNameResolver = $resolver;
        $this->associationManager = $am;
    }

    /**
     * {@inheritdoc}
     */
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        $userNameDQL = $this->entityNameResolver->getNameDQL('Oro\Bundle\UserBundle\Entity\User', 'u');
        $criteria    = $this->prepareQueryCriteria($limit ? : null, $page, $criteria, $orderBy);

        return $this->getRepository()->createQueryBuilder('e')
            ->select('e.id, e.invitationStatus, u.email,' . sprintf('%s AS userFullName', $userNameDQL))
            ->join('e.calendar', 'c')
            ->join('c.owner', 'u')
            ->addCriteria($criteria);
    }
}
