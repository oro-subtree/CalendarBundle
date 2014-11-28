<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class PublicCalendarProvider implements CalendarProviderInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var AbstractCalendarEventNormalizer */
    protected $calendarEventNormalizer;

    /** @var SystemCalendarConfigHelper */
    protected $calendarConfigHelper;

    /**
     * @param DoctrineHelper                    $doctrineHelper
     * @param AbstractCalendarEventNormalizer   $calendarEventNormalizer
     * @param SystemCalendarConfigHelper        $calendarConfigHelper
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        AbstractCalendarEventNormalizer $calendarEventNormalizer,
        SystemCalendarConfigHelper $calendarConfigHelper
    ) {
        $this->doctrineHelper          = $doctrineHelper;
        $this->calendarEventNormalizer = $calendarEventNormalizer;
        $this->calendarConfigHelper    = $calendarConfigHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarDefaultValues($organizationId, $userId, $calendarId, array $calendarIds)
    {
        $result = [];

        if (!$this->calendarConfigHelper->isPublicCalendarSupported()) {
            return $result;
        }

        /** @var SystemCalendarRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:SystemCalendar');
        $qb = $repo->getPublicCalendarsQueryBuilder();
        /** @var SystemCalendar[] $calendars */
        $calendars = $qb->getQuery()->getResult();

        foreach ($calendars as $calendar) {
            $resultItem = [
                'calendarName'  => $calendar->getName(),
                'removable'     => false,
                'position'      => -80,
            ];
            $result[$calendar->getId()] = $resultItem;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getCalendarEvents($organizationId, $userId, $calendarId, $start, $end, $connections)
    {
        if (!$this->calendarConfigHelper->isPublicCalendarSupported()) {
            return [];
        }

        /** @var CalendarEventRepository $repo */
        $repo = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:CalendarEvent');
        $qb = $repo->getPublicEventListByTimeIntervalQueryBuilder($start, $end);
        $invisibleIds = [];
        foreach ($connections as $id => $visible) {
            if (!$visible) {
                $invisibleIds[] = $id;
            }
        }
        if (!empty($invisibleIds)) {
            $qb
                ->andWhere('c.id NOT IN (:invisibleIds)')
                ->setParameter('invisibleIds', $invisibleIds);
        }

        return $this->calendarEventNormalizer->getCalendarEvents($calendarId, $qb->getQuery());
    }
}
