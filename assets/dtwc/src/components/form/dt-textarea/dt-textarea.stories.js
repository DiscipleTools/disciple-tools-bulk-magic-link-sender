import { html } from 'lit';
import './dt-textarea.js';

export default {
  title: 'dt-textarea',
  component: 'dt-textarea',
  argTypes: {
    id: { control: 'text' },
    name: { control: 'text' },
    value: { control: 'text' },
    disabled: { control: 'boolean' },
    icon: { control: 'text' },
    isPrivate: { control: 'boolean' },
    loading: { control: 'boolean' },
    saved: { control: 'boolean' },
    onchange: { control: 'text' },
  },
};

function Template({ id = 'name', name = 'field-name', label = 'Field Name' , value = '', disabled = false, icon='https://cdn-icons-png.flaticon.com/512/1077/1077114.png', isPrivate = false, loading = false, saved = false, onchange = 'onChange' }) {
  console.log(value);
  return html`
    <dt-textarea
      id=${id}
      name=${name}
      label=${label}
      ?disabled=${disabled}
      icon=${icon}
      ?private=${isPrivate}
      ?loading=${loading}
      ?saved=${saved}
      onchange=${onchange}
      value=${value}
    >
    </dt-textarea>
  `;
}

export const Empty = Template.bind({});

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
};
