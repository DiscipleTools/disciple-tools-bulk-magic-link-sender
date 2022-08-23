# Number
Basic number input

## Attributes
| Attribute | Description | Type | Default Value | Example Value | 
| --------- | ----------- | ---- | ------------- | ------------- |
| `name` | Name of the input. Passed to change event to identify source of event. | `string` | - | - |
| `value` | Value of the input. | `number` | - | - |
| `min` | Minimum value of the input. | `number` | - | - |
| `max` | Maximum value of the input. | `number` | - | - |
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
- [x] onchange
- [x] min
- [x] max
- [ ] placeholder
- [ ] loading
- [ ] saved
- [ ] disabled
- [ ] required
