import { APIRequestContext } from '@playwright/test';
import { randomUUID } from 'crypto';

const ANALYTICS_ENDPOINT = '/api/v1/analytics/events';

export interface AnalyticsEventPayload {
    event_id: string;
    domain: string;
    entity_type: string;
    entity_id: string;
    action: string;
    value?: number;
    occurred_at: string;
}

/**
 * Build a valid analytics event payload with sensible defaults.
 * Override any field via the `overrides` parameter.
 */
export function buildValidPayload(overrides?: Partial<AnalyticsEventPayload>): AnalyticsEventPayload {
    return {
        event_id: randomUUID(),
        domain: 'jav',
        entity_type: 'movie',
        entity_id: randomUUID(), // placeholder — override with real movie UUID
        action: 'view',
        value: 1,
        occurred_at: new Date().toISOString(),
        ...overrides,
    };
}

/**
 * POST an analytics event and return the raw response.
 */
export async function postAnalyticsEvent(
    request: APIRequestContext,
    payload: AnalyticsEventPayload | Record<string, any>,
) {
    return request.post(ANALYTICS_ENDPOINT, {
        data: payload,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    });
}
