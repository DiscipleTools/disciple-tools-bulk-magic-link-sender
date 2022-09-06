import { html } from 'lit';
import { argTypes, themeCss } from '../stories-theme.js';

import './layout/dt-tile/dt-tile.js';
import './form/dt-label/dt-label.js';
import './form/dt-text/dt-text.js';
import './form/dt-textarea/dt-textarea.js';
import './form/dt-number/dt-number.js';
import './form/dt-date/dt-date.js';
import './form/dt-multi-select/dt-multi-select.js';
import './form/dt-single-select/dt-single-select.js';
import './form/dt-tags/dt-tags.js';
import './form/dt-connection/dt-connection.js';

export default {
  title: 'Kitchen Sink',
  argTypes,
  theme: 'default',
};

const options = [{
  id: 'opt1', label: 'Option 1',
}, {
  id: 'opt2', label: 'Option 2',
}, {
  id: 'opt3', label: 'Option 3',
}, {
  id: 'opt4', label: 'Option 4',
}, {
  id: 'opt5', label: 'Option 5',
}];
const tags = [{
  id: 'personal', label: 'Personal',
}, {
  id: 'web', label: 'Web',
}, {
  id: 'facebook', label: 'Facebook',
}];
const posts = [{
  id: '1', label: 'John Doe',
}, {
  id: '2', label: 'Jane Smith', user: true,
}, {
  id: '3', label: 'Trevor Virtue', user: true,
}, {
  id: '4', label: 'Jane Meldrum',
}]
function Template(theme) {
  return html`
    <style>
      ${themeCss(theme)}
    </style>
    <dt-tile
      title="Kitchen Sink Tile"
      expands: true,
    >
      <dt-text id='textField' name='textField' value='' label="Text Field"></dt-text>

      <dt-textarea id='textareaField' name='textareaField' value='' label="Textarea Field"></dt-textarea>

      <dt-number id="numberField" name="numberField" label="Number Field"></dt-number>

      <dt-date id="dateField" name="dateField" label="Date Field"></dt-date>

      <dt-single-select
        label="Single Select Field"
        placeholder="Select Item"
        value="opt1"
        options="${JSON.stringify(options)}"
      ></dt-single-select>

      <dt-multi-select
        label="Multi Select Field"
        placeholder="Select Items"
        value="${JSON.stringify(options.slice(0, 2).map(o => o.id))}"
        options="${JSON.stringify(options)}"
      ></dt-multi-select>

      <dt-tags
        label="Tags Field"
        value="${JSON.stringify([{id:'personal',label:'Personal'}])}"
        options="${JSON.stringify(tags)}"
      ></dt-tags>

      <dt-connection
        label="Connection Field"
        options="${JSON.stringify(posts)}"
      ></dt-connection>
    </dt-tile>
    <pre><code>
    ${themeCss(theme)}
    </code></pre>
  `;
}

export const KitchenSink = Template.bind({});
