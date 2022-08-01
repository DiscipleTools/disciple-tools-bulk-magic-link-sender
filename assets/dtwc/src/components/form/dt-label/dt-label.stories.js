import { html } from 'lit-html';
import { themeCss } from '../../../stories-theme.js';
import './dt-label.js';

export default {
  title: 'dt-label',
  component: 'dt-label',
};

const Template = (args) => html`
  <style>
    ${themeCss(args)}
  </style>
  <dt-label
    ?private="${args.private}"
  >
    ${args.label}
    ${args.slotContent}
  </dt-label>
`;

export const Basic = Template.bind({});
Basic.args = {
  label: 'My Field Label',
};

export const ImgLabel = Template.bind({});
ImgLabel.args = {
  label: 'My Field Label',
  slotContent: html`<img slot="icon-start" src="https://placeholder.pics/svg/100/000000-C3C3C3/FFFFFF/icon" alt="icon" />`,
};

export const SvgLabel = Template.bind({});
SvgLabel.args = {
  label: 'My Field Label',
  slotContent: html`<svg slot="icon-start" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><linearGradient id="lg"><stop offset="0%" stop-color="#000000"/><stop offset="100%" stop-color="#c3c3c3"/></linearGradient><rect x="2" y="2" width="96" height="96" style="fill:url(#lg);stroke:#ffffff;stroke-width:2"/><text x="50%" y="50%" font-size="18" text-anchor="middle" alignment-baseline="middle" font-family="monospace, sans-serif" fill="#ffffff">icon</text></svg>`,
};

export const Private = Template.bind({});
Private.args = {
  label: 'My Field Label',
  private: true,
};

export const PrivateCustomTooltip = Template.bind({});
PrivateCustomTooltip.args = {
  label: 'My Field Label',
  private: true,
  slotContent: html`<span slot="private-tooltip">Add other language content here</span>`,
};
