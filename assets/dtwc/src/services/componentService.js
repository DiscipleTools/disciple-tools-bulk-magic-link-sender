import ApiService from './apiService.js';

export default class ComponentService {
  constructor(postType, postId, nonce, apiRoot = '') {
    this.postType = postType;
    this.postId = postId;
    this.nonce = nonce;
    this.apiRoot = `${apiRoot}/`.replace('//', '/'); // ensure it ends with /

    this.api = new ApiService(this.nonce, this.apiRoot);

    this.autoSaveComponents = [
      'dt-connection',
      'dt-date',
      'dt-location',
      'dt-multi-select',
      'dt-number',
      'dt-single-select',
      'dt-tags',
      'dt-text',
      'dt-textarea',
      'dt-toggle',
    ];
  }

  /**
   * Enable auto-save feature for all components on the current page
   * @param {string} [selector] (Optional) Override default selector
   */
  enableAutoSave(selector) {
    const allElements = document.querySelectorAll(
      selector || this.autoSaveComponents.join(',')
    );
    if (allElements) {
      allElements.forEach(el =>
        el.addEventListener('change', this.handleChangeEvent)
      );
    }
  }

  async handleChangeEvent(event) {
    const details = event.detail;
    if (details) {
      const { field, newValue } = details;
      const component = event.target.tagName.toLowerCase();
      const apiValue = ComponentService.convertValue(component, newValue);

      event.target.setAttribute('loading', true);

      // Update post via API
      try {
        await this.api.updatePost(this.postType, this.postId, {
          [field]: apiValue,
        });

        event.target.removeAttribute('loading');
        event.target.setAttribute('saved', true);
      } catch (ex) {
        event.target.removeAttribute('loading');
        event.target.setAttribute('invalid', true); // this isn't hooked up yet
      }
    }
  }

  /**
   * Convert value returned from a component into what is expected by DT API
   * @param {string} component Tag name of component. E.g. dt-text
   * @param {mixed} value
   * @returns {mixed}
   */
  static convertValue(component, value) {
    let returnValue = value;

    // Convert component value format into what the API expects
    if (value) {
      switch (component) {
        case 'dt-toggle':
          if (typeof value === 'string') {
            returnValue = value.toLowerCase() === 'true';
          }
          break;

        case 'dt-multi-select':
          if (typeof value === 'string') {
            returnValue = [value];
          }
          returnValue = {
            values: returnValue.map(itemId => {
              const ret = {
                value: itemId.replace('-', ''),
              };
              if (itemId.startsWith('-')) {
                ret.delete = true;
              }
              return ret;
            }),
            force_values: false,
          };
          break;

        case 'dt-connection':
        case 'dt-location':
        case 'dt-tags':
          if (typeof value === 'string') {
            returnValue = [
              {
                id: value,
              },
            ];
          }
          returnValue = {
            values: returnValue.map(item => {
              const ret = {
                value: item.id,
              };
              if (item.delete) {
                ret.delete = item.delete;
              }
              return ret;
            }),
            force_values: false,
          };
          break;

        default:
          break;
      }
    }

    return returnValue;
  }
}
