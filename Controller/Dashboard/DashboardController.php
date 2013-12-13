<?php

namespace Oro\Bundle\CalendarBundle\Controller\Dashboard;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class DashboardController extends Controller
{
    /**
     * @Route(
     *      "/calendar/{_format}",
     *      name="oro_calendar_dashboard_calendar",
     *      requirements={"_format"="html|json"},
     *      defaults={"_format" = "html"}
     * )
     * @Template("OroCalendarBundle:Dashboard:calendar.html.twig")
     */
    public function calendarAction()
    {
        /** @var SecurityFacade $securityFacade */
        $securityFacade = $this->get('oro_security.security_facade');
        /** @var CalendarDateTimeConfigProvider $calendarConfigProvider */
        $calendarConfigProvider = $this->get('oro_calendar.provider.calendar_config');

        $calendar    = $this->getDoctrine()->getManager()
            ->getRepository('OroCalendarBundle:Calendar')
            ->findByUser($this->getUser()->getId());
        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $startDate   = new \DateTime('now', new \DateTimeZone($this->get('oro_locale.settings')->getTimeZone()));
        $startDate->setTime(0, 0, 0);
        $endDate = clone $startDate;
        $endDate->add(new \DateInterval('P1D'));

        $result = array(
            'event_form' => $this->get('oro_calendar.calendar_event.form')->createView(),
            'entity'     => $calendar,
            'calendar'   => array(
                'selectable'     => $securityFacade->isGranted('oro_calendar_event_create'),
                'editable'       => $securityFacade->isGranted('oro_calendar_event_update'),
                'removable'      => $securityFacade->isGranted('oro_calendar_event_delete'),
                'timezoneOffset' => $calendarConfigProvider->getTimezoneOffset($currentDate)
            ),
            'startDate'  => $startDate,
            'endDate'    => $endDate,
        );

        return $result;
    }
}