<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Provider;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Provider\CalendarEventNormalizer;

class CalendarEventNormalizerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $reminderManager;

    /** @var CalendarEventNormalizer */
    protected $normalizer;

    protected function setUp()
    {
        $this->doctrine       = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->reminderManager = $this->getMockBuilder('Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->normalizer = new CalendarEventNormalizer(
            $this->doctrine,
            $this->securityFacade,
            $this->reminderManager
        );
    }

    /**
     * @dataProvider getCalendarEventsProvider
     */
    public function testGetCalendarEvents($events, $expected)
    {
        $calendarId = 123;
        $qb         = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $query      = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getResult'])
            ->getMockForAbstractClass();
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));
        $query->expects($this->once())
            ->method('getResult')
            ->will($this->returnValue($this->buildCalendarEvents($events)));

        if (!empty($events)) {
            $this->securityFacade->expects($this->exactly(2))
                ->method('isGranted')
                ->will(
                    $this->returnValueMap(
                        [
                            ['oro_calendar_event_update', null, true],
                            ['oro_calendar_event_delete', null, true],
                        ]
                    )
                );
        }

        $this->reminderManager->expects($this->once())
            ->method('applyReminders')
            ->with($expected, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        $result = $this->normalizer->getCalendarEvents($calendarId, $qb);
        $this->assertEquals($expected, $result);
    }

    public function getCalendarEventsProvider()
    {
        $startDate = new \DateTime();
        $endDate   = $startDate->add(new \DateInterval('PT1H'));

        return [
            [
                'events'    => [],
                'expected'  => []
            ],
            [
                'events'    => [
                    [
                        'calendar' => 123,
                        'id'       => 1,
                        'title'    => 'test',
                        'start'    => $startDate,
                        'end'      => $endDate
                    ],
                ],
                'expected'  => [
                    [
                        'calendar'         => 123,
                        'id'               => 1,
                        'title'            => 'test',
                        'description'      => null,
                        'start'            => $startDate->format('c'),
                        'end'              => $endDate->format('c'),
                        'allDay'           => false,
                        'backgroundColor'  => null,
                        'createdAt'        => null,
                        'updatedAt'        => null,
                        'parentEventId'    => null,
                        'invitationStatus' => null,
                        'childEvents'      => [],
                        'invitedUsers'     => [],
                        'editable'         => true,
                        'removable'        => true,
                        'notifiable'       => false
                    ],
                ]
            ],
            [
                'events'    => [
                    [
                        'calendar' => 123,
                        'id'       => 1,
                        'title'    => 'test',
                        'start'    => $startDate,
                        'end'      => $endDate
                    ],
                ],
                'expected'  => [
                    [
                        'calendar'         => 123,
                        'id'               => 1,
                        'title'            => 'test',
                        'description'      => null,
                        'start'            => $startDate->format('c'),
                        'end'              => $endDate->format('c'),
                        'allDay'           => false,
                        'backgroundColor'  => null,
                        'createdAt'        => null,
                        'updatedAt'        => null,
                        'parentEventId'    => null,
                        'invitationStatus' => null,
                        'childEvents'      => [],
                        'invitedUsers'     => [],
                        'editable'         => true,
                        'removable'        => true,
                        'notifiable'       => false
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider getCalendarEventProvider
     */
    public function testGetCalendarEvent($event, $calendarId, $expected)
    {
        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->will(
                $this->returnValueMap(
                    [
                        ['oro_calendar_event_update', null, true],
                        ['oro_calendar_event_delete', null, true],
                    ]
                )
            );

        $this->reminderManager->expects($this->once())
            ->method('applyReminders')
            ->with([$expected], 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        $result = $this->normalizer->getCalendarEvent(
            $this->buildCalendarEvent($event),
            $calendarId
        );
        $this->assertEquals($expected, $result);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)

     * @return array
     */
    public function getCalendarEventProvider()
    {
        $startDate = new \DateTime();
        $endDate   = $startDate->add(new \DateInterval('PT1H'));

        return [
            'calendar not specified' => [
                'event'      => [
                    'calendar' => 123,
                    'id'       => 1,
                    'title'    => 'test',
                    'start'    => $startDate,
                    'end'      => $endDate
                ],
                'calendarId' => null,
                'expected'   => [
                    'calendar'         => 123,
                    'id'               => 1,
                    'title'            => 'test',
                    'description'      => null,
                    'start'            => $startDate->format('c'),
                    'end'              => $endDate->format('c'),
                    'allDay'           => false,
                    'backgroundColor'  => null,
                    'createdAt'        => null,
                    'updatedAt'        => null,
                    'parentEventId'    => null,
                    'invitationStatus' => null,
                    'childEvents'      => [],
                    'invitedUsers'     => [],
                    'editable'         => true,
                    'removable'        => true,
                    'notifiable'       => false
                ]
            ],
            'own calendar'           => [
                'event'      => [
                    'calendar' => 123,
                    'id'       => 1,
                    'title'    => 'test',
                    'start'    => $startDate,
                    'end'      => $endDate
                ],
                'calendarId' => 123,
                'expected'   => [
                    'calendar'         => 123,
                    'id'               => 1,
                    'title'            => 'test',
                    'description'      => null,
                    'start'            => $startDate->format('c'),
                    'end'              => $endDate->format('c'),
                    'allDay'           => false,
                    'backgroundColor'  => null,
                    'createdAt'        => null,
                    'updatedAt'        => null,
                    'parentEventId'    => null,
                    'invitationStatus' => null,
                    'childEvents'      => [],
                    'invitedUsers'     => [],
                    'editable'         => true,
                    'removable'        => true,
                    'notifiable'       => false
                ]
            ],
            'another calendar'       => [
                'event'      => [
                    'calendar' => 123,
                    'id'       => 1,
                    'title'    => 'test',
                    'start'    => $startDate,
                    'end'      => $endDate
                ],
                'calendarId' => 456,
                'expected'   => [
                    'calendar'         => 123,
                    'id'               => 1,
                    'title'            => 'test',
                    'description'      => null,
                    'start'            => $startDate->format('c'),
                    'end'              => $endDate->format('c'),
                    'allDay'           => false,
                    'backgroundColor'  => null,
                    'createdAt'        => null,
                    'updatedAt'        => null,
                    'parentEventId'    => null,
                    'invitationStatus' => null,
                    'childEvents'      => [],
                    'invitedUsers'     => [],
                    'editable'         => false,
                    'removable'        => false,
                    'notifiable'       => false
                ]
            ],
        ];
    }

    /**
     * @param array $eventData
     *
     * @return array
     */
    protected function buildCalendarEvents(array $eventData)
    {
        $events = [];
        foreach ($eventData as $data) {
            $events[] = $this->buildCalendarEvent($data);
        }

        return $events;
    }

    /**
     * @param array $data
     *
     * @return CalendarEvent
     */
    protected function buildCalendarEvent(array $data)
    {
        $event = new CalendarEvent();
        if (!empty($data['id'])) {
            $reflection = new \ReflectionProperty(get_class($event), 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($event, $data['id']);
        }
        if (!empty($data['title'])) {
            $event->setTitle($data['title']);
        }
        if (!empty($data['start'])) {
            $event->setStart($data['start']);
        }
        if (!empty($data['end'])) {
            $event->setEnd($data['end']);
        }
        if (!empty($data['calendar'])) {
            $calendar = new Calendar();
            $event->setCalendar($calendar);
            $reflection = new \ReflectionProperty(get_class($calendar), 'id');
            $reflection->setAccessible(true);
            $reflection->setValue($calendar, $data['calendar']);
        }

        return $event;
    }
}
