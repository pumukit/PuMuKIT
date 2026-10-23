/**
 * Bundled by jsDelivr using Rollup v4.62.2 and esbuild v0.28.1.
 * Original file: /npm/p-timeout@7.0.1/index.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
class a extends Error{name="TimeoutError";constructor(s,e){super(s,e),Error.captureStackTrace?.(this,a)}}const b=o=>o.reason??new DOMException("This operation was aborted.","AbortError");function E(o,s){const{milliseconds:e,fallback:m,message:n,customTimers:f={setTimeout,clearTimeout},signal:r}=s;let c,i;const l=new Promise((u,t)=>{if(typeof e!="number"||Math.sign(e)!==1)throw new TypeError(`Expected \`milliseconds\` to be a positive number, got \`${e}\``);if(r?.aborted){t(b(r));return}if(r&&(i=()=>{t(b(r))},r.addEventListener("abort",i,{once:!0})),o.then(u,t),e===Number.POSITIVE_INFINITY)return;const d=new a;c=f.setTimeout.call(void 0,()=>{if(m){try{u(m())}catch(p){t(p)}return}typeof o.cancel=="function"&&o.cancel(),n===!1?u():n instanceof Error?t(n):(d.message=n??`Promise timed out after ${e} milliseconds`,t(d))},e)}).finally(()=>{l.clear(),i&&r&&r.removeEventListener("abort",i)});return l.clear=()=>{f.clearTimeout.call(void 0,c),c=void 0},l}export{a as TimeoutError,E as default};
