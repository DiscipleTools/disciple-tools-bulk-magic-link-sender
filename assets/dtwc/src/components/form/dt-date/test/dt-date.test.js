import { html } from 'lit';
import { fixture, expect } from '@open-wc/testing';
import { sendKeys } from '@web/test-runner-commands';


import '../dt-date.js';

describe('DT-Date', () => {
  it('Check id, name, label, and value attributes display correctly', async () => {
    const el = await fixture(html`<dt-date id='name' name='Field Name' label='Label Name' value='' timestamp='1577836800000' ></dt-date>`);

    expect(el.id).to.equal('name');
    expect(el.name).to.equal('Field Name');
    expect(el.label).to.equal('Label Name');
    expect(el.value).to.equal('2020-01-01');
    expect(el.timestamp).to.equal(1577836800000);
  });

  it('update the text', async () => {
    const el = await fixture(html`<dt-date value='2020-01-01'></dt-date>`);

    el.shadowRoot.querySelector('input').value = '';
    el.shadowRoot.querySelector('input').focus();

    // The date input shows in the local and expects a MM-DD-YYYY format format but converts it to YYYY-MM-DD
    await sendKeys({
      type: '01-01-1999',
    });

    await sendKeys({
      press: 'Enter',
    })
    expect(el.value).to.equal('1999-01-01');
  });

  it('check PHP timestamp', async () => {
    const el = await fixture(html`<dt-date timestamp='1577836800'></dt-date>`);
    expect(el.shadowRoot.querySelector('input').value).to.equal('2020-01-01');
  });

  it('check JS timestamp', async () => {
    const el = await fixture(html`<dt-date timestamp='1577836800000'></dt-date>`);
    expect(el.shadowRoot.querySelector('input').value).to.equal('2020-01-01');
  });

  it('Check private field', async () => {
    const el = await fixture(html`<dt-date private></dt-date>`);
    const label = await fixture(el.shadowRoot.querySelector('dt-label'));

    expect(label.hasAttribute('private')).to.be.true;
  });

  it('passes the a11y audit', async () => {
    const el = await fixture(html`<dt-date id='name' name='Name' label='Label Name' value='John Doe' timestamp='1577836800000'></dt-date>`);

    await expect(el).shadowDom.to.be.accessible();
  });
});
