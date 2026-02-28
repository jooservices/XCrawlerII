import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import process from 'node:process';
import { describe, it } from 'vitest';

const seoHeadPath = resolve(process.cwd(), 'Modules/Core/resources/js/components/shared/SeoHead.vue');
const loginPagePath = resolve(process.cwd(), 'Modules/Core/resources/js/pages/auth/LoginPage.vue');
const seoHeadSource = readFileSync(seoHeadPath, 'utf8');
const loginPageSource = readFileSync(loginPagePath, 'utf8');

describe('SEO contract', () => {
    it('SeoHead provides required meta, canonical, og, and twitter tags', () => {
        assert.match(seoHeadSource, /meta name="description"/);
        assert.match(seoHeadSource, /meta name="robots"/);
        assert.match(seoHeadSource, /link rel="canonical"/);
        assert.match(seoHeadSource, /meta property="og:title"/);
        assert.match(seoHeadSource, /meta name="twitter:card"/);
    });

    it('login page is explicitly non-indexable', () => {
        assert.match(loginPageSource, /indexable:\s*false/);
        assert.match(loginPageSource, /noindex,nofollow/);
    });
});
