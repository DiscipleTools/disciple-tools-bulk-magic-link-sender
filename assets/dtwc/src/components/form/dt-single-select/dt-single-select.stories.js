import { html } from 'lit';
import { themes, themeCss, argTypes } from '../../../stories-theme.js';
import './dt-single-select.js';

const basicOptions = [
  {
    id: 'opt1',
    label: 'Option 1',
  },
  {
    id: 'opt2',
    label: 'Option 2',
  },
  {
    id: 'opt3',
    label: 'Option 3',
  },
];
const colorOptions = [
  {
    id: 'opt1',
    label: 'Red',
    color: '#990000',
  },
  {
    id: 'opt2',
    label: 'Green',
    color: '#009900',
  },
  {
    id: 'opt3',
    label: 'Blue',
    color: '#000099',
  },
  {
    id: 'opt4',
    label: 'Pale Blue',
    color: '#aaaaff',
  },
];
export default {
  title: 'dt-single-select',
  component: 'dt-single-select',
  argTypes: {
    name: { control: 'text' },
    theme: { control: 'select', options: Object.keys(themes), defaultValue: 'default' },
    placeholder: { control: 'text' },
    value: { control: 'text' },
    isLoading: { control: 'boolean' },
    isSaved: { control: 'boolean' },
    ...argTypes,
  },
};

function Template(args) {
  const {
    name = 'field-name',
    label = 'Field Name',
    options,
    placeholder,
    value,
    icon = 'https://cdn-icons-png.flaticon.com/512/1077/1077114.png',
    isPrivate,
    privateLabel,
    onChange,
    isLoading,
    isSaved,
    slot,
  } = args;
  return html`
    <style>
    ${themeCss(args)}
    </style>
    <script>
      function onChange(event) {
        if (event?.target) {
          event.target.setAttribute('loading', true);
          console.log(
            'Value changed from ' +
              JSON.stringify(event.detail.oldValue) +
              ' to ' +
              JSON.stringify(event.detail.newValue)
          );
          setTimeout(function () {
            event.target.removeAttribute('loading');
            event.target.setAttribute('saved', true);
          }, 1000);
        }
      }
    </script>
    <dt-single-select
      name="${name}"
      label="${label}"
      placeholder="${placeholder}"
      options="${JSON.stringify(options)}"
      value="${value}"
      icon="${icon}"
      ?private=${isPrivate}
      privateLabel="${privateLabel}"
      onchange="${onChange}"
      ?loading="${isLoading}"
      ?saved="${isSaved}"
    >
    ${slot}
    </dt-single-select>
  `;
}

export const Empty = Template.bind({});

export const SvgIcon = Template.bind({});
SvgIcon.args = {
  icon: null,
  slot: html`<svg slot="icon-start" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><linearGradient id="lg"><stop offset="0%" stop-color="#000000"/><stop offset="100%" stop-color="#c3c3c3"/></linearGradient><rect x="2" y="2" width="96" height="96" style="fill:url(#lg);stroke:#ffffff;stroke-width:2"/><text x="50%" y="50%" font-size="18" text-anchor="middle" alignment-baseline="middle" font-family="monospace, sans-serif" fill="#ffffff">icon</text></svg>`,
};

export const CustomOptions = Template.bind({});
CustomOptions.args = {
  options: basicOptions,
};

export const CustomPlaceholder = Template.bind({});
CustomPlaceholder.args = {
  placeholder: '--Select--',
};

export const SelectedValue = Template.bind({});
SelectedValue.args = {
  value: 'opt2',
  options: basicOptions,
};

export const ColorChange = Template.bind({});
ColorChange.args = {
  value: 'opt1',
  options: colorOptions,
};

export const AutoSave = Template.bind({});
AutoSave.args = {
  options: basicOptions,
  onChange: 'onChange(event)',
};

export const Loading = Template.bind({});
Loading.args = {
  value: 'opt2',
  options: basicOptions,
  isLoading: true,
};
export const Saved = Template.bind({});
Saved.args = {
  value: 'opt2',
  options: basicOptions,
  isSaved: true,
};
