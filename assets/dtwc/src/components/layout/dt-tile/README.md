# Tile
Card-style tile for displaying groups of fields. Arranges fields in responsive grid.

## Attributes
| Attribute | Description | Type | Default Value | Example Value | 
| --------- | ----------- | ---- | ------------- | ------------- |
| `title` | Title to be displayed in header | `string` | - | - |
| `expands` | Whether tile should be able to expand/collapse | `boolean` | `false` | '<dt-tile expands>` |
| `collapsed` | Whether tile is collapsed | `boolean` | `false` | '<dt-tile collapsed>` |

## Slots
| Name | Description |
| ---- | ----------- |
| Default | Contents of tile. Uses grid layout, so fields should be wrapped with a container element if they are multiple elements. |

## CSS Custom Properties
| Custom Properties   | Default Value   |
|---------------------|-----------------|
| `--dt-tile-font-family`   | `--font-family`   |
| `--dt-tile-font-size`   | `14px`  |
| `--dt-tile-font-weight`   | `700`   |
| `--dt-tile-background-color`  | `#fefefe`   |
| `--dt-tile-background-color`  | `#fefefe`   |
| `--dt-tile-border-radius`   | `10px`  |
| `--dt-tile-box-shadow`  | `0 2px 4px rgb(0 0 0 / 25%)`  |
| `--dt-list-header-color` | `--primary-color` |
| `--dt-tile-header-color` | `--primary-color` |

## Features
- [x] header text
- [x] expand/collapse
- [x] field content
- [x] responsive layout
