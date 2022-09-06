import{b as t}from"./lit-element-69ea4448.js";
/**
 * @license
 * Copyright 2017 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */const s=1;
/**
 * @license
 * Copyright 2018 Google LLC
 * SPDX-License-Identifier: BSD-3-Clause
 */
const e=(t=>(...s)=>({_$litDirective$:t,values:s}))(class extends class{constructor(t){}get _$AU(){return this._$AM._$AU}_$AT(t,s,e){this._$Ct=t,this._$AM=s,this._$Ci=e}_$AS(t,s){return this.update(t,s)}update(t,s){return this.render(...s)}}{constructor(t){var e;if(super(t),t.type!==s||"class"!==t.name||(null===(e=t.strings)||void 0===e?void 0:e.length)>2)throw Error("`classMap()` can only be used in the `class` attribute and must be the only part in the attribute.")}render(t){return" "+Object.keys(t).filter((s=>t[s])).join(" ")+" "}update(s,[e]){var i,n;if(void 0===this.nt){this.nt=new Set,void 0!==s.strings&&(this.st=new Set(s.strings.join(" ").split(/\s/).filter((t=>""!==t))));for(const t in e)e[t]&&!(null===(i=this.st)||void 0===i?void 0:i.has(t))&&this.nt.add(t);return this.render(e)}const r=s.element.classList;this.nt.forEach((t=>{t in e||(r.remove(t),this.nt.delete(t))}));for(const t in e){const s=!!e[t];s===this.nt.has(t)||(null===(n=this.st)||void 0===n?void 0:n.has(t))||(s?(r.add(t),this.nt.add(t)):(r.remove(t),this.nt.delete(t)))}return t}});export{e as o};
