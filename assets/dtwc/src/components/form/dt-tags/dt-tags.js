import { html } from 'lit';
import { DtMultiSelect } from '../dt-multi-select/dt-multi-select.js';

export class DtTags extends DtMultiSelect {
  static get properties() {
    return {
      ...super.properties,
      allowAdd: { type: Boolean },
      onload: { type: String },
    };
  }

  firstUpdated() {
    this._filterOptions();
  }

  _clickOption(e) {
    if (e.target && e.target.value) {
      this._select({
        id: e.target.value,
        label: e.target.dataset?.label,
      });

      this._filterOptions();
    }
  }

  _clickAddNew(e) {
    if (e.target) {
      this._select({
        id: '',
        label: e.target.dataset?.label,
      });
    }
  }

  _remove(e) {
    if (e.target && e.target.dataset && e.target.dataset.value) {
      this.value = (this.value || []).filter(i => i.id !== e.target.dataset.value);
    }
  }

  _keyboardSelectOption() {
    if (this.activeIndex > -1) {
      if (this.activeIndex + 1 > this.filteredOptions.length) {
        this._select({
          id: '',
          label: this.query,
        });
      } else {
        this._select(this.filteredOptions[this.activeIndex]);
      }

      this._filterOptions();
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
    const selectedValues = (this.value || []).map(v => v?.id);

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
            console.log(error);
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
          class="${this.activeIndex > -1 &&
          this.activeIndex >= this.filteredOptions.length
            ? 'active'
            : null}"
        >
          Add "${this.query}"
        </button>
      </li>`);
    }
    return optionsMarkup;
  }

  _renderSelectedOptions() {
    return (this.value || []).map(
      opt => html`
        <div class="selected-option">
          <span>${opt.label}</span>
          <button @click="${this._remove}" data-value="${opt.id}">x</button>
        </div>
      `
    );
  }
}

window.customElements.define('dt-tags', DtTags);
