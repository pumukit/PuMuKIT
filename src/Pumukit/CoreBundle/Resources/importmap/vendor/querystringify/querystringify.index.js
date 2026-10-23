/**
 * Bundled by jsDelivr using Rollup v4.62.2 and esbuild v0.28.1.
 * Original file: /npm/querystringify@2.2.0/index.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
var u={},c;function _(){if(c)return u;c=1;var f=Object.prototype.hasOwnProperty,y;function s(e){try{return decodeURIComponent(e.replace(/\+/g," "))}catch{return null}}function l(e){try{return encodeURIComponent(e)}catch{return null}}function g(e){for(var t=/([^=?#&]+)=?([^&]*)/g,i={},r;r=t.exec(e);){var n=s(r[1]),o=s(r[2]);n===null||o===null||n in i||(i[n]=o)}return i}function p(e,t){t=t||"";var i=[],r,n;typeof t!="string"&&(t="?");for(n in e)if(f.call(e,n)){if(r=e[n],!r&&(r===null||r===y||isNaN(r))&&(r=""),n=l(n),r=l(r),n===null||r===null)continue;i.push(n+"="+r)}return i.length?t+i.join("&"):""}return u.stringify=p,u.parse=g,u}var a=_(),d=a.parse,v=a.stringify;export{a as default,d as parse,v as stringify};
