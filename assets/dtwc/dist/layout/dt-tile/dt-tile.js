import{s as e,r as t,$ as o}from"../../lit-element-69ea4448.js";window.customElements.define("dt-tile",class extends e{static get styles(){return t`:host{font-family:var(--dt-tile-font-family);font-size:var(--dt-tile-font-size,14px);font-weight:var(--dt-tile-font-weight,700);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}section{background-color:var(--dt-tile-background-color,#fefefe);border:1px solid var(--dt-tile-border-color,#cecece);border-radius:10px;box-shadow:var(--dt-tile-box-shadow,0 2px 4px rgb(0 0 0 / 25%));padding:1rem}h3{line-height:1.4;margin-bottom:.5rem;margin-top:0;text-rendering:optimizeLegibility;font-family:var(--dt-tile-font-family, 'Helvetica,Arial,sans-serif');font-style:normal;font-weight:300}.section-header{color:var(--dt-tile-header-color,#3f729b);font-size:1.5rem;display:flex}.section-body{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));column-gap:1.4rem;transition:height 1s ease 0s;height:auto}.section-body.collapsed{height:0!important}button.toggle{margin-inline-end:0;margin-inline-start:auto;background:0 0;border:none}.chevron::before{border-color:var(--dt-tile-header-color,#3f729b);border-style:solid;border-width:2px 2px 0 0;content:'';display:inline-block;height:1em;width:1em;left:.15em;position:relative;top:.15em;transform:rotate(-45deg);vertical-align:top}.chevron.down:before{top:0;transform:rotate(135deg)}`}static get properties(){return{title:{type:String},expands:{type:Boolean},collapsed:{type:Boolean}}}_toggle(){this.collapsed=!this.collapsed}render(){return o`<section><h3 class="section-header">${this.title} ${this.expands?o`<button @click="${this._toggle}" class="toggle chevron ${this.collapsed?"down":"up"}"> </button>`:null}</h3><div class="section-body ${this.collapsed?"collapsed":null}"><slot></slot></div></section>`}});