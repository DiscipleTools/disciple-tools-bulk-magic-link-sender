# Textarea
Multi-line text input

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
| `--dt-textarea-text-color`  | `#0a0a0a`  |
| `--dt-textarea-background-color`  | `#fefefe` |
| `--dt-textarea-border-color`  | `#cecece`   |
| `--dt-textarea-input-box-shadow`  | `inset 0 1px 2px hsl(0deg 0% 4% / 10%)`   |
| `--dt-form-padding`   | `0.5333333333rem`   |
| `--dt-form-transition` | `box-shadow .5s,border-color .25s ease-in-out`   |
| `--dt-textarea-disabled-background-color`   | `#e6e6e6`   |

## Features
- [x] label
- [x] value
- [x] onchange
- [ ] placeholder
- [ ] loading
- [ ] saved
- [x] disabled
- [ ] required
