import { html } from 'lit';
import { fixture, expect } from '@open-wc/testing';

import './dt-label.js';

describe('DT-Label', () => {
  it('Check the label slot', async () => {
    const el = await fixture(html`<dt-label>Label</dt-label>`);

    expect(el.textContent).to.contain('Label');
  });

  it('input private', async () => {
    const el = await fixture(html`<dt-label private>Private Label</dt-label>`);
    expect(el.shadowRoot.querySelector("div > span.icon.private")).to.exist
    expect(el.shadowRoot.querySelector("div > span.icon.private > span")).to.exist
  });

  it('input private with custom tooltip', async () => {
    const el = await fixture(html`<dt-label private privateLabel="This is a custom tooltip">Private Label</dt-label>`);
    expect(el.shadowRoot.querySelector("div > span.icon.private > span")).to.have.text("This is a custom tooltip");
  });


  it('passes the a11y audit', async () => {
    const el = await fixture(html`<dt-label id='name' name='Name' label='Label Name' value='John Doe'></dt-label>`);

    await expect(el).shadowDom.to.be.accessible();
  });
});
