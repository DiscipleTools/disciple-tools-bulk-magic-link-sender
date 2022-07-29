# Connection
Implementation of the Tags field that customizes the options list for display posts (contacts, groups, etc).

## Attributes
| Attribute | Description | Type | Default Value | Example Value | 
| --------- | ----------- | ---- | ------------- | ------------- |
| `name`    | Name of the field. Passed to `change` event to identify source of event. | `string` | - | - |
| `value`   | Selected values. Should be an array of option objects converted to JSON string. <br> **Note:** This value is updated on the component when internal value changes. | `{id:string, label:string}[]` | | `JSON.stringify([{id:'1',label:'Option 1'},{id:'2',label:'Option 2'}])` |
| `placeholder` | Placeholder text when no selection is made | `string` | - | - |
| `options` | Array of options to choose. If not set, `onLoad` will be triggered to fetch via API. | `{id:string, label:string}[]` | - | `JSON.stringify([{id:'1',label:'Option 1'},{id:'2',label:'Option 2'}])` |
| `loading` | Show loading spinner | `boolean` | `false` | `<dt-tags loading>` |
| `saved`   | Show saved checkmark | `boolean` | `false` | `<dt-tags saved>` |
| `allowAdd`   | Allow new options to be added that don't exist in options list | `boolean` | `false` | `<dt-tags allowAdd>` |
| `onchange` | Event triggered when value is changed. Makes available `event` variable that includes field name, old value, and new value in `event.details` | `event` | - | `onChange(event)` |
| `onload` | Event triggered when search query is changed or options list needs to be populated. Makes available `event` variable that includes field name, search query, onSuccess event, and onError event in `event.details` | `event` | - | `onLoad(event)` |

## Feature Status
- [ ] value
- [ ] options (static)
- [ ] options (via API, onload event)
- [ ] filter options (static)
- [ ] filter options (from API)
- [ ] select option (mouse)
- [ ] select option (keyboard)
- [ ] add new option
- [ ] onchange
- [ ] placeholder
- [ ] loading
- [ ] saved
- [ ] disabled

