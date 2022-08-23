import { html, css } from 'lit';
import { classMap } from 'lit/directives/class-map.js';
import DtFormBase from '../dt-form-base.js';
import '../../icons/dt-spinner.js';
import '../../icons/dt-checkmark.js';
import '../../icons/dt-exclamation-circle.js';

export class DtTextField extends DtFormBase {
  static get styles() {
    return [
      ...super.styles,
      css`
      input {
        color: var(--dt-form-text-color, #000);
        appearance: none;
        background-color: var(--dt-text-background-color, #fefefe);
        border: 1px solid var(--dt-text-border-color, #fefefe);
        border-radius: 0;
        box-shadow: inset 0 1px 2px hsl(0deg 0% 4% / 10%);
        box-sizing: border-box;
        display: block;
        font-family: inherit;
        font-size: 1rem;
        font-weight: 300;
        height: 2.5rem;
        line-height: 1.5;
        margin: 0 0 1.0666666667rem;
        padding: 0.5333333333rem;
        transition: box-shadow .5s, border-color .25s ease-in-out;
        width: 100%;
      }
      input:disabled, input[readonly], textarea:disabled, textarea[readonly] {
        background-color: var(--dt-text-disabled-background-color, #e6e6e6);
        cursor: not-allowed;
      }
      input:focus-within, input:focus-visible { outline: none; }
      
      input.invalid {
        border-color: var(--dt-text-border-color-alert);
      }
    `];
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
      disabled: { type: Boolean },
      onchange: { type: String },
    };
  }

  onChange(e) {
    const event = new CustomEvent('change', {
      detail: {
        field: this.name,
        oldValue: this.value,
        newValue: e.target.value,
      },
    });

    this.value = e.target.value;

    this._setFormValue(this.value);

    this.dispatchEvent(event);
  }

  _validateRequired() {
    const { value } = this;
    const input = this.shadowRoot.querySelector('input');
    if (value === '' && this.required) {
      this.invalid = true;
      this.internals.setValidity({
        valueMissing: true
      }, this.requiredMessage || 'This field is required', input);
    } else {
      this.invalid = false;
      this.internals.setValidity({});
    }
  }

  get classes() {
    const classes = {
      'text-input': true,
      'invalid': this.touched && this.invalid,
    }
    return classes
  }

  render() {
    return html`
      ${this.labelTemplate()}

      <div class="input-group">
        <input
          id="${this.id}"
          name="${this.name}"
          aria-label="${this.label}"
          type="text"
          ?disabled=${this.disabled}
          ?required=${this.required}
          class="${classMap(this.classes)}"
          .value="${this.value}"
          @change=${this.onChange}
          novalidate
        />
        
        ${this.touched && this.invalid ? html`<dt-exclamation-circle class="icon-overlay alert"></dt-exclamation-circle>` : null}
        ${this.loading ? html`<dt-spinner class="icon-overlay"></dt-spinner>` : null}
        ${this.saved ? html`<dt-checkmark class="icon-overlay success"></dt-checkmark>` : null}
      </div>
    `;
  }
}

window.customElements.define('dt-text', DtTextField);
