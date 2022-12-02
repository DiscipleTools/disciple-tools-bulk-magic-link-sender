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
      textColorMid: '#808080',
      textColorInverse: '#fefefe',

      surface0: '#e2e2e2',
      surface1: '#fefefe',
      surface2: '#f5f5f5',
      surface3: '#ffffff',

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
      dttextColorMid: 'var(--dt-form-text-color-mid)',
      dttextColorInverse: 'var(--dt-form-text-color-inverse)',
      textBackgroundColor: 'var(--dt-form-background-color)',
      textBorderColor: 'var(--dt-form-border-color)',
      textBorderColorAlert: 'var(--dt-form-border-color-alert)',
      textDisabledBackgroundColor: 'var(--dt-form-disabled-background-color)',
      textBorderRadius: '0',
      textBoxShadow: 'var(--dt-form-box-shadow)',
      textPlaceholderColor: 'var(--dt-text-placeholder-color)',
      textPlaceholderTextTransform: 'none',
      textPlaceholderFontSize: 'var(--dt-text-placeholder-font-size)',
      textPlaceholderFontWeight: 'var(--dt-text-placeholder-font-weight)',
      textPlaceholderLetterSpacing: 'var(--dt-text-placeholder-letter-spacing)',

      textareaBorderColor: 'var(--dt-form-border-color)',
      textareaBackgroundColor: 'var(--dt-form-background-color)',
      textareaDisabledBackgroundColor:
        'var(--dt-form-disabled-background-color)',
      textareaTextColor: 'var(--dt-form-text-color)',
      textareaTextcolorInverse: 'var(--dt-form-text-color-inverse)',

      labelFontSize: '14px',
      labelFontWeight: '700',
      labelTextColor: 'var(--dt-form-text-color)',
      labelTooltipColor: 'var(--gray-0)',
      labelTooltipBackground: 'var(--surface-2)',

      toggleBackgroundColorOn: 'var(--primary-color)',
      toggleBackgroundColorOff: 'var(--surface-3)',
      toggleIconSuccess: 'var(--success-color)',
      toggleIconAlert: 'var(--alert-color)',

      spinnerColor1: 'var(--gray-1)',
      spinnerColor2: 'var(--black)',

      tileFontFamily: 'var(--font-family)',
      tileFontSize: '14px;',
      tileFontWeight: '700',
      tileBackground: 'var(--surface-1)',
      tileBorderColor: 'var(--border-color)',
      tileShadow: 'var(--shadow-0)',
      tileHeaderColor: 'var(--primary-color)',
      tileBorderRadius: '10px',
      tileMargin: '0 0 10px 0',
      tileHeaderFontWeight: '300',

      listFontSize: 'var(--font-size)',
      listFontWeight: 'var(--font-weight)',
      listLineHeight: 'var(--line-height)',
      listBorderColor: 'var(--border-color)',
      listActionSectionBackgroundColor: 'var(--primary-color-light-0, #ecf5fc)',
      listheaderBackground: 'var(--dt-tile-background-color, #fefefe)',
      listheaderBackgroundHover:
        'var(--dt-list-hover-background-color, #ecf5fc)',
      listSortArrowColor: 'var(--dt-list-sort-arrow-color, #808080)',
      listSortArrowColorHighlight: 'var(--primary-color, #3f729b)',
      listHeaderColor: 'var(--dt-list-header-color, #0a0a0a)',
      listLinkColor: 'var(--primary-color, #3f729b)',

      modalBackgroundColor: 'var(--dt-modal-background-color, #fff)',
      modalColor: 'var(--dt-modal-color, #000)',
      modalButtonColor: 'var(--text-color-inverse, #fff)',
      modalButtonBackground: 'var(--primary-color, #3f729b)',
      modalButtonOpenerColor: 'var(--text-color-inverse, #fff)',
      modalButtonOpenerBackground: 'var(--primary-color, #3f729b)',

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
      primaryColorLight0: 'hsla(207, 8%, 55%, 1)',
      primaryColorLight1: 'hsla(207, 97%, 36%, 1)',

      gray0: '#666',
      gray1: '#919191',
      black: '#0a0a0a',

      textColor: '#fefefe',
      textColorInverse: '#999',

      surface0: '#212529',
      surface1: '#343a40',
      surface2: '#495057',
      surface3: '#868e96',

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
      textBorderRadius: '0',
      textPlaceholderColor: 'var(--dt-text-placeholder-color)',
      textPlaceholderTextTransform: 'none',
      textPlaceholderFontSize: 'var(--dt-text-placeholder-font-size)',
      textPlaceholderFontWeight: 'var(--dt-text-placeholder-font-weight)',
      textPlaceholderLetterSpacing: 'var(--dt-text-placeholder-letter-spacing)',

      textareaBorderColor: 'var(--dt-form-border-color)',
      textareaBackgroundColor: 'var(--dt-form-background-color)',
      textareaDisabledBackgroundColor:
        'var(--dt-form-disabled-background-color)',
      textareaTextColor: 'var(--dt-form-text-color)',
      textareaTextcolorInverse: 'var(--dt-form-text-color-inverse)',

      labelFontSize: '14px',
      labelFontWeight: '700',
      labelTooltipColor: 'var(--gray-0)',
      labelTooltipBackground: 'var(--surface-2)',

      toggleBackgroundColorOn: 'var(--primary-color)',
      toggleBackgroundColorOff: 'var(--surface-3)',
      toggleIconSuccess: 'var(--success-color)',
      toggleIconAlert: 'var(--alert-color)',

      spinnerColor1: 'var(--gray-1)',
      spinnerColor2: 'var(--black)',

      tileFontFamily: 'var(--font-family)',
      tileFontSize: '14px;',
      tileFontWeight: '700',
      tileBackground: 'var(--surface-1)',
      tileBorderColor: 'var(--border-color)',
      tileShadow: 'var(--shadow-0)',
      tileHeaderColor: 'var(--text-color)',
      tileBorderRadius: '10px',
      tileMargin: '0 0 10px 0',
      tileHeaderFontWeight: '300',

      listFontSize: 'var(--font-size)',
      listFontWeight: 'var(--font-weight)',
      listLineHeight: 'var(--line-height)',
      listBorderColor: 'var(--border-color)',
      listActionSectionBackgroundColor: 'var(--primary-color-light-0, #ecf5fc)',
      listheaderBackground: 'var(--dt-tile-background-color, #fefefe)',
      listheaderBackgroundHover:
        'var(--dt-list-hover-background-color, #ecf5fc)',
      listSortArrowColor: 'var(--dt-list-sort-arrow-color, #808080)',
      listSortArrowColorHighlight: 'var(--primary-color, #3f729b)',
      listHeaderColor: 'var(--dt-list-header-color, #0a0a0a)',
      listLinkColor: 'var(--primary-color, #3f729b)',

      modalBackgroundColor: 'var(--dt-modal-background-color, #fff)',
      modalColor: 'var(--dt-modal-color, #000)',
      modalButtonColor: 'var(--text-color-inverse, #fff)',
      modalButtonBackground: 'var(--primary-color, #3f729b)',
      modalButtonOpenerColor: 'var(--text-color-inverse, #fff)',
      modalButtonOpenerBackground: 'var(--primary-color, #3f729b)',

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
  dim: {
    name: 'dim',
    args: {
      colorScheme: 'dark',
      primaryColor: 'hsla(207, 87%, 40%, 1)',
      primaryColorLight0: 'hsla(207, 20%, 65%, 1)',
      primaryColorLight1: 'hsla(207, 97%, 36%, 1)',

      gray0: '#666',
      gray1: '#919191',
      black: '#0a0a0a',

      textColor: '#fefefe',
      textColorInverse: '#999',

      surface0: 'hsla(207, 9%, 31%, 1)',
      surface1: 'hsla(207, 7%, 56%, 1)',
      surface2: 'hsla(207, 7%, 60%, 1)',
      surface3: 'hsla(207, 4%, 70%, 1)',

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
      textBorderRadius: '0',
      textBoxShadow: 'var(--dt-form-box-shadow)',
      textPlaceholderColor: 'var(--dt-text-placeholder-color)',
      textPlaceholderTextTransform: 'none',
      textPlaceholderFontSize: 'var(--dt-text-placeholder-font-size)',
      textPlaceholderFontWeight: 'var(--dt-text-placeholder-font-weight)',
      textPlaceholderLetterSpacing: 'var(--dt-text-placeholder-letter-spacing)',

      textareaBorderColor: 'var(--dt-form-border-color)',
      textareaBackgroundColor: 'var(--dt-form-background-color)',
      textareaDisabledBackgroundColor:
        'var(--dt-form-disabled-background-color)',
      textareaTextColor: 'var(--dt-form-text-color)',
      textareaTextcolorInverse: 'var(--dt-form-text-color-inverse)',

      labelFontSize: '14px',
      labelFontWeight: '700',
      labelTooltipColor: 'var(--gray-0)',
      labelTooltipBackground: 'var(--surface-2)',

      toggleBackgroundColorOn: 'var(--primary-color)',
      toggleBackgroundColorOff: 'var(--surface-3)',
      toggleIconSuccess: 'var(--success-color)',
      toggleIconAlert: 'var(--alert-color)',

      spinnerColor1: 'var(--gray-1)',
      spinnerColor2: 'var(--black)',

      tileFontFamily: 'var(--font-family)',
      tileFontSize: '14px;',
      tileFontWeight: '700',
      tileBackground: 'var(--surface-1)',
      tileBorderColor: 'var(--border-color)',
      tileShadow: 'var(--shadow-0)',
      tileHeaderColor: 'var(--text-color)',
      tileBorderRadius: '10px',
      tileMargin: '0 0 10px 0',
      tileHeaderFontWeight: '300',

      listFontSize: 'var(--font-size)',
      listFontWeight: 'var(--font-weight)',
      listLineHeight: 'var(--line-height)',
      listBorderColor: 'var(--border-color)',
      listActionSectionBackgroundColor: 'var(--primary-color-light-0, #ecf5fc)',
      listheaderBackground: 'var(--dt-tile-background-color, #fefefe)',
      listheaderBackgroundHover:
        'var(--dt-list-hover-background-color, #ecf5fc)',
      listSortArrowColor: 'var(--dt-list-sort-arrow-color, #808080)',
      listSortArrowColorHighlight: 'var(--primary-color, #3f729b)',
      listHeaderColor: 'var(--dt-list-header-color, #0a0a0a)',
      listLinkColor: 'var(--primary-color, #3f729b)',

      modalBackgroundColor: 'var(--dt-modal-background-color, #fff)',
      modalColor: 'var(--dt-modal-color, #000)',
      modalButtonColor: 'var(--text-color-inverse, #fff)',
      modalButtonBackground: 'var(--primary-color, #3f729b)',
      modalButtonOpenerColor: 'var(--text-color-inverse, #fff)',
      modalButtonOpenerBackground: 'var(--primary-color, #3f729b)',

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

export const contexts = [
  'primary',
  'alert',
  'caution',
  'success',
  'inactive',
  'disabled',
];

export const argTypes = {
  theme: {
    control: 'select',
    options: Object.keys(themes),
    defaultValue: ['default'],
  },
  lang: { constrol: 'text' },
  dir: { control: 'text' },
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
  textBorderRadius: { control: 'text' },
  textBoxShadow: { control: 'text' },
  textPlaceholderColor: { control: 'color' },
  textPlaceholderTextTransform: { control: 'text' },
  textPlaceholderFontSize: { control: 'text' },
  textPlaceholderFontWeight: { control: 'text' },
  textPlaceholderLetterSpacing: { control: 'text' },

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

  toggleBackgroundColorOn: { control: 'color' },
  toggleBackgroundColorOff: { control: 'color' },
  toggleIconSuccess: { control: 'color' },
  toggleIconAlert: { control: 'color' },

  tileFontFamily: { control: 'color' },
  tileFontSize: { control: 'text' },
  tileFontWeight: { control: 'text' },
  tileBackground: { control: 'color' },
  tileBorderColor: { control: 'color' },
  tileShadow: { control: 'color' },
  tileHeaderColor: { control: 'color' },
  tileBorderRadius: '10px',
  tileMargin: '0 0 10px 0',
  tileHeaderFontWeight: '300',

  listFontSize: 'var(--font-size)',
  listFontWeight: 'var(--font-weight)',
  listLineHeight: 'var(--line-height)',
  listBorderColor: 'var(--border-color)',
  listActionSectionBackgroundColor: 'var(--primary-color-light-0, #ecf5fc)',
  listheaderBackground: 'var(--dt-tile-background-color, #fefefe)',
  listheaderBackgroundHover: 'var(--dt-list-hover-background-color, #ecf5fc)',
  listSortArrowColor: 'var(--dt-list-sort-arrow-color, #808080)',
  listSortArrowColorHighlight: 'var(--primary-color, #3f729b)',
  listHeaderColor: 'var(--dt-list-header-color, #0a0a0a)',
  listLinkColor: 'var(--primary-color, #3f729b))',

  alertTextColorLight: '#fff',
  alertTextColorDark: '#000',
  alertTextColor: { control: 'color' },
  alertFontFamily: { control: 'color' },
  alertFontSize: { control: 'text' },
  alertFontWeight: { control: 'text' },
  alertBackground: { control: 'color' },
  alertBorderColor: { control: 'color' },
  alertShadow: { control: 'color' },

  modalBackgroundColor: { control: 'color' },
  modalColor: { control: 'color' },
  modalButtonColor: { control: 'color' },
  modalButtonBackground: { control: 'color' },
  modalButtonOpenerColor: { control: 'color' },
  modalButtonOpenerBackground: { control: 'color' },
};

function getArg(storyArgs, argName) {
  if (storyArgs[argName]) {
    return storyArgs[argName];
  }

  if (storyArgs.theme) {
    return themes[storyArgs.theme].args[argName];
  }
  // storyArgs.theme = 'default';
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
    --dt-form-disabled-background-color: ${getArg(
      storyArgs,
      'formDisabledBackgroundColor'
    )};
    --dt-form-text-color: ${getArg(storyArgs, 'formTextColor')};
    --dt-form-text-color-inverse: ${getArg(storyArgs, 'formTextColorInverse')};
    --dt-form-text-border-color: ${getArg(storyArgs, 'formBorderColor')};

    --dt-checkmark-width: ${getArg(storyArgs, 'checkmarkWidth')};
    --dt-checkmark-color: ${getArg(storyArgs, 'checkmarkColor')};

    --dt-connection-icon-fill: ${getArg(storyArgs, 'connectionIconFill')};
    --dt-multi-select-text-color: ${getArg(storyArgs, 'multiSelectTextColor')};
    --dt-multi-select-background-color: ${getArg(
      storyArgs,
      'multiSelectBackgroundColor'
    )};
    --dt-multi-select-tag-border-color: ${getArg(
      storyArgs,
      'multiSelectTagBorderColor'
    )};
    --dt-multi-select-tag-background-color: ${getArg(
      storyArgs,
      'multiSelectTagBackgroundColor'
    )};
    --dt-multi-select-option-hover-background: ${getArg(
      storyArgs,
      'multiSelectOptionHoverBackground'
    )};

    --dt-single-select-text-color: ${getArg(
      storyArgs,
      'singleSelectTextColor'
    )};
    --dt-single-select-text-color-inverse: ${getArg(
      storyArgs,
      'singleSelectTextColorInverse'
    )};

    --dt-text-color: ${getArg(storyArgs, 'dttextColor')};
    --dt-text-color-inverse: ${getArg(storyArgs, 'dttextColorInverse')};
    --dt-text-color-mid: ${getArg(storyArgs, 'dttextColorMid')};
    --dt-text-background-color: ${getArg(storyArgs, 'textBackgroundColor')};
    --dt-text-border-color: ${getArg(storyArgs, 'textBorderColor')};
    --dt-text-border-color-alert: ${getArg(storyArgs, 'textBorderColorAlert')};
    --dt-text-disabled-background-color: ${getArg(
      storyArgs,
      'textDisabledBackgroundColor'
    )};
    --dt-text-border-radius: ${getArg(storyArgs, 'textBorderRadius')};
    --dt-text-box-shadow: ${getArg(storyArgs, 'textBoxShadow')};
    --dt-text-placeholder-color: ${getArg(storyArgs, 'textPlaceholderColor')};
    --dt-text-placeholder-text-transform: ${getArg(
      storyArgs,
      'textPlaceholderTextTransform'
    )};
    --dt-text-placeholder-font-size: ${getArg(
      storyArgs,
      'textPlaceholderFontSize'
    )};
    --dt-text-placeholder-font-weight: ${getArg(
      storyArgs,
      'textPlaceholderFontWeight'
    )};
    --dt-text-placeholder-letter-spacing: ${getArg(
      storyArgs,
      'textPlaceholderLetterSpacing'
    )};

    --dt-textarea-border-color: ${getArg(storyArgs, 'textareaBorderColor')};
    --dt-textarea-background-color: ${getArg(
      storyArgs,
      'textareaBackgroundColor'
    )};
    --dt-textarea-disabled-background-color: ${getArg(
      storyArgs,
      'textareaDisabledBackgroundColor'
    )};
    --dt-textarea-text-color: ${getArg(storyArgs, 'textareaTextColor')};
    --dt-textarea-text-color-inverse: ${getArg(
      storyArgs,
      'textareaTextcolorInverse'
    )};

    --dt-label-font-size: ${getArg(storyArgs, 'labelFontSize')};
    --dt-label-font-weight: ${getArg(storyArgs, 'labelFontWeight')};
    --dt-label-color: ${getArg(storyArgs, 'labelTextColor')};
    --dt-label-tooltip-color: ${getArg(storyArgs, 'labelTooltipColor')};
    --dt-label-tooltip-background: ${getArg(
      storyArgs,
      'labelTooltipBackground'
    )};

    --dt-spinner-color-1: ${getArg(storyArgs, 'spinnerColor1')};
    --dt-spinner-color-2: ${getArg(storyArgs, 'spinnerColor2')};
    --dt-tile-font-family: ${getArg(storyArgs, 'tileFontFamily')};

    --dt-toggle-background-color-on: ${getArg(
      storyArgs,
      'toggleBackgroundColorOn'
    )};
    --dt-toggle-background-color-off: ${getArg(
      storyArgs,
      'toggleBackgroundColorOff'
    )};
    --dt-toggle-border-color: ${getArg(storyArgs, 'toggleBorderColor')};
    --dt-toggle-icon-success: ${getArg(storyArgs, 'toggleIconSuccess')};
    --dt-toggle-icon-alert: ${getArg(storyArgs, 'toggleIconAlert')};


    --dt-tile-font-size: ${getArg(storyArgs, 'tileFontSize')};
    --dt-tile-font-weight: ${getArg(storyArgs, 'tileFontWeight')};
    --dt-tile-background-color: ${getArg(storyArgs, 'tileBackground')};
    --dt-tile-border-color: ${getArg(storyArgs, 'tileBorderColor')};
    --dt-tile-shadow: ${getArg(storyArgs, 'tileShadow')};
    --dt-tile-header-color: ${getArg(storyArgs, 'tileHeaderColor')};
    --dt-tile-border-radius: ${getArg(storyArgs, '10px')};
    --dt-tile-margin: ${getArg(storyArgs, 'tileMargin')};
    --dt-tile-header-font-weight: ${getArg(storyArgs, 'tileHeaderFontWeight')};

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

    --dt-list-font-size: ${getArg(storyArgs, 'listFontSize')};
    --dt-list-font-weight: ${getArg(storyArgs, 'listFontWeight')};
    --dt-list-line-height: ${getArg(storyArgs, 'listLineHeight')};
    --dt-list-border-color: ${getArg(storyArgs, 'listBorderColor')};
    --dt-list-action-section-background-color: ${getArg(
      storyArgs,
      'listActionSectionBackgroundColor'
    )};
    --dt-list-header-background-color: ${getArg(
      storyArgs,
      'listHeaderBackgroundColor'
    )};
    --dt-list-header-color: ${getArg(storyArgs, 'listHeaderColor')};
    --dt-list-background-color: ${getArg(storyArgs, 'listheaderBackground')};
    --dt-list-hover-background-color: ${getArg(
      storyArgs,
      'listheaderHoverBackground'
    )};
    --dt-list-sort-arrow-color: ${getArg(storyArgs, 'listSortArrowColor')};
    --dt-list-sort-arrow-color-highlight: ${getArg(
      storyArgs,
      'listSortArrowColorHighlight'
    )};
    --dt-list-header-color: ${getArg(storyArgs, 'listHeaderColor')};
    --dt-list-link-color: ${getArg(storyArgs, 'listLinkColor')};

    --dt-modal-background-color: ${getArg(storyArgs, 'modalBackgroundColor')};
    --dt-modal-color: ${getArg(storyArgs, 'modalColor')};
    --dt-modal-button-color: ${getArg(storyArgs, 'modalButtonColor')};
    --dt-modal-button-background: ${getArg(storyArgs, 'modalButtonBackground')};
    --dt-modal-button-opener-color: ${getArg(storyArgs, 'modalButtonOpenerColor')};
    --dt-modal-button-opener-background: ${getArg(storyArgs, 'modalButtonOpenerBackground')};
  }
  `;
}
