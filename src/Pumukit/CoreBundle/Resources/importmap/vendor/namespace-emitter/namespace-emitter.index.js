/**
 * Bundled by jsDelivr using Rollup v4.62.2 and esbuild v0.28.1.
 * Original file: /npm/namespace-emitter@2.0.1/index.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
var u,m;function p(){return m||(m=1,u=function(){var f={},o=f._fns={};f.emit=function(r,a,t,n,e,c,s){var _=h(r);_.length&&l(r,_,[a,t,n,e,c,s])},f.on=function(r,a){o[r]||(o[r]=[]),o[r].push(a)},f.once=function(r,a){function t(){a.apply(this,arguments),f.off(r,t)}this.on(r,t)},f.off=function(r,a){var t=[];if(r&&a){var n=this._fns[r],e=0,c=n?n.length:0;for(e;e<c;e++)n[e]!==a&&t.push(n[e])}t.length?this._fns[r]=t:delete this._fns[r]};function h(i){var r=o[i]?o[i]:[],a=i.indexOf(":"),t=a===-1?[i]:[i.substring(0,a),i.substring(a+1)],n=Object.keys(o),e=0,c=n.length;for(e;e<c;e++){var s=n[e];if(s==="*"&&(r=r.concat(o[s])),t.length===2&&t[0]===s){r=r.concat(o[s]);break}}return r}function l(i,r,a){var t=0,n=r.length;for(t;t<n&&r[t];t++)r[t].event=i,r[t].apply(r[t],a)}return f}),u}var v=p();export{v as default};
