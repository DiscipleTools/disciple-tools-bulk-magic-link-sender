import { html } from 'lit';
import { fixture, expect } from '@open-wc/testing';
import { sendKeys } from '@web/test-runner-commands';


import '../dt-text.js';

describe('DT-Text', () => {
  it('has a default name of Name and value of John Doe', async () => {
    const el = await fixture(html`<dt-text></dt-text>`);

    expect(el.name).to.equal('Name');
    expect(el.value).to.equal('John Doe');
  });

  it('update the text', async () => {
    const el = await fixture(html`<dt-text></dt-text>`);
    el.shadowRoot.querySelector('input').value = '';
    el.shadowRoot.querySelector('input').focus();

    await sendKeys({
      type: 'test typing',
    });

    await sendKeys({
      press: 'Enter',
    })

    expect(el.value).to.equal('test typing');
  });

  it('can override the Name via attribute', async () => {
    const el = await fixture(html`<dt-text name="attribute title"></dt-text>`);

    expect(el.name).to.equal('attribute title');
  });

  it('passes the a11y audit', async () => {
    const el = await fixture(html`<dt-text></dt-text>`);

    await expect(el).shadowDom.to.be.accessible();
  });
});
