import { html } from 'lit';
import './dt-number.js';
import { themes, themeCss, argTypes} from '../../../stories-theme.js';

export default {
  title: 'dt-number',
  component: 'dt-number',
  argTypes: {
    theme: { control: 'select', options: Object.keys(themes), defaultValue: 'default' },
    id: { control: 'text' },
    name: { control: 'text' },
    label: { control: 'text' },
    value: { control: 'text' },
    min: { control: 'text' },
    max: { control: 'text' },
    disabled: { control: 'boolean' },
    icon: { control: 'text' },
    isPrivate: { control: 'boolean' },
    loading: { control: 'boolean' },
    saved: { control: 'boolean' },
    onchange: { control: 'text' },
    ...argTypes,
  },
};

function Template(args) {
  const {
    id = 'name',
    name = 'field-name',
    label = 'Field Name' ,
    value = '',
    disabled,
    min,
    max,
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
    <dt-number
      id=${id}
      name=${name}
      label=${label}
      .value=${value}
      .min=${min}
      .max=${max}
      ?disabled=${disabled}
      icon=${icon}
      ?private=${isPrivate}
      ?loading=${loading}
      ?saved=${saved}
      onchange=${onchange}
    >
      ${slot}
    </dt-number>
  `;
}

export const Empty = Template.bind({});

export const SvgIcon = Template.bind({});
SvgIcon.args = {
  icon: null,
  slot: html`<svg slot="icon-start" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><linearGradient id="lg"><stop offset="0%" stop-color="#000000"/><stop offset="100%" stop-color="#c3c3c3"/></linearGradient><rect x="2" y="2" width="96" height="96" style="fill:url(#lg);stroke:#ffffff;stroke-width:2"/><text x="50%" y="50%" font-size="18" text-anchor="middle" alignment-baseline="middle" font-family="monospace, sans-serif" fill="#ffffff">icon</text></svg>`,
};

export const EnteredValue = Template.bind({});
EnteredValue.args = {
  value: 7,
};

export const MinValue = Template.bind({});
MinValue.args = {
  min: 9,
};

export const MaxValue = Template.bind({});
MaxValue.args = {
  max: 10,
};

export const MinMaxValue = Template.bind({});
MinMaxValue.args = {
  min: 3,
  max: 7,
};

export const Disabled = Template.bind({});
Disabled.args = {
  disabled: true,
  value: 12,
};

export const privateField = Template.bind({});
privateField.args = {
  isPrivate: true,
  value: 77,
};
