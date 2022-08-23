# Date
Basic date input

## Attributes
| Attribute | Description | Type | Default Value | Example Value | 
| --------- | ----------- | ---- | ------------- | ------------- |
| `value` | Value of the input as a date string in yyyy-MM-dd format  | string | `null` | `'2020-01-01'` |
| `timestamp` | Timestamp value in either seconds or millisecond percision. | number | `null` | `1577836800` |
| `name` | Name of the input | string | `null` | `'date'` |
| `disabled` | Disable the input | boolean | `false` | `true` |
| `required` | Make the input required | boolean | `false` | `true` |
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
- [x] datepicker UI - uses Native date input
- [x] clear input/value
- [x] placeholder
- [ ] loading
- [ ] saved
- [ ] disabled
- [ ] required
- [ ] i18n dates
