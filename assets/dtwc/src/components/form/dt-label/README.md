# Label
Form label to be displayed above input field

## Attributes
| Attribute | Description | Type | Default Value | Example Value | 
| --------- | ----------- | ---- | ------------- | ------------- |
| `icon`    | URL to an icon to be displayed before the label | `string` | - | - |
| `private` | Whether field should be marked as private | `boolean` | `false` | '<dt-label private>` |
| `privateLabel` | Customize tooltip text for private icon hover state. Pass in correct language content. | `string` | - | - |

## Slots
| Name | Description |
| ---- | ----------- |
| Default | Label to be displayed |
| `icon-start` | Icon to be displayed before label. Use if you want to embed an SVG directly on the page without an `img` tag |


## CSS Custom Properties
| Custom Properties   | Default Value   |
|---------------------|-----------------|
| `--dt-label-font-size` | `14px`   |
| `--dt-label-font-weight`  | `700`   |
| `--dt-label-color`  | `#000`  |
| `--dt-label-font-size` |  `14px` |
| `--dt-label-tooltip-color`  |  `#666`   |
| `--dt-label-tooltip-background` | `#eee`  |

## Features
- [x] label text
- [x] icon
- [x] private
- [x] private tooltip text
