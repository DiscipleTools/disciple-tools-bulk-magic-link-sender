import { html, css, LitElement } from 'lit';
import '../../icons/dt-spinner.js';
import '../../icons/dt-checkmark.js';

export class DtSingleSelect extends LitElement {
  static get styles() {
    return css`
      :host {
        position: relative;
        --borderWidth: 3px;
        --borderColor: #78b13f;
      }

      select {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-color: #fefefe;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' version='1.1' width='32' height='24' viewBox='0 0 32 24'><polygon points='0,0 32,0 16,24' style='fill: rgb%28138, 138, 138%29'></polygon></svg>");
        background-origin: content-box;
        background-position: right -1.0666666667rem center;
        background-repeat: no-repeat;
        background-size: 9px 6px;
        border: 1px solid var(--dt-component-border-color, #cacaca);
        border-radius: 0;
        color: #0a0a0a;
        font-family: Helvetica, Arial, sans-serif;
        font-size: 1rem;
        font-weight: 300;
        height: 2.5rem;
        line-height: 1.5;
        margin: 0 0 1.0666666667rem;
        padding: 0.5333333333rem 1.6rem 0.5333333333rem 0.5333333333rem;
        -webkit-transition: border-color 0.25s ease-in-out,
          -webkit-box-shadow 0.5s;
        transition: border-color 0.25s ease-in-out, -webkit-box-shadow 0.5s;
        transition: box-shadow 0.5s, border-color 0.25s ease-in-out;
        transition: box-shadow 0.5s, border-color 0.25s ease-in-out,
          -webkit-box-shadow 0.5s;

        -webkit-box-sizing: border-box;
        box-sizing: border-box;
        width: 100%;
        text-transform: none;
      }
      select.color-select {
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' version='1.1' width='32' height='24' viewBox='0 0 32 24'><polygon points='0,0 32,0 16,24' style='fill: white'></polygon></svg>");
        border: none;
        border-radius: 10px;
        color: #fff;
        font-weight: 700;
        text-shadow: rgb(0 0 0 / 45%) 0 0 6px;
      }

      .icon-overlay {
        position: absolute;
        right: 2rem;
        top: 0;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
      }
    `;
  }

  static get properties() {
    return {
      name: { type: String },
      placeholder: { type: String },
      options: { type: Array },
      value: {
        type: String,
        reflect: true,
      },
      color: {
        type: String,
        state: true,
      },
      loading: { type: Boolean },
      saved: { type: Boolean },
      onchange: { type: String },
    };
  }

  // constructor() {
  //   super();
  // }

  /**
   * Find the color for the currently selected value
   */
  updateColor() {
    if (this.value && this.options) {
      const options = this.options.filter(opt => opt.id === this.value);
      if (options && options.length) {
        this.color = options[0].color;
      }
    }
  }

  isColorSelect() {
    return (this.options || []).reduce((isColor, option) => {
      return isColor || option.color;
    }, false);
  }
  willUpdate(changedProperties) {
    if (changedProperties.has('value')) {
      this.updateColor();
    }
  }

  _change(e) {
    // Create custom event with new/old values to pass to onchange function
    const event = new CustomEvent('change', {
      detail: {
        oldValue: this.value,
        newValue: e.target.value,
      },
    });

    // update value in this component
    this.value = e.target.value;

    // dispatch event for use with addEventListener from javascript
    this.dispatchEvent(event);

    // check for `onchange` html attribute to trigger event from attribute
    // todo: test across browsers, `dispatchEvent` seems to work for html `onchange` in Chrome on MacOS
    // if (this.onchange) {
    //   // eslint-disable-next-line no-new-func
    //   const fn = new Function('event', this.onchange);
    //   // eval(this.onchange + '()');
    //   fn(event);
    // }
  }

  render() {
    return html`
      <select
        name="${this.name}"
        @change="${this._change}"
        class="${this.isColorSelect() ? 'color-select' : null}"
        style="background-color: ${this.color};"
        ?disabled="${this.loading}"
      >
        <option disabled selected hidden>${this.placeholder}</option>

        ${this.options &&
        this.options.map(
          i => html`
            <option value="${i.id}" ?selected="${i.id === this.value}">
              ${i.label}
            </option>
          `
        )}
      </select>
      ${this.loading
        ? html`<dt-spinner class="icon-overlay"></dt-spinner>`
        : null}
      ${this.saved ? html`<dt-checkmark class="icon-overlay"></dt-checkmark>` : null}
    `;
  }
}

window.customElements.define('dt-single-select', DtSingleSelect);
