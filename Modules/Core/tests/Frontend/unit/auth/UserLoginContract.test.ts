import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import process from 'node:process';
import { describe, it } from 'vitest';

const userLoginPath = resolve(process.cwd(), 'Modules/Core/resources/js/components/auth/UserLogin.vue');
const composablePath = resolve(process.cwd(), 'Modules/Core/resources/js/composables/auth/useLoginForm.js');
const userLoginSfc = readFileSync(userLoginPath, 'utf8');
const composableSource = readFileSync(composablePath, 'utf8');

describe('Core Auth FE contract', () => {
    it('composes reusable identifier/password sub-components', () => {
        assert.match(userLoginSfc, /import LoginIdentifierField from '.\/LoginIdentifierField\.vue'/);
        assert.match(userLoginSfc, /import LoginPasswordField from '.\/LoginPasswordField\.vue'/);
        assert.match(userLoginSfc, /<LoginIdentifierField/);
        assert.match(userLoginSfc, /<LoginPasswordField/);
    });

    it('enforces required FE validation and posts to v1 auth action route', () => {
        assert.match(composableSource, /Username or email is required\./);
        assert.match(composableSource, /Password is required\./);
        assert.match(composableSource, /route\('v1\.action\.auth\.login'/);
    });

    it('includes email-shape hint logic and server-error merge', () => {
        assert.match(composableSource, /Email format looks incomplete\./);
        assert.match(composableSource, /form\.errors\.login/);
        assert.match(composableSource, /form\.errors\.password/);
    });
});
