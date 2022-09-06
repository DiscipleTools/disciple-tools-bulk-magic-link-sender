import { html } from 'lit';
import './dt-date.js';
import { themes, themeCss, argTypes } from '../../../stories-theme.js';

export default {
  title: 'dt-date',
  component: 'dt-date',
  argTypes: {
    id: { control: 'text' },
    name: { control: 'text' },
    theme: { control: 'select', options: Object.keys(themes), defaultValue: 'default' },
    label: { control: 'text' },
    value: { control: 'number' },
    date: { control: 'text' },
    disabled: { control: 'boolean' },
    icon: { control: 'text' },
    isPrivate: { control: 'boolean' },
    loading: { control: 'boolean' },
    saved: { control: 'boolean' },
    onchange: { control: 'text' },
    ...argTypes,
  },
  args: {
    theme: 'default'
  }
};

function Template(args) {
  const {
    id = 'name',
    name = 'field-name',
    label = 'Date Field',
    value = '',
    timestamp = 0,
    disabled,
    icon='https://cdn-icons-png.flaticon.com/512/1077/1077114.png',
    isPrivate,
    loading,
    saved,
    onchange,
    slot,
  } = args;
  return html`
    <style>
    ${themeCss(args)}
    </style>
    <dt-date
      id=${id}
      name=${name}
      label=${label}
      value=${value}
      timestamp=${timestamp}
      ?disabled=${disabled}
      icon=${icon}
      ?private=${isPrivate}
      ?loading=${loading}
      ?saved=${saved}
      onchange=${onchange}
    >
      ${slot}
    </dt-date>
  `;
}

export const Empty = Template.bind({});

export const SvgIcon = Template.bind({});
SvgIcon.args = {
  icon: null,
  slot: html`<svg slot="icon-start" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><linearGradient id="lg"><stop offset="0%" stop-color="#000000"/><stop offset="100%" stop-color="#c3c3c3"/></linearGradient><rect x="2" y="2" width="96" height="96" style="fill:url(#lg);stroke:#ffffff;stroke-width:2"/><text x="50%" y="50%" font-size="18" text-anchor="middle" alignment-baseline="middle" font-family="monospace, sans-serif" fill="#ffffff">icon</text></svg>`,
};

export const EnteredPHPTimestamp = Template.bind({});
EnteredPHPTimestamp.args = {
  timestamp: 1468281600,
};

export const EnteredDateString = Template.bind({});
EnteredDateString.args = {
  value: '2020-01-01',
};

export const EnteredJSTimestamp = Template.bind({});
EnteredJSTimestamp.args = {
  timestamp: 1658361600000,
};
