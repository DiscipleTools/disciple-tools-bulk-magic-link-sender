import { html } from 'lit';
import { fixture, expect, oneEvent } from '@open-wc/testing';
import { sendKeys } from '@web/test-runner-commands';

import './dt-multi-select.js';

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
async function wait(ms) {
  return new Promise(r => setTimeout(r, ms));
}

async function clickOption(el, id) {
  const input = el.shadowRoot.querySelector('input');
  const optionBtn = el.shadowRoot.querySelector(`.option-list button[value=${id}]`);

  input.focus();
  await wait( 50); // wait for UI update

  optionBtn.click();
  await wait(100);

}

describe('dt-multi-select', () => {

  it('sets placeholder', async () => {
    const el = await fixture(html`<dt-multi-select placeholder="Custom Placeholder"></dt-multi-select>`);
    const input = el.shadowRoot.querySelector(('input'));

    expect(input.placeholder).to.equal('Custom Placeholder');
  });

  it('sets options', async () => {
    const el = await fixture(html`<dt-multi-select options="${JSON.stringify(options)}"></dt-multi-select>`);
    const optionList = el.shadowRoot.querySelector('.option-list');

    expect(optionList.children).to.have.lengthOf(3);
    expect(optionList).to.contain('button[value=opt1]');
    expect(optionList).to.contain('button[value=opt2]');
    expect(optionList).to.contain('button[value=opt3]');

    expect(optionList).not.to.be.displayed;
  });

  it('sets selection from attribute', async () => {
    const el = await fixture(html`<dt-multi-select value="${JSON.stringify(['opt1','opt2'])}" options="${JSON.stringify(options)}"></dt-multi-select>`);
    const container = el.shadowRoot.querySelector('.field-container');

    expect(container).to.contain('button[data-value=opt1]');
    expect(container).to.contain('button[data-value=opt2]');
    expect(container).not.to.contain('button[data-value=opt3]');
  });

  it('opens option list on input focus', async () => {
    const el = await fixture(html`<dt-multi-select options="${JSON.stringify(options)}"></dt-multi-select>`);
    const input = el.shadowRoot.querySelector('input');
    const optionList = el.shadowRoot.querySelector('.option-list');

    expect(optionList).not.to.be.displayed;

    input.focus();
    await wait( 50); // wait for UI update

    expect(optionList).to.be.displayed;
  });

  it('selects option via mouse', async () => {
    const el = await fixture(html`<dt-multi-select options="${JSON.stringify(options)}"></dt-multi-select>`);
    const input = el.shadowRoot.querySelector('input');
    const optionBtn = el.shadowRoot.querySelector('.option-list button[value=opt1]');

    input.focus();
    await wait( 50); // wait for UI update

    optionBtn.click();
    await wait(100);

    const container = el.shadowRoot.querySelector('.field-container');
    expect(container).to.contain('button[data-value=opt1]');
  });

  it('selects option via keyboard', async () => {
    const el = await fixture(html`<dt-multi-select options="${JSON.stringify(options)}"></dt-multi-select>`);
    const input = el.shadowRoot.querySelector('input');
    input.focus();

    sendKeys({
      press: 'ArrowDown',
    });
    sendKeys({
      press: 'Enter',
    });
    await wait(100);

    const container = el.shadowRoot.querySelector('.field-container');
    expect(container).to.contain('button[data-value=opt1]');
  });

  it('updates value attribute', async () => {
    const el = await fixture(html`<dt-multi-select options="${JSON.stringify(options)}"></dt-multi-select>`);

    await clickOption(el, 'opt1');

    expect(el).to.have.attr('value', JSON.stringify(['opt1']));
  });

  it('triggers change event', async () => {
    const el = await fixture(html`<dt-multi-select name="custom-name" value="${JSON.stringify(['opt2'])}" options="${JSON.stringify(options)}"></dt-multi-select>`);

    setTimeout(() => clickOption(el, 'opt1'));

    const { detail } = await oneEvent(el, 'change');

    expect(detail.field).to.equal('custom-name');
    expect(detail.oldValue).to.eql(['opt2']);
    expect(detail.newValue).to.eql(['opt2','opt1']);
  });

  it('filters options on text input', async () => {
    const el = await fixture(html`<dt-multi-select options="${JSON.stringify(options)}"></dt-multi-select>`);
    const input = el.shadowRoot.querySelector('input');
    const optionsList = el.shadowRoot.querySelector('.option-list');
    input.focus();

    sendKeys({
      type: 'Sec',
    });
    await wait(50); // wait for UI update

    expect(optionsList).to.be.displayed;
    expect(optionsList).to.contain('button[value=opt2]');
    expect(optionsList).not.to.contain('button[value=opt1]');
    expect(optionsList).not.to.contain('button[value=opt3]');
  });

  it('filters options on option selection', async () => {
    const el = await fixture(html`<dt-multi-select options="${JSON.stringify(options)}"></dt-multi-select>`);
    const input = el.shadowRoot.querySelector('input');
    const optionsList = el.shadowRoot.querySelector('.option-list');
    input.focus();
    await clickOption(el, 'opt1');

    await wait(50); // wait for UI update

    expect(optionsList).not.to.contain('button[value=opt1]');
    expect(optionsList).to.contain('button[value=opt2]');
    expect(optionsList).to.contain('button[value=opt3]');
  });

  it('filters options on option removal', async () => {
    const el = await fixture(html`<dt-multi-select value="${JSON.stringify(['opt1','opt2'])}" options="${JSON.stringify(options)}"></dt-multi-select>`);
    const container = el.shadowRoot.querySelector('.field-container');

    expect(container).to.contain('button[data-value=opt1]');

    const optionBtn = el.shadowRoot.querySelector(`.selected-option button[data-value=opt1]`);
    optionBtn.click();
    await wait(100);

    const input = el.shadowRoot.querySelector('input');
    const optionsList = el.shadowRoot.querySelector('.option-list');
    input.focus();

    await wait(50); // wait for UI update

    expect(optionsList).to.contain('button[value=opt3]');
    expect(optionsList).to.contain('button[value=opt1]');
    expect(optionsList).not.to.contain('button[value=opt2]');
  });

  it('disables inputs', async () => {
    const el = await fixture(html`<dt-multi-select disabled value="${JSON.stringify(['opt1','opt2'])}" options="${JSON.stringify(options)}"></dt-multi-select>`);

    const input = el.shadowRoot.querySelector(('input'));
    expect(input).to.have.attribute('disabled');

    const inputGroup = el.shadowRoot.querySelector('.input-group');
    expect(inputGroup).to.have.class('disabled');

    const selectedOption = el.shadowRoot.querySelector('.selected-option');
    expect(selectedOption).to.have.descendant('button').with.attribute('disabled');
  });
});
