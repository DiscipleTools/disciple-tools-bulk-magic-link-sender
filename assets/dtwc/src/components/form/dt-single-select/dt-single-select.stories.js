import { html } from 'lit';
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
    placeholder: { control: 'text' },
    value: { control: 'text' },
    isLoading: { control: 'boolean' },
    isSaved: { control: 'boolean' },
  },
};

function Template({
  name = 'my-input',
  options,
  placeholder,
  value,
  onChange,
  isLoading,
  isSaved,
}) {
  return html`
    <style>
      :root {
        --primary-color: #3f729b;
      }
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
      placeholder="${placeholder}"
      options="${JSON.stringify(options)}"
      value="${value}"
      onchange="${onChange}"
      ?loading="${isLoading}"
      ?saved="${isSaved}"
    >
    </dt-single-select>
  `;
}

export const Empty = Template.bind({});

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
