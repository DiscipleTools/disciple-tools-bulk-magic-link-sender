export const themes = {
  default: {
    name: 'light',
    args: {
      colorScheme: 'light',
      primaryColor: '#3f729b',
      primaryColorLight0: '#ecf5fc',
      primaryColorLight1: '#c2e0ff',

      gray0: '#666',
      gray1: '#919191',
      black: '#0a0a0a',

      textColor: '#0a0a0a',
      textColorInverse: '#fefefe',

      surface0: '#e2e2e2',
      surface1: '#fefefe',
      surface2: '#f5f5f5',

      borderColor: '#cecece',

      shadow0: '0 2px 4px rgb(0 0 0 / 25%)',
      shadow1: '0 4px 4px rgb(0 0 0 / 25%)',

      alertColor: '#cc4b37',
      cautionColor: '#f2c962',
      successColor: '#4caf50',
      inactiveColor: '#808080',
      disabledColor: '#e6e6e6',

      fontFamily: 'Helvetica, Arial, sans-serif',

      formBorderColor: 'var(--border-color)',
      formBorderColorAlert: 'var(--alert-color)',
      formBackgroundColor: 'var(--surface-1)',
      formDisabledBackgroundColor: 'var(--disabled-color)',
      formTextColor: 'var(--text-color)',
      formTextColorInverse: 'var(--text-color-inverse)',

      checkmarkWidth: '3px',
      checkmarkColor: 'var(--success-color)',

      connectionIconFill: 'var(--primary-color)',

      multiSelectTextColor: 'var(--text-color)',
      multiSelectBackgroundColor: 'var(--surface-1)',
      multiSelectTagBorderColor: 'var(--primary-color-light-1)',
      multiSelectTagBackgroundColor: 'var(--primary-color-light-0)',
      multiSelectOptionHoverBackground: 'var(--surface-2)',

      singleSelectTextColor: 'var(--dt-form-text-color)',
      singleSelectTextColorInverse: 'var(--dt-form-text-color-inverse)',

      dttextColor: 'var(--dt-form-text-color)',
      dttextColorInverse: 'var(--dt-form-text-color-inverse)',
      textBackgroundColor: 'var(--dt-form-background-color)',
      textBorderColor: 'var(--dt-form-border-color)',
      textBorderColorAlert: 'var(--dt-form-border-color-alert)',
      textDisabledBackgroundColor: 'var(--dt-form-disabled-background-color)',

      textareaBorderColor: 'var(--dt-form-border-color)',
      textareaBackgroundColor: 'var(--dt-form-background-color)',
      textareaDisabledBackgroundColor: 'var(--dt-form-disabled-background-color)',
      textareaTextColor: 'var(--dt-form-text-color)',
      textareaTextcolorInverse: 'var(--dt-form-text-color-inverse)',

      labelFontSize: '14px',
      labelFontWeight: '700',
      labelTextColor: 'var(--dt-form-text-color)',
      labelTooltipColor: 'var(--gray-0)',
      labelTooltipBackground: 'var(--surface-2)',

      spinnerColor1: 'var(--gray-1)',
      spinnerColor2: 'var(--black)',

      tileFontFamily: 'var(--font-family)',
      tileFontSize: '14px;',
      tileFontWeight: '700',
      tileBackground: 'var(--surface-1)',
      tileBorderColor: 'var(--border-color)',
      tileShadow: 'var(--shadow-0)',
      tileHeaderColor: 'var(--primary-color)',

      alertTextColorLight: '#fff',
      alertTextColorDark: '#000',
      alertTextColor: 'var(--text-color)',
      alertFontFamily: 'var(--font-family)',
      alertFontSize: '14px;',
      alertFontWeight: '700',
      alertBackground: 'var(--surface-1)',
      alertBorderColor: 'var(--border-color)',
      alertShadow: 'var(--shadow-0)',

      background: 'var(--surface-0)',

    },
  },
  dark: {
    name: 'dark',
    args: {
      colorScheme: 'dark',
      primaryColor: 'hsla(207, 87%, 40%, 1)',
      primaryColorLight0: 'hsla(207, 87%, 46%, 1)',
      primaryColorLight1: 'hsla(207, 97%, 36%, 1)',

      gray0: '#666',
      gray1: '#919191',
      black: '#0a0a0a',

      textColor: '#fefefe',
      textColorInverse: '#999',

      surface0: '#212529',
      surface1: '#343a40',
      surface2: '#495057',
      surface3:'#868e96',

      borderColor: '#868e96v',

      shadow0: '0 2px 4px rgb(0 0 0 / 25%)',

      shadow1: '0 4px 4px rgb(0 0 0 / 25%)',

      alertColor: '#cc4b37',
      cautionColor: '#f2c962',
      successColor: '#4caf50',
      inactiveColor: '#808080',
      disabledColor: '#343a40',

      fontFamily: 'Helvetica, Arial, sans-serif',

      formBorderColor: 'var(--border-color)',
      formBorderColorAlert: 'var(--alert-color)',
      formBackgroundColor: 'var(--surface-3)',
      formDisabledBackgroundColor: 'var(--disabled-color)',
      formTextColor: 'var(--text-color)',
      formTextColorInverse: 'var(--text-color-inverse)',

      checkmarkWidth: '3px',
      checkmarkColor: 'var(--success-color)',

      connectionIconFill: 'var(--primary-color)',

      multiSelectTextColor: 'var(--text-color)',
      multiSelectBackgroundColor: 'var(--surface-3)',
      multiSelectTagBorderColor: 'var(--primary-color-light-1)',
      multiSelectTagBackgroundColor: 'var(--primary-color-light-0)',
      multiSelectOptionHoverBackground: 'var(--surface-2)',

      singleSelectTextColor: 'var(--dt-form-text-color)',
      singleSelectTextColorInverse: 'var(--dt-form-text-color-inverse)',

      dttextColor: 'var(--dt-form-text-color)',
      dttextColorInverse: 'var(--dt-form-text-color-inverse)',
      textBackgroundColor: 'var(--dt-form-background-color)',
      textBorderColor: 'var(--dt-form-border-color)',
      textBorderColorAlert: 'var(--dt-form-border-color-alert)',
      textDisabledBackgroundColor: 'var(--dt-form-disabled-background-color)',

      textareaBorderColor: 'var(--dt-form-border-color)',
      textareaBackgroundColor: 'var(--dt-form-background-color)',
      textareaDisabledBackgroundColor: 'var(--dt-form-disabled-background-color)',
      textareaTextColor: 'var(--dt-form-text-color)',
      textareaTextcolorInverse: 'var(--dt-form-text-color-inverse)',

      labelFontSize: '14px',
      labelFontWeight: '700',
      labelTooltipColor: 'var(--gray-0)',
      labelTooltipBackground: 'var(--surface-2)',

      spinnerColor1: 'var(--gray-1)',
      spinnerColor2: 'var(--black)',

      tileFontFamily: 'var(--font-family)',
      tileFontSize: '14px;',
      tileFontWeight: '700',
      tileBackground: 'var(--surface-1)',
      tileBorderColor: 'var(--border-color)',
      tileShadow: 'var(--shadow-0)',
      tileHeaderColor: 'var(--primary-color)',

      alertTextColorLight: '#fff',
      alertTextColorDark: '#000',
      alertTextColor: 'var(--text-color)',
      alertFontFamily: 'var(--font-family)',
      alertFontSize: '14px;',
      alertFontWeight: '700',
      alertBackground: 'var(--surface-1)',
      alertBorderColor:'var(--border-color)',
      alertShadow: 'var(--shadow-0)',

      background: 'var(--surface-0)',
    },
  },
  dim: {
    name: 'dim',
    args: {
      colorScheme: 'dark',
      primaryColor: 'hsla(207, 87%, 40%, 1)',
      primaryColorLight0: 'hsla(207, 87%, 46%, 1)',
      primaryColorLight1: 'hsla(207, 97%, 36%, 1)',

      gray0: '#666',
      gray1: '#919191',
      black: '#0a0a0a',

      textColor: '#fefefe',
      textColorInverse: '#999',

      surface0: 'hsla(207, 9%, 31%, 1)',
      surface1: 'hsla(207, 7%, 56%, 1)',
      surface2: 'hsla(207, 7%, 60%, 1)',
      surface3:'hsla(207, 4%, 70%, 1)',

      borderColor: '#868e96v',

      shadow0: '0 2px 4px rgb(0 0 0 / 25%)',

      shadow1: '0 4px 4px rgb(0 0 0 / 25%)',

      alertColor: '#cc4b37',
      cautionColor: '#f2c962',
      successColor: '#4caf50',
      inactiveColor: '#808080',
      disabledColor: '#343a40',

      fontFamily: 'Helvetica, Arial, sans-serif',

      formBorderColor: 'var(--border-color)',
      formBorderColorAlert: 'var(--alert-color)',
      formBackgroundColor: 'var(--surface-3)',
      formDisabledBackgroundColor: 'var(--disabled-color)',
      formTextColor: 'var(--text-color)',
      formTextColorInverse: 'var(--text-color-inverse)',

      checkmarkWidth: '3px',
      checkmarkColor: 'var(--success-color)',

      connectionIconFill: 'var(--primary-color)',

      multiSelectTextColor: 'var(--text-color)',
      multiSelectBackgroundColor: 'var(--surface-3)',
      multiSelectTagBorderColor: 'var(--primary-color-light-1)',
      multiSelectTagBackgroundColor: 'var(--primary-color-light-0)',
      multiSelectOptionHoverBackground: 'var(--surface-2)',

      singleSelectTextColor: 'var(--dt-form-text-color)',
      singleSelectTextColorInverse: 'var(--dt-form-text-color-inverse)',

      dttextColor: 'var(--dt-form-text-color)',
      dttextColorInverse: 'var(--dt-form-text-color-inverse)',
      textBackgroundColor: 'var(--dt-form-background-color)',
      textBorderColor: 'var(--dt-form-border-color)',
      textBorderColorAlert: 'var(--dt-form-border-color-alert)',
      textDisabledBackgroundColor: 'var(--dt-form-disabled-background-color)',

      textareaBorderColor: 'var(--dt-form-border-color)',
      textareaBackgroundColor: 'var(--dt-form-background-color)',
      textareaDisabledBackgroundColor: 'var(--dt-form-disabled-background-color)',
      textareaTextColor: 'var(--dt-form-text-color)',
      textareaTextcolorInverse: 'var(--dt-form-text-color-inverse)',

      labelFontSize: '14px',
      labelFontWeight: '700',
      labelTooltipColor: 'var(--gray-0)',
      labelTooltipBackground: 'var(--surface-2)',

      spinnerColor1: 'var(--gray-1)',
      spinnerColor2: 'var(--black)',

      tileFontFamily: 'var(--font-family)',
      tileFontSize: '14px;',
      tileFontWeight: '700',
      tileBackground: 'var(--surface-1)',
      tileBorderColor: 'var(--border-color)',
      tileShadow: 'var(--shadow-0)',
      tileHeaderColor: 'var(--primary-color)',

      alertTextColorLight: '#fff',
      alertTextColorDark: '#000',
      alertTextColor: 'var(--text-color)',
      alertFontFamily: 'var(--font-family)',
      alertFontSize: '14px;',
      alertFontWeight: '700',
      alertBackground: 'var(--surface-1)',
      alertBorderColor: 'var(--border-color)',
      alertShadow: 'var(--shadow-0)',

      background: 'var(--surface-0)',
    },
  },
};

