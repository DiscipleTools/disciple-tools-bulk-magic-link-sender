import { css, html } from 'lit';
import { msg } from '@lit/localize';
import { DtMultiSelect } from '../dt-multi-select/dt-multi-select.js';

export class DtTags extends DtMultiSelect {
  static get properties() {
    return {
      ...super.properties,
      allowAdd: { type: Boolean },
      onload: { type: String },
    };
  }

  static get styles() {
    return [
      ...super.styles,
      css`
        .selected-option a,
        .selected-option a:active,
        .selected-option a:visited {
          text-decoration: none;
          color: var(--primary-color, #3f729b);
        }
      `,
    ];
  }

  _clickOption(e) {
    if (e.target && e.target.value) {
      const id = e.target.value;
      const option = this.filteredOptions.reduce((result, opt) => {
        if (!result && opt.id === id) {
          return opt;
        }
        return result;
      }, null);
      this._select(option);
    }
  }

  _clickAddNew(e) {
    if (e.target) {
      this._select({
        id: '',
        label: e.target.dataset?.label,
        isNew: true,
      });
    }
  }

  _remove(e) {
    if (e.target && e.target.dataset && e.target.dataset.value) {
      const event = new CustomEvent('change', {
        detail: {
          field: this.name,
          oldValue: this.value,
        },
      });
      this.value = (this.value || []).map(i => {
        const val = {
          ...i,
        };
        if (i.id === e.target.dataset.value) {
          val.delete = true;
        }
        return val;
      });
      event.detail.newValue = this.value;

      // dispatch event for use with addEventListener from javascript
      this.dispatchEvent(event);

      // If option was de-selected while list was open, re-focus input
      if (this.open) {
        this.shadowRoot.querySelector('input').focus();
      }
    }
  }

  _keyboardSelectOption() {
    if (this.activeIndex > -1) {
      if (this.activeIndex + 1 > this.filteredOptions.length) {
        this._select({
          id: '',
          label: this.query,
          isNew: true,
        });
      } else {
        this._select(this.filteredOptions[this.activeIndex]);
      }
    }
  }

  _listHighlightNext() {
    this.activeIndex = Math.min(
      this.filteredOptions.length, // allow 1 more than the list length
      this.activeIndex + 1
    );
  }

  /**
   * Filter to options that:
   *   1: are not selected
   *   2: match the search query
   * @private
   */
  _filterOptions() {
    const selectedValues = (this.value || [])
      .filter(i => !i.delete)
      .map(v => v?.id);

    if (this.options?.length) {
      this.filteredOptions = (this.options || []).filter(
        opt =>
          !selectedValues.includes(opt.id) &&
          (!this.query ||
            opt.label
              .toLocaleLowerCase()
              .includes(this.query.toLocaleLowerCase()))
      );
    } else {
      this.loading = true;
      this.filteredOptions = [];

      // need to fetch data via API request
      const self = this;
      const event = new CustomEvent('load', {
        bubbles: true,
        detail: {
          field: this.name,
          query: this.query,
          onSuccess: result => {
            self.loading = false;

            // filter out selected values from list
            self.filteredOptions = result.filter(
              opt => !selectedValues.includes(opt.id)
            );
          },
          onError: error => {
            console.warn(error);
            self.loading = false;
          },
        },
      });
      this.dispatchEvent(event);
    }
    return this.filteredOptions;
  }

  _renderOptions() {
    let optionsMarkup = super._renderOptions();

    if (this.allowAdd && this.query) {
      if (!Array.isArray(optionsMarkup)) {
        optionsMarkup = [optionsMarkup];
      }
      optionsMarkup.push(html`<li>
        <button
          data-label="${this.query}"
          @click="${this._clickAddNew}"
          @touchstart="${this._touchStart}"
          @touchmove="${this._touchMove}"
          @touchend="${this._touchEnd}"
          class="${this.activeIndex > -1 &&
          this.activeIndex >= this.filteredOptions.length
            ? 'active'
            : ''}"
        >
          ${msg('Add')} "${this.query}"
        </button>
      </li>`);
    }
    return optionsMarkup;
  }

  _renderSelectedOptions() {
    return (this.value || [])
      .filter(i => !i.delete)
      .map(
        opt => html`
          <div class="selected-option">
            <a
              href="${opt.link}"
              ?disabled="${this.disabled}"
              alt="${opt.status ? opt.status.label : opt.label}"
              >${opt.label}</a
            >
            <button
              @click="${this._remove}"
              ?disabled="${this.disabled}"
              data-value="${opt.id}"
            >
              x
            </button>
          </div>
        `
      );
  }
}

window.customElements.define('dt-tags', DtTags);
