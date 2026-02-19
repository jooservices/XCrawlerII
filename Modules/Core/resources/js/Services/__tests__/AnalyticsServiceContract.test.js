import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const rootDir = path.resolve(process.cwd());
const javJsRoot = path.join(rootDir, 'Modules', 'JAV', 'resources', 'js');
const movieShowPath = path.join(javJsRoot, 'Pages', 'Movies', 'Show.vue');
const legacyClientPath = path.join(javJsRoot, 'Services', 'analyticsClient.js');

function walkFiles(dir) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    const files = [];

    for (const entry of entries) {
        const fullPath = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            files.push(...walkFiles(fullPath));
            continue;
        }

        files.push(fullPath);
    }

    return files;
}

test('Movie show page imports shared Core analytics service', () => {
    const source = fs.readFileSync(movieShowPath, 'utf8');

    assert.match(source, /import analyticsService from '@core\/Services\/analyticsService';/);
    assert.match(source, /analyticsService\.track\('view', 'movie', props\.jav\?\.uuid\)/);
});

test('Movie show page mounts analytics call in protected onMounted flow', () => {
    const source = fs.readFileSync(movieShowPath, 'utf8');

    assert.match(source, /onMounted\(async \(\) => \{/);
    assert.match(source, /try \{\s*await analyticsService\.track\('view', 'movie', props\.jav\?\.uuid\);/s);
    assert.match(source, /catch \{\s*\/\/ swallow analytics errors to avoid breaking page UX/s);
});

test('Legacy JAV analytics client file is removed', () => {
    assert.equal(fs.existsSync(legacyClientPath), false);
});

test('JAV FE code has no hardcoded analytics endpoint calls', () => {
    const files = walkFiles(javJsRoot).filter((file) => file.endsWith('.js') || file.endsWith('.vue'));
    const offenders = [];

    for (const file of files) {
        const source = fs.readFileSync(file, 'utf8');
        if (source.includes('/api/v1/analytics/events') || source.includes("axios.post('/api/v1/analytics/events'")) {
            offenders.push(path.relative(rootDir, file));
        }
    }

    assert.deepEqual(offenders, []);
});

test('FE analytics payload shape matches BE IngestAnalyticsEventRequest (contract)', async () => {
    const { AnalyticsService } = await import('../analyticsService.js');
    let capturedPayload = null;
    const httpClient = {
        post: (_url, payload) => {
            capturedPayload = payload;
            return Promise.resolve({ data: {} });
        }
    };
    const service = new AnalyticsService({ httpClient, storage: null });

    await service.track('view', 'movie', 'contract-entity-id', {
        userId: 1,
        eventId: 'evt-contract',
        occurredAt: '2026-02-19T00:00:00.000Z'
    });

    assert.ok(capturedPayload, 'payload should be sent');
    assert.strictEqual(capturedPayload.event_id, 'evt-contract');
    assert.strictEqual(capturedPayload.domain, 'jav');
    assert.strictEqual(capturedPayload.entity_type, 'movie');
    assert.strictEqual(capturedPayload.entity_id, 'contract-entity-id');
    assert.strictEqual(capturedPayload.action, 'view');
    assert.strictEqual(capturedPayload.value, 1);
    assert.strictEqual(capturedPayload.occurred_at, '2026-02-19T00:00:00.000Z');
    assert.strictEqual(capturedPayload.user_id, 1);
    const requiredKeys = ['event_id', 'domain', 'entity_type', 'entity_id', 'action', 'value', 'occurred_at'];
    for (const key of requiredKeys) {
        assert.ok(key in capturedPayload, `payload must include ${key}`);
    }
});
