<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class CalendarEventManager
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var NameFormatter */
    protected $nameFormatter;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param SecurityFacade      $securityFacade
     * @param NameFormatter       $nameFormatter
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade,
        NameFormatter $nameFormatter
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->securityFacade = $securityFacade;
        $this->nameFormatter  = $nameFormatter;
    }

    /**
     * Gets a list of system calendars for which it is granted to add events
     *
     * @return array of [id, name, public]
     */
    public function getSystemCalendars()
    {
        /** @var SystemCalendarRepository $repo */
        $repo      = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:SystemCalendar');
        $calendars = $repo->getCalendarsQueryBuilder($this->securityFacade->getOrganizationId())
            ->select('sc.id, sc.name, sc.public')
            ->getQuery()
            ->getArrayResult();

        // @todo: check ACL here. will be done in BAP-6575

        return $calendars;
    }

    /**
     * Gets a list of user's calendars for which it is granted to add events
     *
     * @return array of [id, name]
     */
    public function getUserCalendars()
    {
        /** @var CalendarRepository $repo */
        $repo      = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Calendar');
        $calendars = $repo->getUserCalendarsQueryBuilder(
            $this->securityFacade->getOrganizationId(),
            $this->securityFacade->getLoggedUserId()
        )
            ->select('c.id, c.name')
            ->getQuery()
            ->getArrayResult();
        foreach ($calendars as &$calendar) {
            if (empty($calendar['name'])) {
                $calendar['name'] = $this->nameFormatter->format($this->securityFacade->getLoggedUser());
            }
        }

        return $calendars;
    }

    /**
     * Links an event with a calendar by its alias and id
     *
     * @param CalendarEvent $event
     * @param string        $calendarAlias
     * @param int           $calendarId
     */
    public function setCalendar(CalendarEvent $event, $calendarAlias, $calendarId)
    {
        if ($calendarAlias === Calendar::CALENDAR_ALIAS) {
            $calendar = $event->getCalendar();
            if (!$calendar || $calendar->getId() !== $calendarId) {
                $event->setCalendar($this->findCalendar($calendarId));
            }
        } elseif (in_array($calendarAlias, [SystemCalendar::CALENDAR_ALIAS, SystemCalendar::PUBLIC_CALENDAR_ALIAS])) {
            $event->setSystemCalendar($this->findSystemCalendar($calendarId));
        } else {
            throw new \LogicException(
                sprintf('Unexpected calendar alias: "%s". CalendarId: %d.', $calendarAlias, $calendarId)
            );
        }
    }

    /**
     * Gets UID of a calendar this event belongs to
     * The calendar UID is a string includes a calendar alias and id in the following format: {alias}_{id}
     *
     * @param string $calendarAlias
     * @param int    $calendarId
     *
     * @return string
     */
    public function getCalendarUid($calendarAlias, $calendarId)
    {
        return sprintf('%s_%d', $calendarAlias, $calendarId);
    }

    /**
     * Extracts calendar alias and id from a calendar UID
     *
     * @param string $calendarUid
     *
     * @return array [$calendarAlias, $calendarId]
     */
    public function parseCalendarUid($calendarUid)
    {
        $delim = strrpos($calendarUid, '_');

        return [
            substr($calendarUid, 0, $delim),
            (int)substr($calendarUid, $delim + 1)
        ];
    }

    /**
     * @param int $calendarId
     *
     * @return Calendar|null
     */
    protected function findCalendar($calendarId)
    {
        return $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Calendar')
            ->find($calendarId);
    }

    /**
     * @param int $calendarId
     *
     * @return SystemCalendar|null
     */
    protected function findSystemCalendar($calendarId)
    {
        return $this->doctrineHelper->getEntityRepository('OroCalendarBundle:SystemCalendar')
            ->find($calendarId);
    }
}
