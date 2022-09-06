import { css, LitElement } from 'lit';

export class DtCheckmark extends LitElement {
  static get styles() {
    return css`
      @keyframes fadeOut {
        0% {
          opacity: 1;
        }
        75% {
          opacity: 1;
        }
        100% {
          opacity: 0;
        }
      }
      :host {
        margin-top: -0.25rem;
      }
      :host::before {
        content: '';
        transform: rotate(45deg);
        height: 1rem;
        width: 0.5rem;
        opacity: 0;
        color: inherit;
        border-bottom: var(--dt-checkmark-width) solid currentcolor;
        border-right: var(--dt-checkmark-width) solid currentcolor;
        animation: fadeOut 4s;
      }
    `;
  }
}

window.customElements.define('dt-checkmark', DtCheckmark);
