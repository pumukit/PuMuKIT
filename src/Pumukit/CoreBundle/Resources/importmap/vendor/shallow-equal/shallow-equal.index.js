/**
 * Bundled by jsDelivr using Rollup v4.62.2 and esbuild v0.28.1.
 * Original file: /npm/shallow-equal@3.1.0/dist/index.modern.mjs
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
function f(t,e){if(t===e)return!0;if(!t||!e)return!1;const n=t.length;if(e.length!==n)return!1;for(let r=0;r<n;r++)if(t[r]!==e[r])return!1;return!0}function i(t,e){if(t===e)return!0;if(!t||!e)return!1;const n=Object.keys(t),r=Object.keys(e),u=n.length;if(r.length!==u)return!1;for(let s=0;s<u;s++){const l=n[s];if(t[l]!==e[l]||!Object.prototype.hasOwnProperty.call(e,l))return!1}return!0}function c(t,e){const n=Array.isArray(t),r=Array.isArray(e);return n!==r?!1:n&&r?f(t,e):i(t,e)}export{c as shallowEqual,f as shallowEqualArrays,i as shallowEqualObjects};
