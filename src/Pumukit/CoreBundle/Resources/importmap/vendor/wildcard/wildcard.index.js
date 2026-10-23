/**
 * Bundled by jsDelivr using Rollup v4.62.2 and esbuild v0.28.1.
 * Original file: /npm/wildcard@1.1.2/index.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
var f,l;function n(){if(l)return f;l=1;function h(r,t){this.text=r=r||"",this.hasWild=~r.indexOf("*"),this.separator=t,this.parts=r.split(t)}return h.prototype.match=function(r){var t=!0,a=this.parts,e,c=a.length,i;if(typeof r=="string"||r instanceof String)if(!this.hasWild&&this.text!=r)t=!1;else{for(i=(r||"").split(this.separator),e=0;t&&e<c;e++)a[e]!=="*"&&(e<i.length?t=a[e]===i[e]:t=!1);t=t&&i}else if(typeof r.splice=="function")for(t=[],e=r.length;e--;)this.match(r[e])&&(t[t.length]=r[e]);else if(typeof r=="object"){t={};for(var s in r)this.match(s)&&(t[s]=r[s])}return t},f=function(r,t,a){var e=new h(r,a||/[\/\.]/);return typeof t<"u"?e.match(t):e},f}var o=n();export{o as default};
