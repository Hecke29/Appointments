<?php

namespace OCA\Appointments\AppInfo;

use OCA\Appointments\Backend\DavListener;
use OCA\Appointments\Backend\RemoveScriptsMiddleware;
use OCA\Appointments\CalDAV\IMipPlugin;
use OCA\DAV\Events\CalendarObjectMovedToTrashEvent;
use OCA\DAV\Events\CalendarObjectUpdatedEvent;
use OCA\DAV\Events\SubscriptionDeletedEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\SabrePluginEvent;

class Application extends App implements IBootstrap
{

    const APP_ID = 'appointments';

    public function __construct()
    {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void
    {
        $context->registerEventListener(CalendarObjectUpdatedEvent::class, DavListener::class);
        $context->registerEventListener(CalendarObjectMovedToTrashEvent::class, DavListener::class);
        $context->registerEventListener(SubscriptionDeletedEvent::class, DavListener::class);

        $context->registerService('ApptRemoveScriptsMiddleware', function ($c) {
            return new RemoveScriptsMiddleware();
        });
        $context->registerMiddleware('ApptRemoveScriptsMiddleware');
    }

    public function boot(IBootContext $context): void
    {
        $appContainer = $context->getAppContainer();
        $serverContainer = $context->getServerContainer();

        /** @var IEventDispatcher $eventDispatcher */
        $eventDispatcher = $serverContainer->get(IEventDispatcher::class);
        // prevent default iMip plugin from sending emails for our appointments
        $eventDispatcher->addListener('OCA\DAV\Connector\Sabre::addPlugin', static function (SabrePluginEvent $event) use ($appContainer) {
            if ($event->getServer() === null) {
                return;
            }
            $event->getServer()->addPlugin($appContainer->get(IMipPlugin::class));
        });
    }
}