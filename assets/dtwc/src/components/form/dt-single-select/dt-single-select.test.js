import { html } from 'lit';
import { fixture, expect, oneEvent } from '@open-wc/testing';

import './dt-single-select.js';

const options = [{
  id: 'opt1',
  label: 'Option 1',
}, {
  id: 'opt2',
  label: 'Second Option',
}, {
  id: 'opt3',
  label: 'Option Three',
}];

describe('dt-single-select', () => {

  it('sets placeholder', async () => {
    const el = await fixture(html`<dt-single-select placeholder="Custom Placeholder"></dt-single-select>`);
    const select = el.shadowRoot.querySelector('select');

    expect(select).to.have.descendant('option[value=""]').text('Custom Placeholder');

  });

  it('sets options', async () => {
    const el = await fixture(html`<dt-single-select options="${JSON.stringify(options)}"></dt-single-select>`);
    const select = el.shadowRoot.querySelector('select');

    expect(select).to.contain('option[value=opt1]');
    expect(select).to.contain('option[value=opt2]');
    expect(select).to.contain('option[value=opt3]');
  });

  it('sets selection from attribute', async () => {
    const el = await fixture(html`<dt-single-select value="opt1" options="${JSON.stringify(options)}"></dt-single-select>`);
    const select = el.shadowRoot.querySelector('select');

    expect(select).to.have.value('opt1');
  });

  it('updates value attribute', async () => {
    const el = await fixture(html`<dt-single-select value="" options="${JSON.stringify(options)}"></dt-single-select>`);
    const select = el.shadowRoot.querySelector('select');

    select.value = "opt1";
    const event = document.createEvent('HTMLEvents');
    event.initEvent('change', true, false);
    select.dispatchEvent(event);

    expect(select.value).to.equal('opt1');
    expect(el.value).to.equal('opt1');
  });

  it('triggers change event', async () => {
    const el = await fixture(html`<dt-single-select name="custom-name" options="${JSON.stringify(options)}"></dt-single-select>`);
    const select = el.shadowRoot.querySelector('select');

    select.value = "opt1";
    const event = document.createEvent('HTMLEvents');
    event.initEvent('change', true, false);
    setTimeout(() => select.dispatchEvent(event));

    const { detail } = await oneEvent(el, 'change');

    expect(detail.field).to.equal('custom-name');
    expect(detail.newValue).to.eql('opt1');
    expect(detail.oldValue).to.eql(undefined);
  });

  it('passes the a11y audit', async () => {
    const el = await fixture(html`<dt-single-select name="custom-name" options="${JSON.stringify(options)}"></dt-single-select>`);

    await expect(el).shadowDom.to.be.accessible();
  });

  it('sets color from options', async () => {
    const colorOptions = options.map((o, idx) => ({
      ...o,
      color: `#${idx}${idx}${idx}`,
    }));
    const el = await fixture(html`<dt-single-select value="opt1" options="${JSON.stringify(colorOptions)}"></dt-single-select>`);
    const select = el.shadowRoot.querySelector('select');


    expect(select).to.have.style('backgroundColor', 'rgb(0, 0, 0)');
  });
});
