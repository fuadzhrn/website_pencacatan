import assert from 'node:assert/strict';
import test from 'node:test';

import { preventDoubleSubmit } from './app.js';

const createForm = (method = 'post') => {
    const button = { disabled: false, dataset: {} };

    return {
        button,
        form: {
            tagName: 'FORM',
            method,
            dataset: {},
            attributes: {},
            setAttribute(name, value) {
                this.attributes[name] = value;
            },
            querySelectorAll: () => [button],
        },
    };
};

const createRoot = () => {
    let submitListener = null;

    return {
        addEventListener: (type, listener) => {
            if (type === 'submit') {
                submitListener = listener;
            }
        },
        submit: (form, { cancelled = false } = {}) => {
            const event = {
                target: form,
                defaultPrevented: cancelled,
                preventDefault() {
                    this.defaultPrevented = true;
                },
            };
            submitListener(event);

            return event;
        },
    };
};

const flushTimers = () => new Promise((resolve) => setTimeout(resolve, 0));

test('preventDoubleSubmit memblokir submit kedua dan mengunci tombol setelah submit pertama', async () => {
    const root = createRoot();
    const { form, button } = createForm();
    preventDoubleSubmit(root);

    assert.equal(root.submit(form).defaultPrevented, false);
    assert.equal(button.disabled, false, 'tombol tetap aktif selama event submit agar nilainya ikut terkirim');

    await flushTimers();

    assert.equal(button.disabled, true);
    assert.equal(form.attributes['aria-busy'], 'true');
    assert.equal(root.submit(form).defaultPrevented, true);
});

test('preventDoubleSubmit mengabaikan form GET dan submit yang dibatalkan konfirmasi', async () => {
    const root = createRoot();
    const getForm = createForm('get');
    const cancelledForm = createForm();
    preventDoubleSubmit(root);

    root.submit(getForm.form);
    root.submit(cancelledForm.form, { cancelled: true });
    await flushTimers();

    assert.equal(getForm.button.disabled, false);
    assert.equal(cancelledForm.button.disabled, false);
    assert.equal(cancelledForm.form.dataset.submitting, undefined);
    assert.equal(root.submit(cancelledForm.form).defaultPrevented, false, 'form tetap bisa dikirim setelah konfirmasi dibatalkan');
});
