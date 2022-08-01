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

## Features
- [x] header text
- [x] expand/collapse
- [x] field content
- [x] responsive layout
