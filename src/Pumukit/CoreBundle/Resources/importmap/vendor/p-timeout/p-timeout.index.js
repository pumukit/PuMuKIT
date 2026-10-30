/**
 * Bundled by jsDelivr using Rollup v4.62.2 and esbuild v0.28.1.
 * Original file: /npm/p-timeout@7.0.2/index.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
class s extends Error{name="TimeoutError";constructor(c,e){super(c,e),Error.captureStackTrace?.(this,s)}}const b=n=>n.reason??new DOMException("This operation was aborted.","AbortError");function E(n,c){const{milliseconds:e,fallback:m,message:o,customTimers:f={setTimeout,clearTimeout},signal:r}=c;let l,i;const u=new Promise((a,t)=>{if(typeof e!="number"||Math.sign(e)!==1)throw new TypeError(`Expected \`milliseconds\` to be a positive number, got \`${e}\``);if(r?.aborted){t(b(r)),n.then(a,t);return}if(r&&(i=()=>{t(b(r))},r.addEventListener("abort",i,{once:!0})),n.then(a,t),e===Number.POSITIVE_INFINITY)return;const d=new s;l=f.setTimeout.call(void 0,()=>{if(m){try{a(m())}catch(p){t(p)}return}typeof n.cancel=="function"&&n.cancel(),o===!1?a():o instanceof Error?t(o):(d.message=o??`Promise timed out after ${e} milliseconds`,t(d))},e)}).finally(()=>{u.clear(),i&&r&&r.removeEventListener("abort",i)});return u.clear=()=>{f.clearTimeout.call(void 0,l),l=void 0},u}export{s as TimeoutError,E as default};
