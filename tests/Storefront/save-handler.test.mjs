import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { test } from 'node:test';

const source = await readFile(new URL('../../src/Resources/app/storefront/src/js/observation-wizard/save-handler.js', import.meta.url), 'utf8');
const { default: SaveHandler } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);

function fixture() {
    const nodes = new Map();
    const el = {
        querySelector(selector) {
            if (!nodes.has(selector)) {
                nodes.set(selector, {
                    hidden: true, value: 0, textContent: '', listeners: {}, focused: false,
                    addEventListener(event, listener) { this.listeners[event] = listener; },
                    focus() { this.focused = true; },
                });
            }
            return nodes.get(selector);
        },
    };
    const wizard = {
        el, leaveGuardDisabled: false,
        strings: { photoUploadProgress: '%done% of %total% photos uploaded' },
    };
    const handler = new SaveHandler(wizard);
    const photo = (id) => ({ itemId: 'a'.repeat(32), photoId: id.repeat(32), file: new Blob(['image'], { type: 'image/jpeg' }), done: false });
    return { handler, wizard, el, photo };
}

test('slow responses keep progress and redirect blocked; transient failures retry with the same id', async () => {
    const { handler, wizard, el, photo } = fixture();
    handler.photos = [photo('1'), photo('2')];
    const requests = [];
    const originalFetch = globalThis.fetch;
    const originalWindow = globalThis.window;
    globalThis.window = { location: { href: '/observation' } };
    handler.init();
    let releaseFirst;
    let releaseSecond;
    globalThis.fetch = async (_url, options) => {
        const id = options.body.get('photoId');
        requests.push(id);
        if (requests.length === 1) {
            await new Promise(resolve => { releaseFirst = resolve; });
            return { ok: false, status: 503, json: async () => ({}) };
        }
        if (requests.length === 3) {
            await new Promise(resolve => { releaseSecond = resolve; });
        }
        return { ok: true, status: 200, json: async () => ({ success: true }) };
    };
    try {
        const pending = handler._uploadPending();
        assert.equal(el.querySelector('[data-photo-upload-progress]').textContent, '0 of 2 photos uploaded');
        assert.equal(el.querySelector('[data-finish-redirect]').hidden, true);
        assert.equal(wizard.leaveGuardDisabled, false);
        el.querySelector('[data-finish-redirect]').listeners.click();
        assert.equal(window.location.href, '/observation');
        await new Promise(resolve => setTimeout(resolve, 20));
        assert.equal(requests.length, 1);
        releaseFirst();
        while (!releaseSecond) await new Promise(resolve => setTimeout(resolve, 10));
        assert.equal(el.querySelector('[data-photo-upload-progress]').textContent, '1 of 2 photos uploaded');
        assert.equal(el.querySelector('[data-finish-redirect]').hidden, true);
        releaseSecond();
        await pending;
        assert.deepEqual(requests, ['1'.repeat(32), '1'.repeat(32), '2'.repeat(32)]);
        assert.equal(el.querySelector('[data-photo-upload-progress]').textContent, '2 of 2 photos uploaded');
        assert.equal(el.querySelector('[data-finish-redirect]').hidden, false);
        assert.equal(wizard.leaveGuardDisabled, true);
        el.querySelector('[data-finish-redirect]').listeners.click();
        assert.equal(window.location.href, '/collection/observations');
    } finally {
        globalThis.fetch = originalFetch;
        globalThis.window = originalWindow;
    }
});

test('failed photo remains queued for a manual retry', async () => {
    const { handler, wizard, el, photo } = fixture();
    handler.photos = [photo('3')];
    const originalFetch = globalThis.fetch;
    let calls = 0;
    globalThis.fetch = async () => {
        calls++;
        return calls === 1
            ? { ok: false, status: 400, json: async () => ({}) }
            : { ok: true, status: 200, json: async () => ({ success: true }) };
    };
    try {
        await handler._uploadPending();
        assert.equal(el.querySelector('[data-photo-upload-warning]').hidden, false);
        assert.equal(el.querySelector('[data-photo-retry]').hidden, false);
        assert.equal(el.querySelector('[data-finish-redirect]').hidden, true);
        assert.equal(wizard.leaveGuardDisabled, false);
        await handler._uploadPending();
        assert.equal(calls, 2);
        assert.equal(el.querySelector('[data-photo-upload-warning]').hidden, true);
        assert.equal(wizard.leaveGuardDisabled, true);
    } finally {
        globalThis.fetch = originalFetch;
    }
});

test('upload continues behind cancellation confirmation and closes it on completion', async () => {
    const { handler, wizard, el, photo } = fixture();
    handler.photos = [photo('6')];
    const originalFetch = globalThis.fetch;
    let releaseUpload;
    globalThis.fetch = async () => {
        await new Promise(resolve => { releaseUpload = resolve; });
        return { ok: true, status: 200, json: async () => ({ success: true }) };
    };
    try {
        handler.init();
        const pending = handler._uploadPending();
        el.querySelector('[data-photo-cancel]').listeners.click();
        assert.equal(el.querySelector('[data-photo-cancel-confirmation]').hidden, false);
        assert.equal(el.querySelector('[data-photo-cancel-stay]').focused, true);
        assert.equal(handler.cancelled, false);
        releaseUpload();
        await pending;
        assert.equal(el.querySelector('[data-photo-cancel-confirmation]').hidden, true);
        assert.equal(el.querySelector('[data-finish-redirect]').focused, true);
        assert.equal(wizard.leaveGuardDisabled, true);
    } finally {
        globalThis.fetch = originalFetch;
    }
});

test('continuing upload dismisses the confirmation without cancelling', () => {
    const { handler, el, photo } = fixture();
    handler.photos = [photo('7')];
    handler.init();
    handler.requestCancel();
    el.querySelector('[data-photo-cancel-stay]').listeners.click();
    assert.equal(el.querySelector('[data-photo-cancel-confirmation]').hidden, true);
    assert.equal(el.querySelector('[data-photo-cancel]').focused, true);
    assert.equal(handler.cancelled, false);
    assert.equal(handler.hasPendingPhotos(), true);
});

test('cancel requires confirmation, aborts the active request and releases the redirect', async () => {
    const { handler, wizard, el, photo } = fixture();
    handler.photos = [photo('4'), photo('5')];
    const originalFetch = globalThis.fetch;
    let calls = 0;
    globalThis.fetch = (_url, options) => {
        calls++;
        return new Promise((_resolve, reject) => options.signal.addEventListener('abort', () => reject(new DOMException('Aborted', 'AbortError'))));
    };
    try {
        const pending = handler._uploadPending();
        handler.requestCancel();
        assert.equal(el.querySelector('[data-photo-cancel-confirmation]').hidden, false);
        assert.equal(wizard.leaveGuardDisabled, false);
        handler._confirmCancel();
        await pending;
        assert.equal(calls, 1);
        assert.equal(el.querySelector('[data-photo-cancelled]').hidden, false);
        assert.equal(el.querySelector('[data-finish-redirect]').hidden, false);
        assert.equal(wizard.leaveGuardDisabled, true);
    } finally {
        globalThis.fetch = originalFetch;
    }
});
