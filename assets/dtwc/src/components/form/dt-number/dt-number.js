import { html, css } from 'lit';
import { ifDefined } from 'lit/directives/if-defined.js';
import DtFormBase from '../dt-form-base.js';
import ApiService from '../../../services/apiService.js';

export class DtNumberField extends DtFormBase {
  static get styles() {
    return [
      ...super.styles,
      css`
        input {
          color: var(--dt-form-text-color, #000);
          appearance: none;
          background-color: var(--dt-form-background-color, #fff);
          border: 1px solid var(--dt-form-border-color, #ccc);
          border-radius: 0;
          box-shadow: var(
            --dt-form-input-box-shadow,
            inset 0 1px 2px hsl(0deg 0% 4% / 10%)
          );
          box-sizing: border-box;
          display: block;
          font-family: inherit;
          font-size: 1rem;
          font-weight: 300;
          height: 2.5rem;
          line-height: 1.5;
          margin: 0 0 1.0666666667rem;
          padding: var(--dt-form-padding, 0.5333333333rem);
          transition: var(
            --dt-form-transition,
            box-shadow 0.5s,
            border-color 0.25s ease-in-out
          );
        }
        input:disabled,
        input[readonly],
        textarea:disabled,
        textarea[readonly] {
          background-color: var(--dt-form-disabled-background-color, #e6e6e6);
          cursor: not-allowed;
        }
      `,
    ];
  }

  static get properties() {
    return {
      ...super.properties,
      id: { type: String },
      name: { type: String },
      value: {
        type: String,
        reflect: true,
      },
      min: { type: Number },
      max: { type: Number },
      loading: { type: Boolean },
      saved: { type: Boolean },
      onchange: { type: String },
    };
  }

  _checkValue(value) {
    if (value < this.min || value > this.max) {
      return false;
    }

    return true;
  }

  async onChange(e) {
    if (this._checkValue(e.target.value)) {
      const event = new CustomEvent('change', {
        detail: {
          field: this.name,
          oldValue: this.value,
          newValue: e.target.value,
        },
        bubbles: true,
        composed: true
      });

      this.value = e.target.value;
      this.dispatchEvent(event);

      this.api = new ApiService(this.nonce, this.apiRoot);


      const response = await this.api.updatePost(this.postType, this.postID, {
        [this.name]: e.target.value,
      });

      this.saved = true;

    } else {
      e.currentTarget.value = '';
    }
  }

  render() {
    return html`
      ${this.labelTemplate()}

      <input
        id="${this.id}"
        name="${this.name}"
        aria-label="${this.label}"
        type="number"
        ?disabled=${this.disabled}
        class="text-input"
        .value="${this.value}"
        min="${ifDefined(this.min)}"
        max="${ifDefined(this.max)}"
        @change=${this.onChange}
      />
    `;
  }
}

window.customElements.define('dt-number', DtNumberField);
