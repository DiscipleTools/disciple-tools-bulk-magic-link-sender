~~# Alert

A dismissable message box.

## Attributes

| Attribute     | Description                                              | Type      | Default Value | Example Value               | 
|---------------|----------------------------------------------------------|-----------|---------------|-----------------------------|
| `context`     | Context color.                                           | `string`  | 'default'     | '<dt-tile theme="success">` |
| `dismissable` | Whether alert should be able to be dismissed             | `boolean` | `false`       | '<dt-tile dismissable>`     |
| `timeout`     | The number of milliseconds before the alert is dismissed | `number`  | '0'           | '<dt-tile timeout="10000">` |
| `show`        | Whether the alert is visible                             | `boolean` | `true`        | '<dt-tile show="false">`    |

## Slots

| Name    | Description             |
|---------|-------------------------|
| Default | Contents of the alert.  |

## CSS Custom Properties
| Custom Properties   | Default Value   |
|---------------------|-----------------|
| `--dt-alert-padding`  | `10px`        |
| `--dt-alert-font-family`  |       |
| `--dt-alert-font-size`  | `14px`      |
| `--dt-alert-font-weight`  | `700`     |
| `--dt-alert-context-background-color` |  `--dt-alert-background-color`     |
| `--dt-alert-border-width` | `1px`     |
| `--dt-alert-context-border-color` | `--dt-alert-border-color` |
| `--dt-alert-border-radius`  | `10px`  |
| `--dt-alert-box-shadow`   | `0 2px 4px rgb(0 0 0 / 25%)`  |
| `--dt-alert-context-text-color`   | `--dt-alert-text-color` |
| `--dt-alert-gap`  | `10px`  |
| `--dt-alert-context-text-color`   | `--text-color-inverse`  |



## Features

- [x] Preset color schemes.
- [x] Conditionally display based on boolean.
- [x] Dismissable by user.
- [x] Dismissable after timeout.
