/*!/*@nomin*/
"use strict";var Fe=Object.defineProperty,Pe=Object.defineProperties;var Oe=Object.getOwnPropertyDescriptors;var te=Object.getOwnPropertySymbols;var je=Object.prototype.hasOwnProperty,Be=Object.prototype.propertyIsEnumerable;var B=(e,r,n)=>r in e?Fe(e,r,{enumerable:!0,configurable:!0,writable:!0,value:n}):e[r]=n,P=(e,r)=>{for(var n in r||(r={}))je.call(r,n)&&B(e,n,r[n]);if(te)for(var n of te(r))Be.call(r,n)&&B(e,n,r[n]);return e},G=(e,r)=>Pe(e,Oe(r));var w=(e,r,n)=>B(e,typeof r!="symbol"?r+"":r,n);var _=(e,r,n)=>new Promise((a,u)=>{var i=c=>{try{o(n.next(c))}catch(g){u(g)}},l=c=>{try{o(n.throw(c))}catch(g){u(g)}},o=c=>c.done?a(c.value):Promise.resolve(c.value).then(i,l);o((n=n.apply(e,r)).next())});const t=require("vue"),V=require("vuex"),E=require("@wikimedia/codex"),Ge={action:"wbsearchentities",type:"item",limit:"10"};function De(e){return e.search.map(r=>({id:r.id,display:r.display}))}class We{constructor(r,n){w(this,"api");w(this,"languageCode");this.api=r,this.languageCode=n}searchItems(u,i){return _(this,arguments,function*(r,n,a={}){const l=yield this.api.get(P(G(P({},Ge),{search:r,language:this.languageCode,continue:n}),a));return De(l)})}}class He{constructor(r,n){this.getUrl=r,this.lexemeNamespaceId=n}getSearchUrlForLexeme(r){return this.getUrl("Special:Search",{search:r,[`ns${this.lexemeNamespaceId}`]:1})}}class Ke{constructor(r){this.trackFunction=r}increment(r){this.trackFunction(`counter.MediaWiki.${r}`,1)}}const me=Symbol("ItemSearch");function qe(){return t.inject(me,()=>{throw new Error("No ItemSearcher provided!")},!0)}const pe=Symbol("Config");function U(){return t.inject(pe,()=>{throw new Error("No Config provided!")},!0)}const fe=Symbol("SearchLinker");function Xe(){return t.inject(fe,()=>{throw new Error("No SearchLinker provided!")},!0)}const he=Symbol("AuthenticationLinker");function Ye(){return t.inject(he,()=>{throw new Error("No AuthenticationLinker provided!")},!0)}const q="setLemma",H="setLanguage",X="setLanguageSearchInput",Y="setLexicalCategory",J="setLexicalCategorySearchInput",j="setSpellingVariant",Q="setSpellingVariantSearchInput",K="setLanguageCodeFromLanguageItem",ve="addErrors",$="addPerFieldError",R="clearPerFieldErrors",ye="clearErrors",Je={[q](e,r){e.lemma=r},[H](e,r){e.language=r},[X](e,r){e.languageSearchInput=r},[Y](e,r){e.lexicalCategory=r},[J](e,r){e.lexicalCategorySearchInput=r},[j](e,r){e.spellingVariant=r},[Q](e,r){e.spellingVariantSearchInput=r},[K](e,r){e.languageCodeFromLanguageItem=r},[ve](e,r){e.globalErrors.push(...r)},[$](e,r){e.perFieldErrors[r.field].push(r.error)},[R](e,r){e.perFieldErrors[r]=[]},[ye](e){e.globalErrors=[]}},xe="createLexeme",be="handleLanguageChange",we="initFromParams",D="handleItemLanguageCode",re="assembleValidInputs";function Qe(e,r,n,a){return{[re]({state:i,commit:l}){const o={};if(i.lemma?o.validLemma=i.lemma:l($,{field:"lemmaErrors",error:{messageKey:"wikibaselexeme-newlexeme-lemma-empty-error"}}),i.language)o.validLanguageId=i.language.id;else{let g;i.languageSearchInput?g="wikibaselexeme-newlexeme-language-invalid-error":g="wikibaselexeme-newlexeme-language-empty-error",l($,{field:"languageErrors",error:{messageKey:g}})}if(i.lexicalCategory)o.validLexicalCategoryId=i.lexicalCategory.id;else{let g;i.lexicalCategorySearchInput?g="wikibaselexeme-newlexeme-lexicalcategory-invalid-error":g="wikibaselexeme-newlexeme-lexicalcategory-empty-error",l($,{field:"lexicalCategoryErrors",error:{messageKey:g}})}if(i.language)if(i.spellingVariant)o.validSpellingVariant=i.spellingVariant;else if(i.languageCodeFromLanguageItem)o.validSpellingVariant=i.languageCodeFromLanguageItem;else{let g;i.spellingVariantSearchInput?g="wikibaselexeme-newlexeme-lemma-language-invalid-error":g="wikibaselexeme-newlexeme-lemma-language-empty-error",l($,{field:"spellingVariantErrors",error:{messageKey:g}})}if(!(g=>!!g.validLemma&&!!g.validLanguageId&&!!g.validLexicalCategoryId&&!!g.validSpellingVariant)(o))throw new Error("Not all fields are valid");return o},[xe](o){return _(this,arguments,function*({commit:i,dispatch:l}){const{validLemma:c,validLanguageId:g,validLexicalCategoryId:h,validSpellingVariant:m}=yield l(re);i(ye);try{const p=yield e.createLexeme(c,m,g,h);return a.increment("wikibase.lexeme.special.NewLexeme.js.create"),p}catch(p){return i(ve,p),Promise.reject(null)}})},[be](g,h){return _(this,arguments,function*({state:i,commit:l,dispatch:o},c){const m=i.language;if(l(H,c),(m==null?void 0:m.id)===(c==null?void 0:c.id)||(l(K,void 0),l(j,""),!c))return;const p=yield r.getLanguageCodeFromItem(c.id);yield o(D,p)})},[we](c,g){return _(this,arguments,function*({commit:i,dispatch:l},o){var h,m,p,v;o.lemma!==void 0&&i(q,o.lemma),o.spellVarCode!==void 0&&(i(j,o.spellVarCode),i(Q,o.spellVarCode)),o.language!==void 0&&(i(H,{id:o.language.id,display:o.language.display}),yield l(D,o.language.languageCode),i(X,(m=(h=o.language.display.label)==null?void 0:h.value)!=null?m:o.language.id)),o.lexicalCategory!==void 0&&(i(Y,o.lexicalCategory),i(J,(v=(p=o.lexicalCategory.display.label)==null?void 0:p.value)!=null?v:o.lexicalCategory.id))})},[D](o,c){return _(this,arguments,function*({commit:i},l){typeof l=="string"&&(l=l.toLowerCase()),typeof l=="string"&&!n.isValid(l)&&(l=!1),i(K,l)})}}}function ze({lexemeCreator:e,langCodeRetriever:r,languageCodesProvider:n,tracker:a}){return V.createStore({state(){return{lemma:"",language:null,languageSearchInput:"",languageCodeFromLanguageItem:void 0,lexicalCategory:null,lexicalCategorySearchInput:"",spellingVariant:"",spellingVariantSearchInput:"",globalErrors:[],perFieldErrors:{lemmaErrors:[],languageErrors:[],lexicalCategoryErrors:[],spellingVariantErrors:[]}}},mutations:Je,actions:Qe(e,r,n,a)})}const Ze=t.defineComponent({__name:"WarningMessage",setup(e){return(r,n)=>(t.openBlock(),t.createBlock(t.unref(E.CdxMessage),{type:"warning"},{default:t.withCtx(()=>[t.renderSlot(r.$slots,"default")]),_:3}))}});class _e{constructor(r){w(this,"messagesRepository");if(!r){this.messagesRepository={get(n){return`⧼${n}⧽`},getText(n){return`⧼${n}⧽`}};return}this.messagesRepository=r}get(r,...n){return this.messagesRepository.get(r,...n)}getUnescaped(r,...n){return this.messagesRepository.getText(r,...n)}}const Ee=Symbol("Messages");function k(){return t.inject(Ee,new _e)}const et=["innerHTML"],tt=t.defineComponent({__name:"AnonymousEditWarning",setup(e){const r=Ye(),n=k(),a=U();let u="wikibase-anonymouseditwarning";a.tempUserEnabled&&(u="wikibase-anonymouseditnotificationtempuser");const i=t.computed(()=>n.get(u,r.getLoginLink(),r.getCreateAccountLink()));return(l,o)=>t.unref(a).isAnonymous?(t.openBlock(),t.createBlock(Ze,{key:0,class:"wbl-snl-anonymous-edit-warning"},{default:t.withCtx(()=>[t.createElementVNode("span",{innerHTML:i.value},null,8,et)]),_:1})):t.createCommentVNode("",!0)}}),M=(e,r)=>{const n=e.__vccOpts||e;for(const[a,u]of r)n[a]=u;return n},rt=M(tt,[["__scopeId","data-v-1a7220a5"]]),nt=["title"],at=t.defineComponent({__name:"RequiredAsterisk",setup(e){const r=k();return(n,a)=>(t.openBlock(),t.createElementBlock("span",{class:"wbl-snl-required-asterisk","aria-hidden":"true",title:t.unref(r).getUnescaped("wikibaselexeme-form-field-required")},"*",8,nt))}}),F=M(at,[["__scopeId","data-v-c4eb84f6"]]),st=t.defineComponent({__name:"LemmaInput",props:{modelValue:{}},emits:["update:modelValue"],setup(e){const r=e,n=k(),a=U(),u=a.placeholderExampleData.lemma,i=V.useStore(),l=t.computed(()=>Array.from(r.modelValue).length>a.maxLemmaLength?{status:"error",messages:{error:n.getUnescaped("wikibaselexeme-newlexeme-lemma-too-long-error",a.maxLemmaLength.toString())}}:i.state.perFieldErrors.lemmaErrors.length?{status:"error",messages:{error:n.getUnescaped(i.state.perFieldErrors.lemmaErrors[0].messageKey)}}:{status:"default",messages:{}});return(o,c)=>(t.openBlock(),t.createBlock(t.unref(E.CdxField),{class:"wbl-snl-lemma-input",status:l.value.status,messages:l.value.messages},{label:t.withCtx(()=>[t.createTextVNode(t.toDisplayString(t.unref(n).getUnescaped("wikibaselexeme-newlexeme-lemma")),1),t.createVNode(F)]),default:t.withCtx(()=>[t.createVNode(t.unref(E.CdxTextInput),{placeholder:t.unref(n).getUnescaped("wikibaselexeme-newlexeme-lemma-placeholder-with-example",t.unref(u)),name:"lemma","aria-required":"true","model-value":o.modelValue,"onUpdate:modelValue":c[0]||(c[0]=g=>o.$emit("update:modelValue",g))},null,8,["placeholder","model-value"])]),_:1},8,["status","messages"]))}});var O=typeof globalThis!="undefined"?globalThis:typeof window!="undefined"?window:typeof global!="undefined"?global:typeof self!="undefined"?self:{};function Se(e){return e&&e.__esModule&&Object.prototype.hasOwnProperty.call(e,"default")?e.default:e}function ot(e){var r=typeof e;return e!=null&&(r=="object"||r=="function")}var Le=ot,it=typeof O=="object"&&O&&O.Object===Object&&O,lt=it,ut=lt,ct=typeof self=="object"&&self&&self.Object===Object&&self,gt=ut||ct||Function("return this")(),Ie=gt,dt=Ie,mt=function(){return dt.Date.now()},pt=mt,ft=/\s/;function ht(e){for(var r=e.length;r--&&ft.test(e.charAt(r)););return r}var vt=ht,yt=vt,xt=/^\s+/;function bt(e){return e&&e.slice(0,yt(e)+1).replace(xt,"")}var wt=bt,_t=Ie,Et=_t.Symbol,z=Et,ne=z,ke=Object.prototype,St=ke.hasOwnProperty,Lt=ke.toString,A=ne?ne.toStringTag:void 0;function It(e){var r=St.call(e,A),n=e[A];try{e[A]=void 0;var a=!0}catch(i){}var u=Lt.call(e);return a&&(r?e[A]=n:delete e[A]),u}var kt=It,Ct=Object.prototype,Tt=Ct.toString;function Vt(e){return Tt.call(e)}var Ut=Vt,ae=z,Nt=kt,At=Ut,$t="[object Null]",Rt="[object Undefined]",se=ae?ae.toStringTag:void 0;function Mt(e){return e==null?e===void 0?Rt:$t:se&&se in Object(e)?Nt(e):At(e)}var Ft=Mt;function Pt(e){return e!=null&&typeof e=="object"}var Ot=Pt,jt=Ft,Bt=Ot,Gt="[object Symbol]";function Dt(e){return typeof e=="symbol"||Bt(e)&&jt(e)==Gt}var Ce=Dt,Wt=wt,oe=Le,Ht=Ce,ie=NaN,Kt=/^[-+]0x[0-9a-f]+$/i,qt=/^0b[01]+$/i,Xt=/^0o[0-7]+$/i,Yt=parseInt;function Jt(e){if(typeof e=="number")return e;if(Ht(e))return ie;if(oe(e)){var r=typeof e.valueOf=="function"?e.valueOf():e;e=oe(r)?r+"":r}if(typeof e!="string")return e===0?e:+e;e=Wt(e);var n=qt.test(e);return n||Xt.test(e)?Yt(e.slice(2),n?2:8):Kt.test(e)?ie:+e}var Qt=Jt,zt=Le,W=pt,le=Qt,Zt="Expected a function",er=Math.max,tr=Math.min;function rr(e,r,n){var a,u,i,l,o,c,g=0,h=!1,m=!1,p=!0;if(typeof e!="function")throw new TypeError(Zt);r=le(r)||0,zt(n)&&(h=!!n.leading,m="maxWait"in n,i=m?er(le(n.maxWait)||0,r):i,p="trailing"in n?!!n.trailing:p);function v(s){var d=a,f=u;return a=u=void 0,g=s,l=e.apply(f,d),l}function L(s){return g=s,o=setTimeout(I,r),h?v(s):l}function T(s){var d=s-c,f=s-g,N=r-d;return m?tr(N,i-f):N}function C(s){var d=s-c,f=s-g;return c===void 0||d>=r||d<0||m&&f>=i}function I(){var s=W();if(C(s))return S(s);o=setTimeout(I,T(s))}function S(s){return o=void 0,p&&a?v(s):(a=u=void 0,l)}function x(){o!==void 0&&clearTimeout(o),g=0,a=c=u=o=void 0}function y(){return o===void 0?l:S(W())}function b(){var s=W(),d=C(s);if(a=arguments,u=this,c=s,d){if(o===void 0)return L(c);if(m)return clearTimeout(o),o=setTimeout(I,r),v(c)}return o===void 0&&(o=setTimeout(I,r)),l}return b.cancel=x,b.flush=y,b}var nr=rr;const ar=Se(nr);function sr(e,r){for(var n=-1,a=e==null?0:e.length,u=Array(a);++n<a;)u[n]=r(e[n],n,e);return u}var or=sr,ir=Array.isArray,lr=ir,ue=z,ur=or,cr=lr,gr=Ce,dr=1/0,ce=ue?ue.prototype:void 0,ge=ce?ce.toString:void 0;function Te(e){if(typeof e=="string")return e;if(cr(e))return ur(e,Te)+"";if(gr(e))return ge?ge.call(e):"";var r=e+"";return r=="0"&&1/e==-dr?"-0":r}var mr=Te,pr=mr;function fr(e){return e==null?"":pr(e)}var hr=fr,vr=hr,Ve=/[\\^$.*+?()[\]{}|]/g,yr=RegExp(Ve.source);function xr(e){return e=vr(e),e&&yr.test(e)?e.replace(Ve,"\\$&"):e}var br=xr;const Ue=Se(br),wr=t.defineComponent({__name:"ItemLookup",props:{label:{},placeholder:{},value:{},searchForItems:{},searchInput:{default:""},error:{default:null},itemSuggestions:{default:()=>[]},ariaRequired:{type:Boolean,default:!1}},emits:{"update:modelValue":e=>e===null||/^Q\d+$/.test(e.id),"update:searchInput":null},setup(e,{emit:r}){const n=e,a=r,u=t.ref(null),i=t.computed(()=>{const s=new RegExp(`\\b${Ue(n.searchInput)}`,"i");return n.itemSuggestions.filter(d=>{var f;return s.test(((f=d.display.label)==null?void 0:f.value)||"")})}),l=t.ref([]),o=t.computed(()=>{const s=[...i.value,...l.value.filter(d=>!i.value.some(f=>f.id===d.id))];return!s.length&&n.value&&s.push(n.value),s}),c=t.ref(null),g=s=>{c.value=s,a("update:modelValue",s)},h=s=>{const d=o.value.find(f=>f.id===s);return g(d!=null?d:null)},m=ar(s=>_(this,null,function*(){l.value=yield n.searchForItems(s)}),150),p=t.ref(null),v=s=>{var f,N,Z;if(p.value===s)return;p.value=s;const d=o.value.find(Me=>{var ee;return((ee=Me.display.label)==null?void 0:ee.value)===s});if(s.trim()===""){l.value=[];return}s===((N=(f=c.value)==null?void 0:f.display.label)==null?void 0:N.value)||s===((Z=c.value)==null?void 0:Z.id)||(h((d==null?void 0:d.id)||null),m(s))},L=()=>_(this,null,function*(){const s=yield n.searchForItems(n.searchInput,l.value.length);l.value=[...l.value,...s]});function T(s){var d,f;return{label:((d=s.display.label)==null?void 0:d.value)||s.id,description:((f=s.display.description)==null?void 0:f.value)||"",value:s.id}}const C=t.computed(()=>o.value.map(T)),I=k(),S={visibleItemLimit:6,boldLabel:!0},x=t.computed(()=>n.error?n.error.type:"default"),y=t.computed(()=>{if(n.error){if(n.error.type==="error")return{error:n.error.message};if(n.error.type==="warning")return{warning:n.error.message}}return{}}),b=E.useModelWrapper(t.toRef(n,"searchInput"),a,"update:searchInput");return(s,d)=>(t.openBlock(),t.createBlock(t.unref(E.CdxField),{status:x.value,messages:y.value},{label:t.withCtx(()=>[t.createTextVNode(t.toDisplayString(s.label),1),s.ariaRequired?(t.openBlock(),t.createBlock(F,{key:0})):t.createCommentVNode("",!0)]),default:t.withCtx(()=>[t.createVNode(t.unref(E.CdxLookup),{selected:u.value,"onUpdate:selected":[d[0]||(d[0]=f=>u.value=f),h],"input-value":t.unref(b),"onUpdate:inputValue":d[1]||(d[1]=f=>t.isRef(b)?b.value=f:null),"aria-required":s.ariaRequired,placeholder:s.placeholder,"menu-items":C.value,"menu-config":S,onLoadMore:L,onInput:v},{"no-results":t.withCtx(()=>[t.createTextVNode(t.toDisplayString(t.unref(I).getUnescaped("wikibase-entityselector-notfound")),1)]),_:1},8,["selected","input-value","aria-required","placeholder","menu-items"])]),_:1},8,["status","messages"]))}}),Ne=M(wr,[["__scopeId","data-v-3aae3495"]]),Ae=Symbol("LanguageItemSearch");function _r(){return t.inject(Ae,()=>{throw new Error("No LanguageItemSearcher provided!")},!0)}const Er={class:"wbl-snl-language-lookup"},Sr=t.defineComponent({__name:"LanguageInput",props:{modelValue:{},searchInput:{}},emits:["update:modelValue","update:searchInput"],setup(e,{emit:r}){const n=e,a=r,u=k(),i=_r(),l=i.searchItems.bind(i),o=V.useStore(),c=t.computed(()=>o.state.perFieldErrors.languageErrors.length?{type:"error",message:u.getUnescaped(o.state.perFieldErrors.languageErrors[0].messageKey)}:o.state.languageCodeFromLanguageItem!==!1?null:{type:"warning",message:u.getUnescaped("wikibaselexeme-newlexeme-invalid-language-code-warning")}),g=U(),h=E.useModelWrapper(t.toRef(n,"searchInput"),a,"update:searchInput");return(m,p)=>(t.openBlock(),t.createElementBlock("div",Er,[t.createVNode(Ne,{"search-input":t.unref(h),"onUpdate:searchInput":[p[0]||(p[0]=v=>t.isRef(h)?h.value=v:null),p[2]||(p[2]=v=>m.$emit("update:searchInput",v))],label:t.unref(u).getUnescaped("wikibaselexeme-newlexeme-language"),placeholder:t.unref(u).getUnescaped("wikibaselexeme-newlexeme-language-placeholder-with-example",t.unref(g).placeholderExampleData.languageLabel),value:m.modelValue,"search-for-items":t.unref(l),error:c.value,"aria-required":!0,"onUpdate:modelValue":p[1]||(p[1]=v=>m.$emit("update:modelValue",v))},{suffix:t.withCtx(()=>[t.createVNode(F)]),_:1},8,["search-input","label","placeholder","value","search-for-items","error"])]))}}),$e=Symbol("LanguageCodesProvider");function Lr(){return t.inject($e,()=>{throw new Error("No LanguageCodesProvider provided!")},!0)}const Ir={class:"wbl-snl-spelling-variant-lookup__help-link"},kr=["href"],Cr=t.defineComponent({__name:"SpellingVariantInput",props:{searchInput:{default:""}},emits:{"update:modelValue":e=>e==null||typeof e=="string"&&e.length>0,"update:searchInput":null},setup(e,{emit:r}){const n=e,a=Lr(),u=k(),i=[];a.getLanguages().forEach((x,y)=>{i.push({label:u.getUnescaped("wikibase-lexeme-lemma-language-option",x,y),value:y,description:""})});const l=t.ref([]),o=r,c=t.ref(null),g=x=>{if(c.value===x)return;if(c.value=x,o("update:searchInput",x),x.trim()===""){l.value=[];return}const y=new RegExp(`\\b${Ue(x)}`,"i");l.value=i.filter(b=>b.label&&y.test(b.label))},h=t.ref(null),m=x=>{const y=l.value.find(b=>b.value===x);o("update:modelValue",(y==null?void 0:y.value.toString())||void 0)},p=U(),v=V.useStore(),L=u.get("wikibaselexeme-newlexeme-lemma-language-help-link-target"),T=u.get("wikibaselexeme-newlexeme-lemma-language-help-link-text"),C=t.computed(()=>v.state.perFieldErrors.spellingVariantErrors.length?"error":"default"),I=t.computed(()=>v.state.perFieldErrors.spellingVariantErrors.length?{error:u.getUnescaped(v.state.perFieldErrors.spellingVariantErrors[0].messageKey)}:{}),S=E.useModelWrapper(t.toRef(n,"searchInput"),o,"update:searchInput");return(x,y)=>(t.openBlock(),t.createBlock(t.unref(E.CdxField),{class:"wbl-snl-spelling-variant-lookup",status:C.value,messages:I.value},{label:t.withCtx(()=>[t.createTextVNode(t.toDisplayString(t.unref(u).getUnescaped("wikibaselexeme-newlexeme-lemma-language")),1),t.createVNode(F),t.createElementVNode("span",Ir,[t.createElementVNode("a",{href:t.unref(L),target:"_blank"},t.toDisplayString(t.unref(T)),9,kr)])]),default:t.withCtx(()=>[t.createVNode(t.unref(E.CdxLookup),{selected:h.value,"onUpdate:selected":[y[0]||(y[0]=b=>h.value=b),m],"input-value":t.unref(S),"onUpdate:inputValue":y[1]||(y[1]=b=>t.isRef(S)?S.value=b:null),placeholder:t.unref(u).getUnescaped("wikibaselexeme-newlexeme-lemma-language-placeholder-with-example",t.unref(p).placeholderExampleData.spellingVariant),"menu-items":l.value,onInput:g},{"no-results":t.withCtx(()=>[t.createTextVNode(t.toDisplayString(t.unref(u).getUnescaped("wikibase-entityselector-notfound")),1)]),_:1},8,["selected","input-value","placeholder","menu-items"])]),_:1},8,["status","messages"]))}}),Tr={class:"wbl-snl-lexical-category-lookup"},Vr=t.defineComponent({__name:"LexicalCategoryInput",props:{modelValue:{},searchInput:{}},emits:["update:modelValue","update:searchInput"],setup(e){const r=k(),n=qe(),a=n.searchItems.bind(n),u=U(),i=u.lexicalCategorySuggestions,l=u.placeholderExampleData.lexicalCategoryLabel,o=V.useStore(),c=t.computed(()=>o.state.perFieldErrors.lexicalCategoryErrors.length?{type:"error",message:r.getUnescaped(o.state.perFieldErrors.lexicalCategoryErrors[0].messageKey)}:null);return(g,h)=>(t.openBlock(),t.createElementBlock("div",Tr,[t.createVNode(Ne,{label:t.unref(r).getUnescaped("wikibaselexeme-newlexeme-lexicalcategory"),placeholder:t.unref(r).getUnescaped("wikibaselexeme-newlexeme-lexicalcategory-placeholder-with-example",t.unref(l)),value:g.modelValue,"search-input":g.searchInput,"search-for-items":t.unref(a),"item-suggestions":t.unref(i),error:c.value,"aria-required":!0,"onUpdate:modelValue":h[0]||(h[0]=m=>g.$emit("update:modelValue",m)),"onUpdate:searchInput":h[1]||(h[1]=m=>g.$emit("update:searchInput",m))},{suffix:t.withCtx(()=>[t.createVNode(F)]),_:1},8,["label","placeholder","value","search-input","search-for-items","item-suggestions","error"])]))}}),Ur=t.defineComponent({__name:"ErrorMessage",setup(e){return(r,n)=>(t.openBlock(),t.createBlock(t.unref(E.CdxMessage),{type:"error"},{default:t.withCtx(()=>[t.renderSlot(r.$slots,"default")]),_:3}))}}),Re=Symbol("UrlLauncher");function Nr(){return t.inject(Re,()=>{throw new Error("No UrlLauncher provided!")},!0)}const Ar={class:"wbl-snl-form"},$r=["innerHTML"],Rr=["innerHTML"],Mr=t.defineComponent({__name:"NewLexemeForm",setup(e){const r=U(),n=k(),a=V.useStore(),u=t.computed({get(){return a.state.lemma},set(s){a.commit(q,s),s.trim().length>0&&a.commit(R,"lemmaErrors")}}),i=t.computed({get(){return a.state.language},set(s){return _(this,null,function*(){yield a.dispatch(be,s),s&&a.commit(R,"languageErrors")})}}),l=t.computed({get(){return a.state.languageSearchInput},set(s){a.commit(X,s)}}),o=t.computed({get(){return a.state.lexicalCategory},set(s){a.commit(Y,s),s&&a.commit(R,"lexicalCategoryErrors")}}),c=t.computed({get(){return a.state.lexicalCategorySearchInput},set(s){a.commit(J,s)}}),g=t.computed(()=>a.state.languageCodeFromLanguageItem===null||a.state.languageCodeFromLanguageItem===!1),h=t.computed({get(){return a.state.spellingVariant},set(s){a.commit(j,s),s&&a.commit(R,"spellingVariantErrors")}}),m=t.computed({get(){return a.state.spellingVariantSearchInput},set(s){a.commit(Q,s)}}),p=t.ref(!1),v=n.getUnescaped("wikibaselexeme-newlexeme-submit"),L=n.getUnescaped("wikibaselexeme-newlexeme-submitting"),T=n.get("copyrightpage"),C=n.get("wikibase-shortcopyrightwarning",v,T,r.licenseUrl,r.licenseName),I=t.computed(()=>p.value?L:v),S=t.computed(()=>{if(a.state.globalErrors.length>0){const s=a.state.globalErrors[0];return s.message?s.message:n.getUnescaped("wikibaselexeme-newlexeme-submit-error")}return null}),x=Nr(),y=()=>_(this,null,function*(){p.value=!0;try{const s=yield a.dispatch(xe);yield x.goToURL(s)}catch(s){}p.value=!1});return(s,d)=>(t.openBlock(),t.createElementBlock("form",Ar,[t.createVNode(st,{modelValue:u.value,"onUpdate:modelValue":d[0]||(d[0]=f=>u.value=f)},null,8,["modelValue"]),t.createVNode(Sr,{modelValue:i.value,"onUpdate:modelValue":d[1]||(d[1]=f=>i.value=f),"search-input":l.value,"onUpdate:searchInput":d[2]||(d[2]=f=>l.value=f)},null,8,["modelValue","search-input"]),g.value?(t.openBlock(),t.createBlock(Cr,{key:0,modelValue:h.value,"onUpdate:modelValue":d[3]||(d[3]=f=>h.value=f),"search-input":m.value,"onUpdate:searchInput":d[4]||(d[4]=f=>m.value=f)},null,8,["modelValue","search-input"])):t.createCommentVNode("",!0),t.createVNode(Vr,{modelValue:o.value,"onUpdate:modelValue":d[5]||(d[5]=f=>o.value=f),"search-input":c.value,"onUpdate:searchInput":d[6]||(d[6]=f=>c.value=f)},null,8,["modelValue","search-input"]),t.createElementVNode("p",{class:"wbl-snl-copyright",innerHTML:t.unref(C)},null,8,$r),S.value?(t.openBlock(),t.createBlock(Ur,{key:1},{default:t.withCtx(()=>[t.createElementVNode("span",{innerHTML:S.value},null,8,Rr)]),_:1})):t.createCommentVNode("",!0),t.createElementVNode("div",null,[t.createVNode(t.unref(E.CdxButton),{class:"form-button-submit",action:"progressive",weight:"primary",type:"submit",disabled:p.value,onClick:t.withModifiers(y,["prevent"])},{default:t.withCtx(()=>[t.createTextVNode(t.toDisplayString(I.value),1)]),_:1},8,["disabled"])])]))}}),Fr=M(Mr,[["__scopeId","data-v-050e1865"]]),Pr=["innerHTML"],Or=t.defineComponent({__name:"SearchExisting",setup(e){const r=k(),n=Xe(),a=V.useStore(),u=t.computed(()=>{const l=a.state.lemma;return n.getSearchUrlForLexeme(l)}),i=t.computed(()=>r.get("wikibaselexeme-newlexeme-search-existing",u.value));return(l,o)=>(t.openBlock(),t.createElementBlock("p",{class:"wbl-snl-search-existing",innerHTML:i.value},null,8,Pr))}}),jr=M(Or,[["__scopeId","data-v-40a94da9"]]),Br={class:"wbl-snl-app"},Gr=t.defineComponent({__name:"App",setup(e){return(r,n)=>(t.openBlock(),t.createElementBlock(t.Fragment,null,[(t.openBlock(),t.createBlock(t.Teleport,{to:"#wbl-snl-intro-text-wrapper"},[t.createVNode(jr),t.createVNode(rt)])),t.createElementVNode("div",Br,[t.createVNode(Fr)])],64))}});class Dr{constructor(r){w(this,"validLanguages");this.validLanguages=r}getLanguages(){return this.validLanguages}isValid(r){return this.validLanguages.has(r)}}function Wr(e,r){const n=t.createApp(Gr),a=new Dr(e.wikibaseLexemeTermLanguages),u=ze(G(P({},r),{languageCodesProvider:a}));return n.use(u),e.initParams!==void 0&&u.dispatch(we,e.initParams),n.provide(pe,e),n.provide(Ee,new _e(r.messagesRepository)),n.provide(me,r.itemSearcher),n.provide(Ae,r.languageItemSearcher),n.provide(fe,r.searchLinker),n.provide(he,r.authenticationLinker),n.provide(Re,r.urlLauncher),n.provide($e,a),n.mount(e.rootSelector)}class Hr{constructor(r,n,a=[]){w(this,"api");w(this,"getUrl");w(this,"tags");this.api=r,this.getUrl=n,this.tags=a}createLexeme(r,n,a,u){return _(this,null,function*(){const i=this.api.assertCurrentUser({action:"wbeditentity",new:"lexeme",tags:this.tags,data:JSON.stringify({lemmas:{[n]:{language:n,value:r}},language:a,lexicalCategory:u}),errorformat:"html",formatversion:2}),o=yield this.api.postWithEditToken(i).catch((g,h,m)=>{let p;try{m&&m.errors?p=m.errors.map(v=>{const L={type:v.code};return v.html&&(L.message=v.html),L}):p=[{type:g}]}catch(v){console.error("Unexpected API result",m,v),p=[{type:"assertionerror"}]}return Promise.reject(p)}),c=window.location.href;return o.tempuserredirect?new URL(o.tempuserredirect,c):new URL(this.getUrl(`Special:EntityPage/${o.entity.id}`),c)})}}class Kr{constructor(r){w(this,"mwMessages");this.mwMessages=r}get(r,...n){return this.mwMessages(r,...n).parse()}getText(r,...n){return this.mwMessages(r,...n).text()}}class qr{goToURL(r){return window.location.href=r.toString(),new Promise(n=>{})}}function de(e){var r,n;switch(e.mainsnak.snaktype){case"value":break;case"somevalue":return!1;case"novalue":return null;default:throw new Error(`Unexpected snak type ${e.mainsnak.snaktype}!`)}if(((r=e.mainsnak.datavalue)==null?void 0:r.type)!=="string")throw new Error(`Expected ${e.mainsnak.property} to have DataValueType "string" but got "${(n=e.mainsnak.datavalue)==null?void 0:n.type}"!`);return e.mainsnak.datavalue.value}function Xr(e,r){if(!e.claims[r])return null;const n=e.claims[r].filter(u=>u.rank==="preferred");if(n.length!==0)return de(n[0]);const a=e.claims[r].filter(u=>u.rank==="normal");return a.length!==0?de(a[0]):null}class Yr{constructor(r,n){w(this,"api");w(this,"languageCodeProperty");this.api=r,this.languageCodeProperty=n}getLanguageCodeFromItem(r){return _(this,null,function*(){if(!this.languageCodeProperty)return null;const n=yield this.api.get({action:"wbgetclaims",entity:r,property:this.languageCodeProperty,props:""}).catch((a,u,i)=>(console.warn(`Error while retrieving language code in ${this.languageCodeProperty} for item ${r}: ${a}`,i),!1));return n===!1?!1:Xr(n,this.languageCodeProperty)})}}class Jr{constructor(r,n){w(this,"LANGUAGE_PROFILE_NAME","language");w(this,"itemSearcher");w(this,"useLanguageProfile");this.itemSearcher=r,this.useLanguageProfile=n.includes(this.LANGUAGE_PROFILE_NAME)}searchItems(r,n){return _(this,null,function*(){const a={};return this.useLanguageProfile&&(a.profile=this.LANGUAGE_PROFILE_NAME),this.itemSearcher.searchItems(r,n,a)})}}class Qr{constructor(r,n){this.getUrl=r,this.currentPage=n}getCreateAccountLink(){return this.getUrl("Special:CreateAccount",{returnto:this.currentPage})}getLoginLink(){return this.getUrl("Special:UserLogin",{returnto:this.currentPage})}}function zr(e,r){const n=r.config.get("wgUserLanguage"),a=new r.Api({parameters:{formatversion:2,uselang:n,errorformat:"html"}}),u=new We(a,n),i=new Jr(u,e.availableSearchProfiles),l=new Yr(a,r.config.get("LexemeLanguageCodePropertyId")),o=new Kr(r.message),c=new Hr(a,r.util.getUrl,e.tags),g=new He(r.util.getUrl,r.config.get("wgNamespaceIds").lexeme),h=new Qr(r.util.getUrl,r.config.get("wgPageName")),m=new Ke(r.track),p=new qr;return Wr(e,{itemSearcher:u,languageItemSearcher:i,langCodeRetriever:l,messagesRepository:o,lexemeCreator:c,searchLinker:g,authenticationLinker:h,tracker:m,urlLauncher:p})}module.exports=zr;
