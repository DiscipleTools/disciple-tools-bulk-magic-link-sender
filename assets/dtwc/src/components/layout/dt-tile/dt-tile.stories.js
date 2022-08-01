import { html } from 'lit-html';
import { themeCss } from '../../../stories-theme.js';
import './dt-tile.js';
import '../../form/dt-label/dt-label.js';
import '../../form/dt-text/dt-text.js';

export default {
  title: 'dt-tile',
  component: 'dt-tile',
};

const Template = (args) => html`
  <style>
    body { background-color: #e2e2e2; }
    ${themeCss(args)}
  </style>
  <dt-tile
    title="${args.title}"
    ?expands="${args.expands}"
    ?collapsed="${args.collapsed}"
  >
    <div>
      <dt-label>Field 1</dt-label>
      <dt-text></dt-text>
    </div>
    <div>
      <dt-label>Field 2</dt-label>
      <dt-text></dt-text>
    </div>
    <div>
      <dt-label>Field 3</dt-label>
      <dt-text></dt-text>
    </div>
    <div>
      <dt-label>Field 4</dt-label>
      <dt-text></dt-text>
    </div>
    <div>
      <dt-label>Field 5</dt-label>
      <dt-text></dt-text>
    </div>
  </dt-tile>
`;

export const Basic = Template.bind({});
Basic.args = {
  title: 'My Tile',
};

export const Expands = Template.bind({});
Expands.args = {
  title: 'My Tile',
  expands: true,
};

export const Collapsed = Template.bind({});
Collapsed.args = {
  title: 'My Tile',
  expands: true,
  collapsed: true,
};
