import { html } from 'lit';
import { fixture, expect } from '@open-wc/testing';
import { sendKeys } from '@web/test-runner-commands';


import '../dt-textarea.js';

describe('dt-textareaarea', () => {
  it('Check id, name, label, and value attributes display correctly', async () => {
    const el = await fixture(html`<dt-textarea id='name' name='Name' label='Label Name' value='John Doe'></dt-textarea>`);

    expect(el.id).to.equal('name');
    expect(el.name).to.equal('Name');
    expect(el.label).to.equal('Label Name');
    expect(el.value).to.equal('John Doe');
  });

  it('update the text', async () => {
    const el = await fixture(html`<dt-textarea id='name' name='Name' label='Label Name' value='John Doe'></dt-textarea>`);
    el.shadowRoot.querySelector('textarea').value = '';
    el.shadowRoot.querySelector('textarea').focus();

    await sendKeys({
      type: 'test typing',
    });

    el.shadowRoot.querySelector('textarea').blur();

    expect(el.value).to.equal('test typing');
  });

  it('textarea disabled', async () => {
    const el = await fixture(html`<dt-textarea disabled ></dt-textarea>`);
    expect(el.shadowRoot.querySelector('textarea').disabled).to.be.true
  });

  it('Check private field', async () => {
    const el = await fixture(html`<dt-textarea private></dt-textarea>`);
    const label = await fixture(el.shadowRoot.querySelector('dt-label'));

    expect(label.hasAttribute('private')).to.be.true;
  });

  it('passes the a11y audit', async () => {
    const el = await fixture(html`<dt-textarea id='name' name='Name' label='Label Name' value='John Doe'></dt-textarea>`);

    await expect(el).shadowDom.to.be.accessible();
  });
});
