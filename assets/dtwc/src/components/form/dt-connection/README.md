# Connection
Implementation of the Tags field that customizes the options list for displaying posts (contacts, groups, etc).

## Attributes
| Attribute | Description | Type | Default Value | Example Value | 
| --------- | ----------- | ---- | ------------- | ------------- |
| `name`    | Name of the field. Passed to `change` event to identify source of event. | `string` | - | - |
| `value`   | Selected values. Should be an array of option objects converted to JSON string. If an option is removed, it will have a `delete` property. <br> **Note:** This value is updated on the component when internal value changes. | `{id:string, label:string}[]` | | `JSON.stringify([{id:'1',label:'Option 1'},{id:'2',label:'Option 2'}])` |
| `placeholder` | Placeholder text when no selection is made | `string` | - | - |
| `options` | Array of options to choose. If not set, `onLoad` will be triggered to fetch via API. | `{id:string, label:string, link:string, status:{key:string, label:string, color:string}}[]` | - | `JSON.stringify([{id:'1',label:'Option 1',link:'/#opt1'},{id:'2',label:'Option 2',link:'/#opt2'}])` |
| `loading` | Show loading spinner | `boolean` | `false` | `<dt-tags loading>` |
| `saved`   | Show saved checkmark | `boolean` | `false` | `<dt-tags saved>` |
| `allowAdd`   | Allow new options to be added that don't exist in options list | `boolean` | `false` | `<dt-tags allowAdd>` |
| `onchange` | Event triggered when value is changed. Makes available `event` variable that includes field name, old value, and new value in `event.details` | `event` | - | `onChange(event)` |
| `onload` | Event triggered when search query is changed or options list needs to be populated. Makes available `event` variable that includes field name, search query, onSuccess event, and onError event in `event.details` | `event` | - | `onLoad(event)` |
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
| `--dt-connection-icon-fill`   | `--primary-color`   |

## Feature Status
- [x] label
- [x] value
- [x] options (static)
- [x] options (via API, onload event)
- [x] filter options (static)
- [x] filter options (from API)
- [x] select option (mouse)
- [x] select option (keyboard)
- [x] add new option
- [x] status indicators
- [x] link selected option
- [x] onchange
- [x] placeholder
- [x] loading
- [x] saved
- [x] disabled
- [x] track deleted options

