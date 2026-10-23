/**
 * Bundled by jsDelivr using Rollup v4.62.2 and esbuild v0.28.1.
 * Original file: /npm/is-network-error@1.3.2/index.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
const n=Object.prototype.toString,o=e=>n.call(e)==="[object Error]",s=new Set(["network error","NetworkError when attempting to fetch resource.","The Internet connection appears to be offline.","Network request failed","fetch failed","terminated"," A network error occurred.","Network connection lost"]);function i(e){if(!(e&&o(e)&&e.name==="TypeError"&&typeof e.message=="string"))return!1;const{message:t,stack:r}=e;return t==="Load failed"||t.startsWith("Load failed (")&&t.endsWith(")")?r===void 0||"__sentry_captured__"in e:t.startsWith("error sending request for url")||t==="Failed to fetch"||t.startsWith("Failed to fetch (")&&t.endsWith(")")?!0:s.has(t)}export{i as default};