export const contexts = ['primary', 'alert', 'caution', 'success', 'inactive', 'disabled'];

export const argTypes = {
  theme: { control: 'select', options: Object.keys(themes), defaultValue: ['default'] },
  RTL: { control: 'boolean', defaultValue: false },
  primaryColor: { control: 'color' },
  primaryColorLight0: { control: 'color' },
  primaryColorLight1: { control: 'color' },
  gray0: { control: 'color' },
  gray1: { control: 'color' },
  black: { control: 'color' },

  textColor: { control: 'color' },
  textColorInverse: { control: 'color' },

  surface0: { control: 'color' },
  surface1: { control: 'color' },
  surface2: { control: 'color' },

  borderColor: { control: 'color' },

  shadow0: { control: 'text' },
  shadow1: { control: 'text' },

  alertColor: { control: 'color' },
  cautionColor: { control: 'color' },
  successColor: { control: 'color' },
  inactiveColor: { control: 'color' },
  disabledColor: { control: 'color' },

  fontFamily: { control: 'text' },

  formBorderColor: { control: 'color' },
  formBorderColorAlert: { control: 'color' },
  formBackgroundColor: { control: 'color' },
  formDisabledBackgroundColor: { control: 'color' },
  formTextColor: { control: 'color' },
  formTextColorInverse: { control: 'color' },

  checkmarkWidth: { control: 'text' },
  checkmarkColor: { control: 'color' },

  connectionIconFill: { control: 'color' },

  multiSelectTextColor: { control: 'color' },
  multiSelectBackgroundColor: { control: 'color' },
  multiSelectTagBorderColor: { control: 'color' },
  multiSelectTagBackgroundColor: { control: 'color' },
  multiSelectOptionHoverBackground: { control: 'color' },

  singleSelectTextColor: { control: 'color' },
  singleSelectTextColorInverse: { control: 'color' },

  dttextColor: { control: 'color' },
  dttextColorInverse: { control: 'color' },
  textBackgroundColor: { control: 'color' },
  textBorderColor: { control: 'color' },
  textBorderColorAlert: { control: 'color' },
  textDisabledBackgroundColor: { control: 'color' },

  textareaBorderColor: { control: 'color' },
  textareaBackgroundColor: { control: 'color' },
  textareaDisabledBackgroundColor: { control: 'color' },
  textareaTextColor: { control: 'color' },
  textareaTextcolorInverse: { control: 'color' },

  labelFontSize: { control: 'text' },
  labelFontWeight: { control: 'text' },
  labelTooltipColor: { control: 'color' },
  labelTooltipBackground: { control: 'color' },

  spinnerColor1: { control: 'color' },
  spinnerColor2: { control: 'color' },

  tileFontFamily: { control: 'color' },
  tileFontSize: { control: 'text' },
  tileFontWeight: { control: 'text' },
  tileBackground: { control: 'color' },
  tileBorderColor: { control: 'color' },
  tileShadow: { control: 'color' },
  tileHeaderColor: { control: 'color' },

  alertTextColorLight: '#fff',
  alertTextColorDark: '#000',
  alertTextColor: { control: 'color' },
  alertFontFamily: { control: 'color' },
  alertFontSize: { control: 'text' },
  alertFontWeight: { control: 'text' },
  alertBackground: { control: 'color' },
  alertBorderColor: { control: 'color' },
  alertShadow: { control: 'color' },
};

