import { css, html } from 'lit';
import { styleMap } from 'lit/directives/style-map.js';
import { map } from 'lit/directives/map.js';
import { DtTags } from '../dt-tags/dt-tags.js';

export class DtLocation extends DtTags {
  static get properties() {
    return {
      ...super.properties,
      filters: { type: Array },
      mapboxKey: { type: String },
      dtMapbox: { type: Object },
    };
  }

  static get styles() {
    return [
      ...super.styles,
      css`
        .input-group {
          display: flex;
        }

        .field-container {
          position: relative;
        }

        select {
          border: 1px solid var(--dt-form-border-color, #cacaca);
          outline: 0;
        }
        .selected-option > *:first-child {
          max-width: calc(
            var(--container-width) - var(--select-width) -
              var(--container-padding) - var(--option-padding) -
              var(--option-button) - 8px
          );
        }
      `,
    ];
  }

  updated() {
    super.updated();

    // set variable with width of container for truncating selected options via CSS
    const container = this.shadowRoot.querySelector('.input-group');
    const currentValue = container.style.getPropertyValue('--select-width');
    if (!currentValue) {
      const select = this.shadowRoot.querySelector('select');
      container.style.setProperty('--select-width', `${select.clientWidth}px`);
    }
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
      const selectEl = this.shadowRoot.querySelector('select');
      const event = new CustomEvent('load', {
        bubbles: true,
        detail: {
          field: this.name,
          query: this.query,
          filter: selectEl?.value,
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

  render() {
    const optionListStyles = {
      display: this.open ? 'block' : 'none',
      top: `${this.containerHeight}px`,
    };
    return this.mapboxKey
      ? html` ${this.labelTemplate()}
          <div id="mapbox-wrapper">
            <div
              id="mapbox-autocomplete"
              class="mapbox-autocomplete input-group"
              data-autosubmit="true"
              data-add-address="true"
            >
              <input
                id="mapbox-search"
                type="text"
                name="mapbox_search"
                class="input-group-field"
                autocomplete="off"
                dir="auto"
                placeholder="Search Location"
              />
              <div class="input-group-button">
                <button
                  id="mapbox-spinner-button"
                  class="button hollow"
                  style="display:none;border-color:lightgrey;"
                >
                  <span
                    class=""
                    style="border-radius: 50%;width: 24px;height: 24px;border: 0.25rem solid lightgrey;border-top-color: black;animation: spin 1s infinite linear;display: inline-block;"
                  ></span>
                </button>
                <button
                  id="mapbox-clear-autocomplete"
                  class="button alert input-height delete-button-style mapbox-delete-button"
                  type="button"
                  title="Clear"
                  style="display:none;"
                >
                  ×
                </button>
              </div>
              <div
                id="mapbox-autocomplete-list"
                class="mapbox-autocomplete-items"
              ></div>
            </div>
            <div id="location-grid-meta-results"></div>
          </div>`
      : html`
          ${this.labelTemplate()}

          <div class="input-group ${this.disabled ? 'disabled' : ''}">
            <div
              class="field-container"
              @click="${this._focusInput}"
              @keydown="${this._focusInput}"
            >
              ${this._renderSelectedOptions()}
              <input
                type="text"
                placeholder="${this.placeholder}"
                @focusin="${this._inputFocusIn}"
                @blur="${this._inputFocusOut}"
                @keydown="${this._inputKeyDown}"
                @keyup="${this._inputKeyUp}"
                ?disabled="${this.disabled}"
              />

              ${this.loading
                ? html`<dt-spinner class="icon-overlay"></dt-spinner>`
                : null}
              ${this.saved
                ? html`<dt-checkmark
                    class="icon-overlay success"
                  ></dt-checkmark>`
                : null}
            </div>
            <select class="filter-list" ?disabled="${this.disabled}">
              ${map(
                this.filters,
                f => html`<option value="${f.id}">${f.label}</option>`
              )}
            </select>
            <ul class="option-list" style=${styleMap(optionListStyles)}>
              ${this._renderOptions()}
            </ul>
          </div>
        `;
  }
}

window.customElements.define('dt-location', DtLocation);
