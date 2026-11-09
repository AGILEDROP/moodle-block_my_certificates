// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later.

const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

for (const file of ['amd/src/pdf_preview.js', 'amd/build/pdf_preview.min.js']) {
    test(`${file}: a failed preview does not prevent the next PDF rendering`, async() => {
        const requested = [];
        const rendered = [];
        const failed = {dataset: {src: 'denied.pdf'}};
        const valid = {
            dataset: {src: 'valid.pdf'},
            getContext: () => ({}),
            classList: {add: name => rendered.push(name)},
        };
        let api;
        const page = {
            getViewport: ({scale}) => ({width: 100 * scale, height: 70 * scale}),
            render: () => ({promise: Promise.resolve()}),
        };
        vm.runInNewContext(fs.readFileSync(path.join(__dirname, '..', file), 'utf8'), {
            define: (name, deps, factory) => { api = factory(); },
            document: {querySelectorAll: () => [failed, valid]},
            window: {pdfjsLib: {
                GlobalWorkerOptions: {},
                getDocument: url => {
                    requested.push(url);
                    return {promise: url === 'denied.pdf'
                        ? Promise.reject(new Error('Access denied'))
                        : Promise.resolve({getPage: async() => page})};
                },
            }},
        });
        await api.init({workersrc: 'worker.js'});
        assert.deepEqual(requested, ['denied.pdf', 'valid.pdf']);
        assert.equal(failed.hidden, true);
        assert.equal(valid.width, 900);
        assert.deepEqual(rendered, ['pdf-loaded']);
    });
}
