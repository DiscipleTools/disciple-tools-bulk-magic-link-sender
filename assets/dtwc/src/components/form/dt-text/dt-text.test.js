import { html } from 'lit';
import { fixture, expect } from '@open-wc/testing';
import { sendKeys } from '@web/test-runner-commands';


import './dt-text.js';

describe('DT-Text', () => {
  it('Check id, name, label, and value attributes display correctly', async () => {
    const el = await fixture(html`<dt-text id='name' name='Name' label='Label Name' value='John Doe'></dt-text>`);

    expect(el.id).to.equal('name');
    expect(el.name).to.equal('Name');
    expect(el.label).to.equal('Label Name');
    expect(el.value).to.equal('John Doe');
  });

  it('update the text', async () => {
    const el = await fixture(html`<dt-text id='name' name='Name' label='Label Name' value='John Doe'></dt-text>`);
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

  it('input disabled', async () => {
    const el = await fixture(html`<dt-text disabled ></dt-text>`);
    expect(el.shadowRoot.querySelector('input').disabled).to.be.true
  });

  it('Check private field', async () => {
    const el = await fixture(html`<dt-text private></dt-text>`);
    const label = await fixture(el.shadowRoot.querySelector('dt-label'));

    expect(label.hasAttribute('private')).to.be.true;
  });

  it('passes the a11y audit', async () => {
    const el = await fixture(html`<dt-text id='name' name='Name' label='Label Name' value='John Doe'></dt-text>`);

    await expect(el).shadowDom.to.be.accessible();
  });
});
