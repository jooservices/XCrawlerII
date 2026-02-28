<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\Events;

/**
 * Contract for event subscribers. Feature modules implement this and tag instances as 'core.event_subscribers'.
 * Core resolves tagged subscribers and registers them; Core does NOT scan or depend on feature classes.
 *
 * subscriptions() returns a map: event_name (string) => handler method name(s).
 * Example: ['order.created' => 'onOrderCreated', 'order.updated' => ['onOrderUpdated', 'logAudit']]
 */
interface EventSubscriberInterface
{
    /**
     * Event name to handler method(s). One method name or list of method names.
     *
     * @return array<string, string|list<string>>
     */
    public static function subscriptions(): array;
}
