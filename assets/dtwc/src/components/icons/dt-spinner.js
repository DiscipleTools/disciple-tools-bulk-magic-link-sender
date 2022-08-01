import { css, LitElement } from 'lit';

export class DtSpinner extends LitElement {
  static get styles() {
    return css`
      @keyframes spin {
        0% {
          transform: rotate(0deg);
        }

        100% {
          transform: rotate(360deg);
        }
      }
      :host::before {
        content: '';
        -webkit-animation: spin 1s linear infinite;
        animation: spin 1s linear infinite;
        border: 0.25rem solid var(--dt-spinner-color, #919191);
        border-radius: 50%;
        border-top-color: #000;
        display: inline-block;
        height: 1rem;
        width: 1rem;
      }
    `;
  }
}

window.customElements.define('dt-spinner', DtSpinner);
