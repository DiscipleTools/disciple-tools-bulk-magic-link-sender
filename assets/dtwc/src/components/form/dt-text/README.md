# Text
Basic single line text input

## Attributes
| Attribute | Description | Type | Default Value | Example Value | 
| --------- | ----------- | ---- | ------------- | ------------- |
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
| `--dt-form-text-color`  | `#000`  |
| `--dt-text-background-color`  | `#fefefe` |
| `--dt-text-border-color`  | `#fefefe`   |
| `--dt-text-border-radius`   | `0`   |
| `--dt-text-input-box-shadow`  | `--dt-form-input-box-shadow`   |
| `--dt-form-padding`   | `0.5333333333rem`   |
| `--dt-form-transition` | `box-shadow .5s,border-color .25s ease-in-out`   |
| `--dt-text-disabled-background-color`   | `--dt-form-disabled-background-color`   |
| `--dt-text-placeholder-color` | `#999`  |
| `--dt-text-placeholder-transform` |  `none`   |
| `--dt-text-placeholder-font-size`   | `1rem`  |
|  `--dt-text-placeholder-font-weight`  |  `400`  |
| `--dt-text-placeholder-letter-spacing`  | `normal`  |
| `--dt-text-border-color-alert`  | `--alert-color`   |


## Features
- [x] label
- [x] value
- [x] onchange
- [ ] placeholder
- [ ] loading
- [ ] saved
- [x] disabled
- [x] required