function getArg(storyArgs, argName) {
  if (storyArgs[argName]) {
    return storyArgs[argName];
  }

  if (storyArgs.theme) {
    return themes[storyArgs.theme].args[argName]
  }
  storyArgs.theme = 'default';
  return themes.default.args[argName];
}

export function themeCss(storyArgs) {
  return `
  html {
    --primary-color: ${getArg(storyArgs, 'primaryColor')};
    --primary-color-light-1: ${getArg(storyArgs, 'primaryColorLight1')};
    --primary-color-light-0: ${getArg(storyArgs, 'primaryColorLight0')};
    --gray-0: ${getArg(storyArgs, 'gray0')};
    --gray-1: ${getArg(storyArgs, 'gray1')};
    --black: ${getArg(storyArgs, 'black')};

    --text-color: ${getArg(storyArgs, 'textColor')};
    --text-color-inverse: ${getArg(storyArgs, 'textColorInverse')};

    --surface-0: ${getArg(storyArgs, 'surface0')};
    --surface-1: ${getArg(storyArgs, 'surface1')};
    --surface-2: ${getArg(storyArgs, 'surface2')};
    --surface-3: ${getArg(storyArgs, 'surface3')};

    --border-color: ${getArg(storyArgs, 'borderColor')};

    --shadow-0: ${getArg(storyArgs, 'shadow0')};
    --shadow-1: ${getArg(storyArgs, 'shadow1')};


    --alert-color: ${getArg(storyArgs, 'alertColor')};
    --caution-color: ${getArg(storyArgs, 'cautionColor')};
    --success-color: ${getArg(storyArgs, 'successColor')};
    --inactive-color: ${getArg(storyArgs, 'inactiveColor')};
    --disabled-color: ${getArg(storyArgs, 'disabledColor')};

    --font-family: ${getArg(storyArgs, 'fontFamily')};

    --dt-form-border-color: ${getArg(storyArgs, 'formBorderColor')};
    --dt-form-border-color-alert: ${getArg(storyArgs, 'formBorderColorAlert')};
    --dt-form-background-color: ${getArg(storyArgs, 'formBackgroundColor')};
    --dt-form-disabled-background-color: ${getArg(storyArgs, 'formDisabledBackgroundColor')};
    --dt-form-text-color: ${getArg(storyArgs, 'formTextColor')};
    --dt-form-text-color-inverse: ${getArg(storyArgs, 'formTextColorInverse')};
    --dt-form-text-border-color: ${getArg(storyArgs, 'formBorderColor')};

    --dt-checkmark-width: ${getArg(storyArgs, 'checkmarkWidth')};
    --dt-checkmark-color: ${getArg(storyArgs, 'checkmarkColor')};

    --dt-connection-icon-fill: ${getArg(storyArgs, 'connectionIconFill')};
    --dt-multi-select-text-color: ${getArg(storyArgs, 'multiSelectTextColor')};
    --dt-multi-select-background-color: ${getArg(storyArgs, 'multiSelectBackgroundColor')};
    --dt-multi-select-tag-border-color: ${getArg(storyArgs, 'multiSelectTagBorderColor')};
    --dt-multi-select-tag-background-color: ${getArg(storyArgs, 'multiSelectTagBackgroundColor')};
    --dt-multi-select-option-hover-background: ${getArg(storyArgs, 'multiSelectOptionHoverBackground')};

    --dt-single-select-text-color: ${getArg(storyArgs, 'singleSelectTextColor')};
    --dt-single-select-text-color-inverse: ${getArg(storyArgs, 'singleSelectTextColorInverse')};

    --dt-text-color: ${getArg(storyArgs, 'dttextColor')};
    --dt-text-color-inverse: ${getArg(storyArgs, 'dttextColorInverse')};
    --dt-text-background-color: ${getArg(storyArgs, 'textBackgroundColor')};
    --dt-text-border-color: ${getArg(storyArgs, 'textBorderColor')};
    --dt-text-border-color-alert: ${getArg(storyArgs, 'textBorderColorAlert')};
    --dt-text-disabled-background-color: ${getArg(storyArgs, 'textDisabledBackgroundColor')};
    --dt-textarea-border-color: ${getArg(storyArgs, 'textareaBorderColor')};
    --dt-textarea-background-color: ${getArg(storyArgs, 'textareaBackgroundColor')};
    --dt-textarea-disabled-background-color: ${getArg(storyArgs, 'textareaDisabledBackgroundColor')};
    --dt-textarea-text-color: ${getArg(storyArgs, 'textareaTextColor')};
    --dt-textarea-text-color-inverse: ${getArg(storyArgs, 'textareaTextcolorInverse')};
    --dt-label-font-size: ${getArg(storyArgs, 'labelFontSize')};
    --dt-label-font-weight: ${getArg(storyArgs, 'labelFontWeight')};
    --dt-label-color: ${getArg(storyArgs, 'labelTextColor')};
    --dt-label-tooltip-color: ${getArg(storyArgs, 'labelTooltipColor')};
    --dt-label-tooltip-background: ${getArg(storyArgs, 'labelTooltipBackground')};
    --dt-spinner-color-1: ${getArg(storyArgs, 'spinnerColor1')};
    --dt-spinner-color-2: ${getArg(storyArgs, 'spinnerColor2')};
    --dt-tile-font-family: ${getArg(storyArgs, 'tileFontFamily')};
    --dt-tile-font-size: ${getArg(storyArgs, 'tileFontSize')};
    --dt-tile-font-weight: ${getArg(storyArgs, 'tileFontWeight')};
    --dt-tile-background-color: ${getArg(storyArgs, 'tileBackground')};
    --dt-tile-border-color: ${getArg(storyArgs, 'tileBorderColor')};
    --dt-tile-shadow: ${getArg(storyArgs, 'tileShadow')};
    --dt-alert-text-color-light: ${getArg(storyArgs, 'alertTextColorLight')};
    --dt-alert-text-color-dark: ${getArg(storyArgs, 'alertTextColorDark')};
    --dt-alert-text-color: ${getArg(storyArgs, 'alertTextColor')};
    --dt-alert-font-family: ${getArg(storyArgs, 'alertFontFamily')};
    --dt-alert-font-size: ${getArg(storyArgs, 'alertFontSize')};
    --dt-alert-font-weight: ${getArg(storyArgs, 'alertFontWeight')};
    --dt-alert-background-color: ${getArg(storyArgs, 'alertBackground')};
    --dt-alert-border-color: ${getArg(storyArgs, 'alertBorderColor')};
    --dt-alert-shadow: ${getArg(storyArgs, 'alertShadow')};
    background: ${getArg(storyArgs, 'surface0')};
    color-scheme: ${getArg(storyArgs, 'colorScheme')};

    ${storyArgs.RTL ? 'direction: rtl;' : 'direction: ltr;'}
  }
  `;
}
