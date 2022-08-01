import { html, css, LitElement } from 'lit';

export class DtTile extends LitElement {
  static get styles() {
    return css`
      :host {
        font-family: var(--font-family);        
        font-size: var(--dt-label-font-size, 14px);
        font-weight: var(--dt-label-font-weight, 700);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
      }
      
      section {
        background-color: #fefefe;
        border: 1px solid #e6e6e6;
        border-radius: 10px;
        -webkit-box-shadow: 0 2px 4px rgb(0 0 0 / 25%);
        box-shadow: 0 2px 4px rgb(0 0 0 / 25%);
        padding: 1rem;
      }
      
      h3 {
        line-height: 1.4;
        margin-bottom: 0.5rem;
        margin-top: 0;
        text-rendering: optimizeLegibility;
        font-family: var(--font-family, 'Helvetica,Arial,sans-serif');
        font-style: normal;
        font-weight: 300;
      }
      .section-header {
        color: var(--primary-color, #3f729b);
        font-size: 1.5rem;
        display: flex;
      }
      
      .section-body {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        column-gap: 1.4rem;
        transition: height 1s ease 0s;
        overflow: hidden;
        height: auto;
      }
      .section-body.collapsed {
        height: 0 !important;
      }
      
      button.toggle {
        margin-inline-end: 0;
        margin-inline-start: auto;
        background: none;
        border: none;
      }
      
      .chevron::before {
        border-color: var(--primary-color);
        border-style: solid;
        border-width: 2px 2px 0 0;
        content: '';
        display: inline-block;
        height: 1em;
        width: 1em;
        left: 0.15em;
        position: relative;
        top: 0.15em;
        transform: rotate(-45deg);
        vertical-align: top;
      }
      
      .chevron.down:before {
        top: 0;
        transform: rotate(135deg);
      }
    `;
  }

  static get properties() {
    return {
      title: { type: String },
      expands: { type: Boolean },
      collapsed: { type: Boolean },
    };
  }

  _toggle() {
    // const body = this.renderRoot.querySelector('.section-body');
    // if (!this.collapsed && body && body.clientHeight) {
    //   body.style.height = body.clientHeight + 'px';
    // }
    this.collapsed = !this.collapsed;
  }

  render() {
    return html`
      <section>
        <h3 class="section-header">
          ${this.title}
          
          ${this.expands ? html`
          <button @click="${this._toggle}" class="toggle chevron ${this.collapsed ? 'down' : 'up'}">&nbsp;</button>
          ` : null }
        </h3>
        <div class="section-body ${this.collapsed ? 'collapsed' : null}">
          <slot></slot>
        </div>
      </section>
    `;
  }
}

window.customElements.define('dt-tile', DtTile);
