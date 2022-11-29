# Multi Select
Select field that allows multiple values to be selected.

## Attributes
| Attribute | Description | Type | Default Value | Example Value | 
| --------- | ----------- | ---- | ------------- | ------------- |
| `name`    | Name of the field. Passed to `change` event to identify source of event. | `string` | - | - |
| `value`   | Selected values. Should be an array of option IDs converted to JSON string. If an option is removed, it will be prefixed with a hyphen ("-"). <br> **Note:** This value is updated on the component when internal value changes. | `{id:string, label:string}[]` | | `JSON.stringify(['1','2'])` |
| `placeholder` | Placeholder text when no selection is made | `string` | - | - |
| `options` | Array of options to choose. If not set, `onLoad` will be triggered to fetch via API. | `{id:string, label:string}[]` | - | `JSON.stringify([{id:'1',label:'Option 1'},{id:'2',label:'Option 2'}])` |
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

## CSS Custom Properties
| Custom Properties   | Default Value   |
|---------------------|-----------------|
| `--dt-multi-select-text-color` | `#0a0a0a`  |
| `--dt-multi-select-background-color` | `#fefefe`  |
| `--dt-multi-select-text-color` | `#0a0a0a`  |
| `--dt-multi-select-tag-border-color`  | `#c2e0ff`   |
| `--dt-multi-select-option-hover-background`  | `#f5f5f5`  |

| `--dt-form-border-color`| `#cacaca`   |
| `--dt-form-background-color`  | `#fff`  |
| `--dt-form-text-color` | `#000`  |

## Feature Status
- [x] label
- [x] value
- [x] options
- [x] filter options
- [x] select option (mouse)
- [x] select option (keyboard)
- [x] onchange
- [x] placeholder
- [x] loading
- [x] saved
- [x] disabled
- [x] track deleted options

