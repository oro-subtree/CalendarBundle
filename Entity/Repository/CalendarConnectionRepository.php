<?php

namespace Oro\Bundle\CalendarBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\CalendarBundle\Entity\CalendarConnection;

class CalendarConnectionRepository extends EntityRepository
{
    /**
     * Gets CalendarConnection object represents a connection between the given calendars
     *
     * @param $calendarId
     * @param $connectedCalendarId
     * @return CalendarConnection
     */
    public function findByRelation($calendarId, $connectedCalendarId)
    {
        return $this->findOneBy(array('calendar' => $calendarId, 'connectedCalendar' => $connectedCalendarId));
    }

    /**
     * @param int $calendarId
     * @return QueryBuilder
     */
    public function getConnectionsQueryBuilder($calendarId)
    {
        return $this->getEntityManager()->getRepository('OroCalendarBundle:CalendarConnection')->createQueryBuilder('a')
            ->select(
                'a.color, a.backgroundColor'
                . ', ac.id as calendar, ac.name as calendarName'
                . ', u.id as owner, u.firstName as ownerFirstName, u.lastName as ownerLastName'
            )
            ->innerJoin('a.calendar', 'c')
            ->innerJoin('a.connectedCalendar', 'ac')
            ->innerJoin('ac.owner', 'u')
            ->where('c.id = :id')
            ->orderBy('a.createdAt')
            ->setParameter('id', $calendarId);
    }
}
