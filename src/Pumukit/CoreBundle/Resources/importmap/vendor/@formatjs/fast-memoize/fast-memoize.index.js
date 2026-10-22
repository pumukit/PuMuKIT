/**
 * Bundled by jsDelivr using Rollup v4.62.2 and esbuild v0.28.1.
 * Original file: /npm/@formatjs/fast-memoize@3.1.7/index.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
function l(t,e){const c=e&&e.cache?e.cache:m,r=e&&e.serializer?e.serializer:d;return(e&&e.strategy?e.strategy:h)(t,{cache:c,serializer:r})}function f(t){return t==null||typeof t=="number"||typeof t=="boolean"}function s(t,e,c,r){const n=f(r)?r:c(r);let a=e.get(n);return typeof a>"u"&&(a=t.call(this,r),e.set(n,a)),a}function u(t,e,c){const r=Array.prototype.slice.call(arguments,3),n=c(r);let a=e.get(n);return typeof a>"u"&&(a=t.apply(this,r),e.set(n,a)),a}function i(t,e,c,r,n){return c.bind(e,t,r,n)}function h(t,e){const c=t.length===1?s:u;return i(t,this,c,e.cache.create(),e.serializer)}function o(t,e){return i(t,this,u,e.cache.create(),e.serializer)}function y(t,e){return i(t,this,s,e.cache.create(),e.serializer)}const d=function(){return JSON.stringify(arguments)};var g=class{constructor(){this.cache=Object.create(null)}get(t){return this.cache[t]}set(t,e){this.cache[t]=e}};const m={create:function(){return new g}},z={variadic:o,monadic:y};export{l as memoize,z as strategies};
