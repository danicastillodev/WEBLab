function i(o){if(!o)return"";const t=String(o),[r,n,e]=t.slice(0,10).split("-");if(!(r&&n&&e))return t;const c=t.slice(11,16);return c?`${e}/${n}/${r} ${c}`:`${e}/${n}/${r}`}export{i as f};
