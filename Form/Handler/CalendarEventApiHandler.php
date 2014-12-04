<?php

namespace Oro\Bundle\CalendarBundle\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Email\EmailSendProcessor;

class CalendarEventApiHandler
{
    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /** @var EmailSendProcessor */
    protected $emailSendProcessor;

    /** @var CalendarEvent */
    protected $dirtyEntity;

    /**
     * @param FormInterface      $form
     * @param Request            $request
     * @param ObjectManager      $manager
     * @param EmailSendProcessor $emailSendProcessor
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $manager,
        EmailSendProcessor $emailSendProcessor
    ) {
        $this->form    = $form;
        $this->request = $request;
        $this->manager = $manager;
        $this->emailSendProcessor  = $emailSendProcessor;
    }

    /**
     * Process form
     *
     * @param  CalendarEvent $entity
     * @return bool  True on successful processing, false otherwise
     */
    public function process(CalendarEvent $entity)
    {
        $this->form->setData($entity);

        if (in_array($this->request->getMethod(), array('POST', 'PUT'))) {
            $dirtyEntity = clone $entity;
            $originalChildren = new ArrayCollection();
            foreach ($entity->getChildEvents() as $childEvent) {
                $originalChildren->add($childEvent);
            }

            $this->form->submit($this->request);

            if ($this->form->isValid()) {
                $this->onSuccess($entity, $dirtyEntity, $originalChildren);
                return true;
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     *
     * @param CalendarEvent   $entity
     * @param CalendarEvent   $dirtyEntity
     * @param ArrayCollection $originalChildren
     */
    protected function onSuccess(CalendarEvent $entity, CalendarEvent $dirtyEntity, ArrayCollection $originalChildren)
    {
        $new = $entity->getId() ? false : true;
        $this->manager->persist($entity);

        if ($new) {
            $this->emailSendProcessor->sendInviteNotification($entity);
        } else {
            $this->emailSendProcessor->sendUpdateParentEventNotification($entity, $dirtyEntity, $originalChildren);
        }

        $this->manager->flush();
    }
}
