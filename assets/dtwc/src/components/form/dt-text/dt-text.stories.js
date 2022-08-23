import { html } from 'lit';
import { themes, themeCss, argTypes } from '../../../stories-theme.js';
import './dt-text.js';

export default {
  title: 'dt-text',
  component: 'dt-text',
  argTypes: {
    theme: { control: 'select', options: Object.keys(themes), defaultValue: 'default' },
    id: { control: 'text' },
    name: { control: 'text' },
    label: { control: 'text' },
    value: { control: 'text' },
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
    label = 'Field Name',
    value = '',
    disabled = false,
    required = false,
    requiredMessage = '',
    icon = 'https://cdn-icons-png.flaticon.com/512/1077/1077114.png',
    isPrivate,
    privateLabel,
    loading = false,
    saved = false,
    onchange,
    slot,
  } = args;
  return html`
    <style>
    ${themeCss(args)}
    </style>
    <dt-text
      id=${id}
      name=${name}
      label=${label}
      value=${value}
      ?disabled=${disabled}
      ?required=${required}
      requiredMessage=${requiredMessage}
      icon="${icon}"
      ?private=${isPrivate}
      privateLabel="${privateLabel}"
      ?loading=${loading}
      ?saved=${saved}
      onchange=${onchange}
    >
    ${slot}
    </dt-text>
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
  value: 'Lorem Ipsum',
};

export const Disabled = Template.bind({});
Disabled.args = {
  disabled: true,
  value: 'Lorem Ipsum',
};

export const privateField = Template.bind({});
privateField.args = {
  isPrivate: true,
  value: 'Lorem Ipsum',
  privateLabel: 'This is a custom tooltip',
};

const FormDecorator = (story) => html`<form onsubmit="onFormSubmit(event)">
    ${story()}  
      
    <button type="submit">Submit</button>
    
    <pre><output></output></pre>
  </form>
  <script>
  function onFormSubmit(event) {
    if (event) {
      event.preventDefault();
    }
    
    const output = document.querySelector('output');
        
    const form = event.target;
  
    /** Get all of the form data */
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => data[key] = value);
    output.innerHTML = JSON.stringify(data, null, 2);
  }
  </script>
`;

export const Loading = Template.bind({});
Loading.args = {
  loading: true,
};
export const Saved = Template.bind({});
Saved.args = {
  saved: true,
};

export const basicForm = Template.bind({});
basicForm.decorators = [FormDecorator];
basicForm.args = {
  value: 'Lorem Ipsum',
};

export const required = Template.bind({});
required.decorators = [FormDecorator];
required.args = {
  required: true,
};

export const requiredCustomMessage = Template.bind({});
requiredCustomMessage.decorators = [FormDecorator];
requiredCustomMessage.args = {
  required: true,
  requiredMessage: 'Custom error message',
};
