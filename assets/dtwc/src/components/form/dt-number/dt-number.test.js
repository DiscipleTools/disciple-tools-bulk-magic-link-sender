import { html } from 'lit';
import { fixture, expect } from '@open-wc/testing';
import { sendKeys } from '@web/test-runner-commands';


import './dt-number.js';

describe('DT-Number', () => {
  it('should display id, name, label, and value attributes correctly', async () => {
    const el = await fixture(html`<dt-number id='name' name='Name' label='Label Name' value='10'></dt-number>`);

    expect(el.id).to.equal('name');
    expect(el.name).to.equal('Name');
    expect(el.label).to.equal('Label Name');
    expect(el.value).to.equal('10');
  });

  it('updates the text', async () => {
    const el = await fixture(html`<dt-number id='name' name='Name' label='Label Name' value='John Doe'></dt-number>`);
    el.shadowRoot.querySelector('input').value = '';
    el.shadowRoot.querySelector('input').focus();

    await sendKeys({
      type: '10',
    });

    await sendKeys({
      press: 'Enter',
    })

    expect(el.value).to.equal('10');
  });

  it('should check min value', async () => {
    const el = await fixture(html`<dt-number min=3></dt-number>`);
    el.shadowRoot.querySelector('input').value = '';
    el.shadowRoot.querySelector('input').focus();

    await sendKeys({
      type: '2',
    });

    await sendKeys({
      press: 'Enter',
    })

    expect(el.value).to.be.undefined;

    await sendKeys({
      type: '3',
    });

    await sendKeys({
      press: 'Enter',
    })

    expect(el.value).to.equal('3');

  });

  it('should check max value', async () => {
    const el = await fixture(html`<dt-number max=10></dt-number>`);
    el.shadowRoot.querySelector('input').value = '';
    el.shadowRoot.querySelector('input').focus();

    await sendKeys({
      type: '20',
    });

    await sendKeys({
      press: 'Enter',
    })

    expect(el.value).to.be.undefined;

    await sendKeys({
      type: '10',
    });

    await sendKeys({
      press: 'Enter',
    })

    expect(el.value).to.equal('10');
  });

  it('should check min/max value', async () => {
    const el = await fixture(html`<dt-number min=2 max=10></dt-number>`);
    el.shadowRoot.querySelector('input').value = '';
    el.shadowRoot.querySelector('input').focus();

    await sendKeys({
      type: '1',
    });

    await sendKeys({
      press: 'Enter',
    })

    expect(el.value).to.be.undefined;
    await sendKeys({
      type: '11',
    });

    await sendKeys({
      press: 'Enter',
    })

    expect(el.value).to.be.undefined;

    await sendKeys({
      type: '5',
    });

    await sendKeys({
      press: 'Enter',
    })

    expect(el.value).to.equal('5');
  });

  it('sets min/max attributes on input', async () => {
    const el = await fixture(html`<dt-number min="2" max="23"></dt-number>`);
    const input = el.shadowRoot.querySelector('input');

    expect(input).to.have.attribute('min', '2');
    expect(input).to.have.attribute('max', '23');
  });
  it('ignores empty min/max attributes', async () => {
    const el = await fixture(html`<dt-number ></dt-number>`);
    const input = el.shadowRoot.querySelector('input');

    expect(input).to.not.have.attribute('min');
    expect(input).to.not.have.attribute('max');
  })

  it('disables input', async () => {
    const el = await fixture(html`<dt-number disabled ></dt-number>`);
    expect(el.shadowRoot.querySelector('input').disabled).to.be.true
  });

  it('sets private field', async () => {
    const el = await fixture(html`<dt-number private></dt-number>`);
    const label = await fixture(el.shadowRoot.querySelector('dt-label'));

    expect(label.hasAttribute('private')).to.be.true;
  });

  it('passes the a11y audit', async () => {
    const el = await fixture(html`<dt-number id='name' name='Name' label='Label Name' value='John Doe'></dt-number>`);

    await expect(el).shadowDom.to.be.accessible();
  });
});
