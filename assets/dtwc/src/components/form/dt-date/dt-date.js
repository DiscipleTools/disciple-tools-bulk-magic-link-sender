import { html, css } from 'lit';
import DtFormBase from '../dt-form-base.js';

export class DtDateField extends DtFormBase {
  static get styles() {
    return css`
      input {
        color: var(--dt-form-text-color, #000);
        appearance: none;
        background-color: var(--dt-form-background-color, #cecece);
        border: 1px solid var(--dt-form-border-color, #cacaca);
        border-radius: 0;
        box-shadow: inset 0 1px 2px hsl(0deg 0% 4% / 10%);
        box-sizing: border-box;
        display: inline-flex;
        font-family: inherit;
        font-size: 1rem;
        font-weight: 300;
        height: 2.5rem;
        line-height: 1.5;
        padding: 0.5333333333rem;
        transition: box-shadow .5s,border-color .25s ease-in-out;
        width: 100%;
      }
      input:disabled, input[readonly], textarea:disabled, textarea[readonly] {
        background-color: var(--dt-form-disabled-background-color, #e6e6e6);
        cursor: not-allowed;
      }

      /* input::-webkit-datetime-edit-text { color: red; padding: 0 0.3em; } */
      input::-webkit-calendar-picker-indicator { color: red; }

      .input-group {
          position: relative;
          display: inline-flex;
          margin: 0 0 1.0666666667rem;
          width: 100%;
      }

      .input-group .input-group-button {
          font-size: .75rem;
          line-height: 1em;
          display: inline-flex;
      }
      .input-group .button {
        display: inline-block;
        background:  var(--dt-form-background-color, #fefefe);
        border: 1px solid var(--dt-form-border-color, #fefefe);
        color: var(--alert-color, #cc4b37);
        align-self: stretch;
        font-size: 1rem;
        height: auto;
        padding: 0 1em;
        margin: 0;
      }
      .input-group .button:hover {
          background-color: var(--alert-color, #cc4b37);
          color: var(--text-color-inverse, #fefefe);
      }
    `;
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
      timestamp: {
        converter: (date) => {
        let JStimestamp = Number(date);
        if (JStimestamp < 1000000000000) {
          JStimestamp *= 1000
        }
        if(JStimestamp) return JStimestamp;
        return undefined;
        },
        reflect: true
      },
      disabled: { type: Boolean },
      loading: { type: Boolean },
      saved: { type: Boolean },
      onchange: { type: String },
    };
  }

  // _convertArabicToEnglishNumbers() {
  //   this.value
  //   .replace(/[\u0660-\u0669]/g, (c) => { return c.charCodeAt(0) - 0x0660; })
  //     .replace(/[\u06f0-\u06f9]/g, (c) => {
  //       return c.charCodeAt(0) - 0x06f0;
  //     });
  // }

  updateTimestamp(timestamp) {
    let timestampSecond = '';
    let timestampMilliseconds = '';
    if (typeof timestamp === 'number') {
      timestampMilliseconds = new Date(timestamp).getTime();
      timestampSecond = timestampMilliseconds/1000;
    }
    const event = new CustomEvent('change', {
      detail: {
        field: this.name,
        oldValue: this.timestamp,
        newValue: timestampSecond,
      },
    });

    this.dispatchEvent(event);
    this.timestamp = timestampMilliseconds
    this.value = timestamp;
  }

  onChange(e) {
    this.updateTimestamp(e.target.value);
  }

  clearInput() {
    this.updateTimestamp('');
  }

  showDatePicker() {
    const input = this.shadowRoot.querySelector('input');
    input.showPicker();
  }

  render() {
    if (this.timestamp) {
      this.value = new Date(this.timestamp).toISOString().substring(0,10);
    } else if (this.value) {
      this.timestamp = new Date(this.value).getTime();
    }

    return html`
      ${this.labelTemplate()}

      <div class="input-group">
          <input
            id="${this.id}"
            class="input-group-field dt_date_picker"
            type="date"
            autocomplete="off"
            .placeholder="${new Date().toISOString().substring(0,10)}"
            .value="${this.value}"
            .timestamp="${this.date}"
            ?disabled=${this.disabled}
            @change="${this.onChange}"
            @click="${this.showDatePicker}"
          />
          <button
              id="${this.id}-clear-button"
              class="button alert clear-date-button"
              data-inputid="${this.id}"
              title="Delete Date"
              type="button"
              @click="${this.clearInput}">
              x
          </button>
      </div>
    `;
  }
}

window.customElements.define('dt-date', DtDateField);
