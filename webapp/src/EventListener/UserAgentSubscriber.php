<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class UserAgentSubscriber implements EventSubscriberInterface
{

    private $logger;
    private $mailer;
    private $container;

    public function __construct(LoggerInterface $logger, MailerInterface $mailer, ContainerInterface $container)
    {
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->container = $container;
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $this->logger->info('SUCESSS LOGIN ... SENDING EMAIL');
        $email = (new Email())
            ->from('info@agiswapi.com')
            ->to($this->container->getParameter('mailer_notification'))
            ->cc('jasson142@gmail.com')
            ->priority(Email::PRIORITY_HIGH)
            ->subject('New user register')
            ->text('One user has registered on the page')
            ->html('<p>がんばれ(Gambare) !!</p>');

        $this->mailer->send($email);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }
}