# Single Select (Key Select)
Dropdown list that allows selection of a single value.

## Attributes
| Attribute | Description | Type | Default Value | Example Value | 
| --------- | ----------- | ---- | ------------- | ------------- |
| `name`    | Name of the field. Passed to `change` event to identify source of event. | `string` | - | - |
| `value`   | Selected value. <br> **Note:** This value is updated on the component when internal value changes. | `string` | | `opt1` |
| `placeholder` | Placeholder text when no selection is made | `string` | - | - |
| `options` | Array of options to choose. | `{id:string, label:string}[]` | - | `JSON.stringify([{id:'1',label:'Option 1'},{id:'2',label:'Option 2'}])` |
| `loading` | Show loading spinner | `boolean` | `false` | `<dt-tags loading>` |
| `saved`   | Show saved checkmark | `boolean` | `false` | `<dt-tags saved>` |
| `onchange` | Event triggered when value is changed. Makes available `event` variable that includes field name, old value, and new value in `event.details` | `event` | - | `onChange(event)` |
| `label`   | Text to be displayed in label | `string` | - | - |
| `icon`    | URL to an icon to be displayed before the label | `string` | - | - |
| `private` | Whether field should be marked as private | `boolean` | `false` | '<dt-label private>` |
| `privateLabel` | Customize tooltip text for private icon hover state. Pass in correct language content. | `string` | - | - |

## Slots

| Name | Description |
| ---- | ----------- |
| `icon-start` | Icon to be displayed before label. Use if you want to embed an SVG directly on the page without an `img` tag |

## Features
- [x] label
- [x] value
- [x] options
- [x] onchange
- [x] placeholder
- [x] loading
- [x] saved
- [ ] disabled
- [ ] required
- [x] color
- [ ] options.hidden
- [ ] options.order
