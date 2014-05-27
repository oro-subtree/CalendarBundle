<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarConnection;
use Oro\Bundle\CalendarBundle\EventListener\EntityListener;
use Oro\Bundle\UserBundle\Entity\User;

class EntitySubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $uow;

    /** @var EntityListener */
    protected $listener;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->uow = $this->getMockBuilder('\Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));

        $this->listener = new EntityListener();
    }

    public function testOnFlush()
    {
        $args = new OnFlushEventArgs($this->em);

        $user = new User();
        $newCalendar = new Calendar();
        $newCalendar->setOwner($user);
        $newConnection = new CalendarConnection($newCalendar);
        $newCalendar->addConnection($newConnection);
        $calendarMetadata = new ClassMetadata(get_class($newCalendar));
        $connectionMetadata = new ClassMetadata(get_class($newConnection));

        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($this->uow));
        $this->uow->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->will($this->returnValue(array($user)));
        $this->em->expects($this->at(1))
            ->method('persist')
            ->with($this->equalTo($newCalendar));
        $this->em->expects($this->at(2))
            ->method('persist')
            ->with($this->equalTo($newConnection));
        $this->em->expects($this->at(3))
            ->method('getClassMetadata')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($calendarMetadata));
        $this->em->expects($this->at(4))
            ->method('getClassMetadata')
            ->with('OroCalendarBundle:CalendarConnection')
            ->will($this->returnValue($connectionMetadata));
        $this->uow->expects($this->at(1))
            ->method('computeChangeSet')
            ->with($calendarMetadata, $newCalendar);
        $this->uow->expects($this->at(2))
            ->method('computeChangeSet')
            ->with($connectionMetadata, $newConnection);

        $this->listener->onFlush($args);
    }
}
