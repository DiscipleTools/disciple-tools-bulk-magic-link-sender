import { css, html, LitElement } from 'lit';
import 'element-internals-polyfill';
import './dt-label/dt-label.js';

export default class DtFormBase extends LitElement {
  static get formAssociated() {
    return true;
  }

  static get styles() {
    return [css`
    .input-group {
      position: relative;
    }
    .input-group.disabled {
      background-color: var(--disabled-color);
    }
    
    /* === Inline Icons === */
      .icon-overlay {
        position: absolute;
        inset-inline-end: 1rem;
        top: 0;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
      }
      
      .icon-overlay.alert {
        color: var(--alert-color);
      }
      .icon-overlay.success {
        color: var(--success-color);
      }
    `];
  }
  static get properties() {
    return {
      label: { type: String },
      icon: { type: String },
      private: { type: Boolean },
      privateLabel: { type: String },
      disabled: { type: Boolean },
      required: { type: Boolean },
      requiredMessage: { type: String },
      touched: {
        type: Boolean,
        state: true,
      },
      invalid: {
        type: Boolean,
        state: true,
      },
      loading: { type: Boolean },
      saved: { type: Boolean },
    };
  }

  constructor() {
    super();
    this.touched = false;
    this.invalid = false;
    this.internals = this.attachInternals();

    // catch oninvalid event (when validation is triggered from form submit)
    // and set touched=true so that styles are shown
    this.addEventListener('invalid', (e) => {
      this.touched = true;
      this._validateRequired();
    });
  }

  firstUpdated(...args) {
    super.firstUpdated(...args);

    // set initial form value
    this.internals.setFormValue(this.value);
    this._validateRequired();
  }

  _setFormValue(value) {
    this.internals.setFormValue(value);
    this._validateRequired();
    this.touched = true;
  }

  /**
   * Can/should be overriden by each component to implement logic for checking if a value is entered/selected
   * @private
   */
  _validateRequired() {
    // const { value } = this;
    // const input = this.shadowRoot.querySelector('input');
    // if (value === '' && this.required) {
    //   this.invalid = true;
    //   this.internals.setValidity({
    //     valueMissing: true
    //   }, this.requiredMessage || 'This field is required', input);
    // } else {
    //   this.invalid = false;
    //   this.internals.setValidity({});
    // }
  }

  labelTemplate() {
    return html`
      <dt-label
        ?private=${this.private}
        privateLabel="${this.privateLabel}"
        icon="${this.icon}"
      >
        ${!this.icon ? html`<slot name="icon-start" slot="icon-start"></slot>` : null}
        ${this.label}
      </dt-label>
    `;
  }

  render() {
    return html`
      ${this.labelTemplate()}
      <slot></slot>
    `;
  }
}
