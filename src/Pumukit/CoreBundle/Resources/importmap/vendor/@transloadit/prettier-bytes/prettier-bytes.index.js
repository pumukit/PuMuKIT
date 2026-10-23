/**
 * Bundled by jsDelivr using Rollup v4.62.2 and esbuild v0.28.1.
 * Original file: /npm/@transloadit/prettier-bytes@2.0.0/dist/prettierBytes.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
const a=["B","KB","MB","GB","TB","PB","EB","ZB","YB"];function f(e,B){if(typeof e!="number"||Number.isNaN(e))throw new TypeError(`Expected a number, got ${typeof e}`);const s=e<0;let o=Math.abs(e);if(s&&(o=-o),o===0)return"0 B";const r=B?a.indexOf(B):Math.min(Math.floor(Math.log(o)/Math.log(1024)),a.length-1),t=Number(o/1024**r),n=a[r];return r>=3?t%1===0?`${Math.round(t)} ${n}`:`${t.toFixed(2)} ${n}`:`${t>=10||t%1===0?Math.round(t):t.toFixed(1)} ${n}`}export{f as prettierBytes};
