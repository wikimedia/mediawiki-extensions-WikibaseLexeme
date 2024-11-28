/*!/*@nomin*/
"use strict";var Fe=Object.defineProperty,Oe=Object.defineProperties;var Pe=Object.getOwnPropertyDescriptors;var te=Object.getOwnPropertySymbols;var je=Object.prototype.hasOwnProperty,Be=Object.prototype.propertyIsEnumerable;var D=(e,r,n)=>r in e?Fe(e,r,{enumerable:!0,configurable:!0,writable:!0,value:n}):e[r]=n,C=(e,r)=>{for(var n in r||(r={}))je.call(r,n)&&D(e,n,r[n]);if(te)for(var n of te(r))Be.call(r,n)&&D(e,n,r[n]);return e},k=(e,r)=>Oe(e,Pe(r));var b=(e,r,n)=>D(e,typeof r!="symbol"?r+"":r,n);var w=(e,r,n)=>new Promise((a,u)=>{var i=c=>{try{s(n.next(c))}catch(d){u(d)}},l=c=>{try{s(n.throw(c))}catch(d){u(d)}},s=c=>c.done?a(c.value):Promise.resolve(c.value).then(i,l);s((n=n.apply(e,r)).next())});const t=require("vue"),N=require("vuex"),E=require("@wikimedia/codex"),De={action:"wbsearchentities",type:"item",limit:"10"};function Ge(e){return e.search.map(r=>({id:r.id,display:r.display}))}class We{constructor(r,n){b(this,"api");b(this,"languageCode");this.api=r,this.languageCode=n}searchItems(u,i){return w(this,arguments,function*(r,n,a={}){const l=yield this.api.get(C(k(C({},De),{search:r,language:this.languageCode,continue:n}),a));return Ge(l)})}}class He{constructor(r,n){this.getUrl=r,this.lexemeNamespaceId=n}getSearchUrlForLexeme(r){return this.getUrl("Special:Search",{search:r,[`ns${this.lexemeNamespaceId}`]:1})}}class Ke{constructor(r){this.trackFunction=r}increment(r){this.trackFunction(`counter.MediaWiki.${r}`,1)}}const me=Symbol("ItemSearch");function qe(){return t.inject(me,()=>{throw new Error("No ItemSearcher provided!")},!0)}const pe=Symbol("Config");function A(){return t.inject(pe,()=>{throw new Error("No Config provided!")},!0)}const fe=Symbol("SearchLinker");function Xe(){return t.inject(fe,()=>{throw new Error("No SearchLinker provided!")},!0)}const he=Symbol("AuthenticationLinker");function Ye(){return t.inject(he,()=>{throw new Error("No AuthenticationLinker provided!")},!0)}const q="setLemma",H="setLanguage",X="setLanguageSearchInput",Y="setLexicalCategory",J="setLexicalCategorySearchInput",B="setSpellingVariant",Q="setSpellingVariantSearchInput",K="setLanguageCodeFromLanguageItem",ve="addErrors",M="addPerFieldError",F="clearPerFieldErrors",ye="clearErrors",Je={[q](e,r){e.lemma=r},[H](e,r){e.language=r},[X](e,r){e.languageSearchInput=r},[Y](e,r){e.lexicalCategory=r},[J](e,r){e.lexicalCategorySearchInput=r},[B](e,r){e.spellingVariant=r},[Q](e,r){e.spellingVariantSearchInput=r},[K](e,r){e.languageCodeFromLanguageItem=r},[ve](e,r){e.globalErrors.push(...r)},[M](e,r){e.perFieldErrors[r.field].push(r.error)},[F](e,r){e.perFieldErrors[r]=[]},[ye](e){e.globalErrors=[]}},xe="createLexeme",_e="handleLanguageChange",be="initFromParams",G="handleItemLanguageCode",re="assembleValidInputs";function Qe(e,r,n,a){return{[re]({state:i,commit:l}){const s={};if(i.lemma?s.validLemma=i.lemma:l(M,{field:"lemmaErrors",error:{messageKey:"wikibaselexeme-newlexeme-lemma-empty-error"}}),i.language)s.validLanguageId=i.language.id;else{let d;i.languageSearchInput?d="wikibaselexeme-newlexeme-language-invalid-error":d="wikibaselexeme-newlexeme-language-empty-error",l(M,{field:"languageErrors",error:{messageKey:d}})}if(i.lexicalCategory)s.validLexicalCategoryId=i.lexicalCategory.id;else{let d;i.lexicalCategorySearchInput?d="wikibaselexeme-newlexeme-lexicalcategory-invalid-error":d="wikibaselexeme-newlexeme-lexicalcategory-empty-error",l(M,{field:"lexicalCategoryErrors",error:{messageKey:d}})}if(i.language)if(i.spellingVariant)s.validSpellingVariant=i.spellingVariant;else if(i.languageCodeFromLanguageItem)s.validSpellingVariant=i.languageCodeFromLanguageItem;else{let d;i.spellingVariantSearchInput?d="wikibaselexeme-newlexeme-lemma-language-invalid-error":d="wikibaselexeme-newlexeme-lemma-language-empty-error",l(M,{field:"spellingVariantErrors",error:{messageKey:d}})}if(!(d=>!!d.validLemma&&!!d.validLanguageId&&!!d.validLexicalCategoryId&&!!d.validSpellingVariant)(s))throw new Error("Not all fields are valid");return s},[xe](s){return w(this,arguments,function*({commit:i,dispatch:l}){const{validLemma:c,validLanguageId:d,validLexicalCategoryId:h,validSpellingVariant:m}=yield l(re);i(ye);try{const p=yield e.createLexeme(c,m,d,h);return a.increment("wikibase.lexeme.special.NewLexeme.js.create"),p}catch(p){return i(ve,p),Promise.reject(null)}})},[_e](d,h){return w(this,arguments,function*({state:i,commit:l,dispatch:s},c){const m=i.language;if(l(H,c),(m==null?void 0:m.id)===(c==null?void 0:c.id)||(l(K,void 0),l(B,""),!c))return;const p=yield r.getLanguageCodeFromItem(c.id);yield s(G,p)})},[be](c,d){return w(this,arguments,function*({commit:i,dispatch:l},s){var h,m,p,v;s.lemma!==void 0&&i(q,s.lemma),s.spellVarCode!==void 0&&(i(B,s.spellVarCode),i(Q,s.spellVarCode)),s.language!==void 0&&(i(H,{id:s.language.id,display:s.language.display}),yield l(G,s.language.languageCode),i(X,(m=(h=s.language.display.label)==null?void 0:h.value)!=null?m:s.language.id)),s.lexicalCategory!==void 0&&(i(Y,s.lexicalCategory),i(J,(v=(p=s.lexicalCategory.display.label)==null?void 0:p.value)!=null?v:s.lexicalCategory.id))})},[G](s,c){return w(this,arguments,function*({commit:i},l){typeof l=="string"&&(l=l.toLowerCase()),typeof l=="string"&&!n.isValid(l)&&(l=!1),i(K,l)})}}}function ze({lexemeCreator:e,langCodeRetriever:r,languageCodesProvider:n,tracker:a}){return N.createStore({state(){return{lemma:"",language:null,languageSearchInput:"",languageCodeFromLanguageItem:void 0,lexicalCategory:null,lexicalCategorySearchInput:"",spellingVariant:"",spellingVariantSearchInput:"",globalErrors:[],perFieldErrors:{lemmaErrors:[],languageErrors:[],lexicalCategoryErrors:[],spellingVariantErrors:[]}}},mutations:Je,actions:Qe(e,r,n,a)})}const Ze=t.defineComponent({__name:"WarningMessage",setup(e){return(r,n)=>(t.openBlock(),t.createBlock(t.unref(E.CdxMessage),{type:"warning"},{default:t.withCtx(()=>[t.renderSlot(r.$slots,"default")],void 0,!0),_:3}))}});class we{constructor(r){b(this,"messagesRepository");if(!r){this.messagesRepository={get(n){return`⧼${n}⧽`},getText(n){return`⧼${n}⧽`}};return}this.messagesRepository=r}get(r,...n){return this.messagesRepository.get(r,...n)}getUnescaped(r,...n){return this.messagesRepository.getText(r,...n)}}const Ee=Symbol("Messages");function T(){return t.inject(Ee,new we)}const et=["innerHTML"],tt=t.defineComponent({__name:"AnonymousEditWarning",setup(e){const r=Ye(),n=T(),a=A();let u="wikibase-anonymouseditwarning";a.tempUserEnabled&&(u="wikibase-anonymouseditnotificationtempuser");const i=t.computed(()=>n.get(u,r.getLoginLink(),r.getCreateAccountLink()));return(l,s)=>t.unref(a).isAnonymous?(t.openBlock(),t.createBlock(Ze,{key:0,class:"wbl-snl-anonymous-edit-warning"},{default:t.withCtx(()=>[t.createElementVNode("span",{innerHTML:i.value},null,8,et)],void 0,!0),_:1})):t.createCommentVNode("",!0)}}),O=(e,r)=>{const n=e.__vccOpts||e;for(const[a,u]of r)n[a]=u;return n},rt=O(tt,[["__scopeId","data-v-1a7220a5"]]),nt=["title"],at={compatConfig:{MODE:3}},ot=t.defineComponent(k(C({},at),{__name:"RequiredAsterisk",setup(e){const r=T();return(n,a)=>(t.openBlock(),t.createElementBlock("span",{class:"wbl-snl-required-asterisk","aria-hidden":"true",title:t.unref(r).getUnescaped("wikibaselexeme-form-field-required")},"*",8,nt))}})),P=O(ot,[["__scopeId","data-v-18d90fe6"]]),st={compatConfig:{MODE:3}},it=t.defineComponent(k(C({},st),{__name:"LemmaInput",props:{modelValue:{}},emits:["update:modelValue"],setup(e){const r=e,n=T(),a=A(),u=a.placeholderExampleData.lemma,i=N.useStore(),l=t.computed(()=>Array.from(r.modelValue).length>a.maxLemmaLength?{status:"error",messages:{error:n.getUnescaped("wikibaselexeme-newlexeme-lemma-too-long-error",a.maxLemmaLength.toString())}}:i.state.perFieldErrors.lemmaErrors.length?{status:"error",messages:{error:n.getUnescaped(i.state.perFieldErrors.lemmaErrors[0].messageKey)}}:{status:"default",messages:{}});return(s,c)=>(t.openBlock(),t.createBlock(t.unref(E.CdxField),{class:"wbl-snl-lemma-input",status:l.value.status,messages:l.value.messages},{label:t.withCtx(()=>[t.createTextVNode(t.toDisplayString(t.unref(n).getUnescaped("wikibaselexeme-newlexeme-lemma")),1),t.createVNode(P)]),default:t.withCtx(()=>[t.createVNode(t.unref(E.CdxTextInput),{placeholder:t.unref(n).getUnescaped("wikibaselexeme-newlexeme-lemma-placeholder-with-example",t.unref(u)),name:"lemma","aria-required":"true","model-value":s.modelValue,"onUpdate:modelValue":c[0]||(c[0]=d=>s.$emit("update:modelValue",d))},null,8,["placeholder","model-value"])],void 0,!0),_:1},8,["status","messages"]))}}));var j=typeof globalThis!="undefined"?globalThis:typeof window!="undefined"?window:typeof global!="undefined"?global:typeof self!="undefined"?self:{};function Se(e){return e&&e.__esModule&&Object.prototype.hasOwnProperty.call(e,"default")?e.default:e}function lt(e){var r=typeof e;return e!=null&&(r=="object"||r=="function")}var Le=lt,ut=typeof j=="object"&&j&&j.Object===Object&&j,ct=ut,dt=ct,gt=typeof self=="object"&&self&&self.Object===Object&&self,mt=dt||gt||Function("return this")(),Ie=mt,pt=Ie,ft=function(){return pt.Date.now()},ht=ft,vt=/\s/;function yt(e){for(var r=e.length;r--&&vt.test(e.charAt(r)););return r}var xt=yt,_t=xt,bt=/^\s+/;function wt(e){return e&&e.slice(0,_t(e)+1).replace(bt,"")}var Et=wt,St=Ie,Lt=St.Symbol,z=Lt,ne=z,Ce=Object.prototype,It=Ce.hasOwnProperty,Ct=Ce.toString,R=ne?ne.toStringTag:void 0;function kt(e){var r=It.call(e,R),n=e[R];try{e[R]=void 0;var a=!0}catch(i){}var u=Ct.call(e);return a&&(r?e[R]=n:delete e[R]),u}var Tt=kt,Vt=Object.prototype,Ut=Vt.toString;function Nt(e){return Ut.call(e)}var At=Nt,ae=z,$t=Tt,Rt=At,Mt="[object Null]",Ft="[object Undefined]",oe=ae?ae.toStringTag:void 0;function Ot(e){return e==null?e===void 0?Ft:Mt:oe&&oe in Object(e)?$t(e):Rt(e)}var Pt=Ot;function jt(e){return e!=null&&typeof e=="object"}var Bt=jt,Dt=Pt,Gt=Bt,Wt="[object Symbol]";function Ht(e){return typeof e=="symbol"||Gt(e)&&Dt(e)==Wt}var ke=Ht,Kt=Et,se=Le,qt=ke,ie=NaN,Xt=/^[-+]0x[0-9a-f]+$/i,Yt=/^0b[01]+$/i,Jt=/^0o[0-7]+$/i,Qt=parseInt;function zt(e){if(typeof e=="number")return e;if(qt(e))return ie;if(se(e)){var r=typeof e.valueOf=="function"?e.valueOf():e;e=se(r)?r+"":r}if(typeof e!="string")return e===0?e:+e;e=Kt(e);var n=Yt.test(e);return n||Jt.test(e)?Qt(e.slice(2),n?2:8):Xt.test(e)?ie:+e}var Zt=zt,er=Le,W=ht,le=Zt,tr="Expected a function",rr=Math.max,nr=Math.min;function ar(e,r,n){var a,u,i,l,s,c,d=0,h=!1,m=!1,p=!0;if(typeof e!="function")throw new TypeError(tr);r=le(r)||0,er(n)&&(h=!!n.leading,m="maxWait"in n,i=m?rr(le(n.maxWait)||0,r):i,p="trailing"in n?!!n.trailing:p);function v(o){var g=a,f=u;return a=u=void 0,d=o,l=e.apply(f,g),l}function L(o){return d=o,s=setTimeout(I,r),h?v(o):l}function U(o){var g=o-c,f=o-d,$=r-g;return m?nr($,i-f):$}function V(o){var g=o-c,f=o-d;return c===void 0||g>=r||g<0||m&&f>=i}function I(){var o=W();if(V(o))return S(o);s=setTimeout(I,U(o))}function S(o){return s=void 0,p&&a?v(o):(a=u=void 0,l)}function x(){s!==void 0&&clearTimeout(s),d=0,a=c=u=s=void 0}function y(){return s===void 0?l:S(W())}function _(){var o=W(),g=V(o);if(a=arguments,u=this,c=o,g){if(s===void 0)return L(c);if(m)return clearTimeout(s),s=setTimeout(I,r),v(c)}return s===void 0&&(s=setTimeout(I,r)),l}return _.cancel=x,_.flush=y,_}var or=ar;const sr=Se(or);function ir(e,r){for(var n=-1,a=e==null?0:e.length,u=Array(a);++n<a;)u[n]=r(e[n],n,e);return u}var lr=ir,ur=Array.isArray,cr=ur,ue=z,dr=lr,gr=cr,mr=ke,pr=1/0,ce=ue?ue.prototype:void 0,de=ce?ce.toString:void 0;function Te(e){if(typeof e=="string")return e;if(gr(e))return dr(e,Te)+"";if(mr(e))return de?de.call(e):"";var r=e+"";return r=="0"&&1/e==-pr?"-0":r}var fr=Te,hr=fr;function vr(e){return e==null?"":hr(e)}var yr=vr,xr=yr,Ve=/[\\^$.*+?()[\]{}|]/g,_r=RegExp(Ve.source);function br(e){return e=xr(e),e&&_r.test(e)?e.replace(Ve,"\\$&"):e}var wr=br;const Ue=Se(wr),Er=t.defineComponent({__name:"ItemLookup",props:{label:{},placeholder:{},value:{},searchForItems:{},searchInput:{default:""},error:{default:null},itemSuggestions:{default:()=>[]},ariaRequired:{type:Boolean,default:!1}},emits:{"update:modelValue":e=>e===null||/^Q\d+$/.test(e.id),"update:searchInput":null},setup(e,{emit:r}){const n=e,a=r,u=t.ref(null),i=t.computed(()=>{const o=new RegExp(`\\b${Ue(n.searchInput)}`,"i");return n.itemSuggestions.filter(g=>{var f;return o.test(((f=g.display.label)==null?void 0:f.value)||"")})}),l=t.ref([]),s=t.computed(()=>{const o=[...i.value,...l.value.filter(g=>!i.value.some(f=>f.id===g.id))];return!o.length&&n.value&&o.push(n.value),o}),c=t.ref(null),d=o=>{c.value=o,a("update:modelValue",o)},h=o=>{const g=s.value.find(f=>f.id===o);return d(g!=null?g:null)},m=sr(o=>w(this,null,function*(){l.value=yield n.searchForItems(o)}),150),p=t.ref(null),v=o=>{var f,$,Z;if(p.value===o)return;p.value=o;const g=s.value.find(Me=>{var ee;return((ee=Me.display.label)==null?void 0:ee.value)===o});if(o.trim()===""){l.value=[];return}o===(($=(f=c.value)==null?void 0:f.display.label)==null?void 0:$.value)||o===((Z=c.value)==null?void 0:Z.id)||(h((g==null?void 0:g.id)||null),m(o))},L=()=>w(this,null,function*(){const o=yield n.searchForItems(n.searchInput,l.value.length);l.value=[...l.value,...o]});function U(o){var g,f;return{label:((g=o.display.label)==null?void 0:g.value)||o.id,description:((f=o.display.description)==null?void 0:f.value)||"",value:o.id}}const V=t.computed(()=>s.value.map(U)),I=T(),S={visibleItemLimit:6,boldLabel:!0},x=t.computed(()=>n.error?n.error.type:"default"),y=t.computed(()=>{if(n.error){if(n.error.type==="error")return{error:n.error.message};if(n.error.type==="warning")return{warning:n.error.message}}return{}}),_=E.useModelWrapper(t.toRef(n,"searchInput"),a,"update:searchInput");return(o,g)=>(t.openBlock(),t.createBlock(t.unref(E.CdxField),{status:x.value,messages:y.value},{label:t.withCtx(()=>[t.createTextVNode(t.toDisplayString(o.label),1),o.ariaRequired?(t.openBlock(),t.createBlock(P,{key:0})):t.createCommentVNode("",!0)]),default:t.withCtx(()=>[t.createVNode(t.unref(E.CdxLookup),{selected:u.value,"onUpdate:selected":[g[0]||(g[0]=f=>u.value=f),h],"input-value":t.unref(_),"onUpdate:inputValue":g[1]||(g[1]=f=>t.isRef(_)?_.value=f:null),"aria-required":o.ariaRequired,placeholder:o.placeholder,"menu-items":V.value,"menu-config":S,onLoadMore:L,onInput:v},{"no-results":t.withCtx(()=>[t.createTextVNode(t.toDisplayString(t.unref(I).getUnescaped("wikibase-entityselector-notfound")),1)]),_:1},8,["selected","input-value","aria-required","placeholder","menu-items"])],void 0,!0),_:1},8,["status","messages"]))}}),Ne=O(Er,[["__scopeId","data-v-3aae3495"]]),Ae=Symbol("LanguageItemSearch");function Sr(){return t.inject(Ae,()=>{throw new Error("No LanguageItemSearcher provided!")},!0)}const Lr={class:"wbl-snl-language-lookup"},Ir={compatConfig:{MODE:3}},Cr=t.defineComponent(k(C({},Ir),{__name:"LanguageInput",props:{modelValue:{},searchInput:{}},emits:["update:modelValue","update:searchInput"],setup(e,{emit:r}){const n=e,a=r,u=T(),i=Sr(),l=i.searchItems.bind(i),s=N.useStore(),c=t.computed(()=>s.state.perFieldErrors.languageErrors.length?{type:"error",message:u.getUnescaped(s.state.perFieldErrors.languageErrors[0].messageKey)}:s.state.languageCodeFromLanguageItem!==!1?null:{type:"warning",message:u.getUnescaped("wikibaselexeme-newlexeme-invalid-language-code-warning")}),d=A(),h=E.useModelWrapper(t.toRef(n,"searchInput"),a,"update:searchInput");return(m,p)=>(t.openBlock(),t.createElementBlock("div",Lr,[t.createVNode(Ne,{"search-input":t.unref(h),"onUpdate:searchInput":[p[0]||(p[0]=v=>t.isRef(h)?h.value=v:null),p[2]||(p[2]=v=>m.$emit("update:searchInput",v))],label:t.unref(u).getUnescaped("wikibaselexeme-newlexeme-language"),placeholder:t.unref(u).getUnescaped("wikibaselexeme-newlexeme-language-placeholder-with-example",t.unref(d).placeholderExampleData.languageLabel),value:m.modelValue,"search-for-items":t.unref(l),error:c.value,"aria-required":!0,"onUpdate:modelValue":p[1]||(p[1]=v=>m.$emit("update:modelValue",v))},{suffix:t.withCtx(()=>[t.createVNode(P)]),_:1},8,["search-input","label","placeholder","value","search-for-items","error"])]))}})),$e=Symbol("LanguageCodesProvider");function kr(){return t.inject($e,()=>{throw new Error("No LanguageCodesProvider provided!")},!0)}const Tr={class:"wbl-snl-spelling-variant-lookup__help-link"},Vr=["href"],Ur={compatConfig:{MODE:3}},Nr=t.defineComponent(k(C({},Ur),{__name:"SpellingVariantInput",props:{searchInput:{default:""}},emits:{"update:modelValue":e=>e==null||typeof e=="string"&&e.length>0,"update:searchInput":null},setup(e,{emit:r}){const n=e,a=kr(),u=T(),i=[];a.getLanguages().forEach((x,y)=>{i.push({label:u.getUnescaped("wikibase-lexeme-lemma-language-option",x,y),value:y,description:""})});const l=t.ref([]),s=r,c=t.ref(null),d=x=>{if(c.value===x)return;if(c.value=x,s("update:searchInput",x),x.trim()===""){l.value=[];return}const y=new RegExp(`\\b${Ue(x)}`,"i");l.value=i.filter(_=>_.label&&y.test(_.label))},h=t.ref(null),m=x=>{const y=l.value.find(_=>_.value===x);s("update:modelValue",(y==null?void 0:y.value.toString())||void 0)},p=A(),v=N.useStore(),L=u.get("wikibaselexeme-newlexeme-lemma-language-help-link-target"),U=u.get("wikibaselexeme-newlexeme-lemma-language-help-link-text"),V=t.computed(()=>v.state.perFieldErrors.spellingVariantErrors.length?"error":"default"),I=t.computed(()=>v.state.perFieldErrors.spellingVariantErrors.length?{error:u.getUnescaped(v.state.perFieldErrors.spellingVariantErrors[0].messageKey)}:{}),S=E.useModelWrapper(t.toRef(n,"searchInput"),s,"update:searchInput");return(x,y)=>(t.openBlock(),t.createBlock(t.unref(E.CdxField),{class:"wbl-snl-spelling-variant-lookup",status:V.value,messages:I.value},{label:t.withCtx(()=>[t.createTextVNode(t.toDisplayString(t.unref(u).getUnescaped("wikibaselexeme-newlexeme-lemma-language")),1),t.createVNode(P),t.createElementVNode("span",Tr,[t.createElementVNode("a",{href:t.unref(L),target:"_blank"},t.toDisplayString(t.unref(U)),9,Vr)])]),default:t.withCtx(()=>[t.createVNode(t.unref(E.CdxLookup),{selected:h.value,"onUpdate:selected":[y[0]||(y[0]=_=>h.value=_),m],"input-value":t.unref(S),"onUpdate:inputValue":y[1]||(y[1]=_=>t.isRef(S)?S.value=_:null),placeholder:t.unref(u).getUnescaped("wikibaselexeme-newlexeme-lemma-language-placeholder-with-example",t.unref(p).placeholderExampleData.spellingVariant),"menu-items":l.value,onInput:d},{"no-results":t.withCtx(()=>[t.createTextVNode(t.toDisplayString(t.unref(u).getUnescaped("wikibase-entityselector-notfound")),1)]),_:1},8,["selected","input-value","placeholder","menu-items"])],void 0,!0),_:1},8,["status","messages"]))}})),Ar={class:"wbl-snl-lexical-category-lookup"},$r={compatConfig:{MODE:3}},Rr=t.defineComponent(k(C({},$r),{__name:"LexicalCategoryInput",props:{modelValue:{},searchInput:{}},emits:["update:modelValue","update:searchInput"],setup(e){const r=T(),n=qe(),a=n.searchItems.bind(n),u=A(),i=u.lexicalCategorySuggestions,l=u.placeholderExampleData.lexicalCategoryLabel,s=N.useStore(),c=t.computed(()=>s.state.perFieldErrors.lexicalCategoryErrors.length?{type:"error",message:r.getUnescaped(s.state.perFieldErrors.lexicalCategoryErrors[0].messageKey)}:null);return(d,h)=>(t.openBlock(),t.createElementBlock("div",Ar,[t.createVNode(Ne,{label:t.unref(r).getUnescaped("wikibaselexeme-newlexeme-lexicalcategory"),placeholder:t.unref(r).getUnescaped("wikibaselexeme-newlexeme-lexicalcategory-placeholder-with-example",t.unref(l)),value:d.modelValue,"search-input":d.searchInput,"search-for-items":t.unref(a),"item-suggestions":t.unref(i),error:c.value,"aria-required":!0,"onUpdate:modelValue":h[0]||(h[0]=m=>d.$emit("update:modelValue",m)),"onUpdate:searchInput":h[1]||(h[1]=m=>d.$emit("update:searchInput",m))},{suffix:t.withCtx(()=>[t.createVNode(P)]),_:1},8,["label","placeholder","value","search-input","search-for-items","item-suggestions","error"])]))}})),Mr=t.defineComponent({__name:"ErrorMessage",setup(e){return(r,n)=>(t.openBlock(),t.createBlock(t.unref(E.CdxMessage),{type:"error"},{default:t.withCtx(()=>[t.renderSlot(r.$slots,"default")],void 0,!0),_:3}))}}),Re=Symbol("UrlLauncher");function Fr(){return t.inject(Re,()=>{throw new Error("No UrlLauncher provided!")},!0)}const Or={class:"wbl-snl-form"},Pr=["innerHTML"],jr=["innerHTML"],Br={compatConfig:{MODE:3}},Dr=t.defineComponent(k(C({},Br),{__name:"NewLexemeForm",setup(e){const r=A(),n=T(),a=N.useStore(),u=t.computed({get(){return a.state.lemma},set(o){a.commit(q,o),o.trim().length>0&&a.commit(F,"lemmaErrors")}}),i=t.computed({get(){return a.state.language},set(o){return w(this,null,function*(){yield a.dispatch(_e,o),o&&a.commit(F,"languageErrors")})}}),l=t.computed({get(){return a.state.languageSearchInput},set(o){a.commit(X,o)}}),s=t.computed({get(){return a.state.lexicalCategory},set(o){a.commit(Y,o),o&&a.commit(F,"lexicalCategoryErrors")}}),c=t.computed({get(){return a.state.lexicalCategorySearchInput},set(o){a.commit(J,o)}}),d=t.computed(()=>a.state.languageCodeFromLanguageItem===null||a.state.languageCodeFromLanguageItem===!1),h=t.computed({get(){return a.state.spellingVariant},set(o){a.commit(B,o),o&&a.commit(F,"spellingVariantErrors")}}),m=t.computed({get(){return a.state.spellingVariantSearchInput},set(o){a.commit(Q,o)}}),p=t.ref(!1),v=n.getUnescaped("wikibaselexeme-newlexeme-submit"),L=n.getUnescaped("wikibaselexeme-newlexeme-submitting"),U=n.get("copyrightpage"),V=n.get("wikibase-shortcopyrightwarning",v,U,r.licenseUrl,r.licenseName),I=t.computed(()=>p.value?L:v),S=t.computed(()=>{if(a.state.globalErrors.length>0){const o=a.state.globalErrors[0];return o.message?o.message:n.getUnescaped("wikibaselexeme-newlexeme-submit-error")}return null}),x=Fr(),y=()=>w(this,null,function*(){p.value=!0;try{const o=yield a.dispatch(xe);yield x.goToURL(o)}catch(o){}p.value=!1});return(o,g)=>(t.openBlock(),t.createElementBlock("form",Or,[t.createVNode(it,{modelValue:u.value,"onUpdate:modelValue":g[0]||(g[0]=f=>u.value=f)},null,8,["modelValue"]),t.createVNode(Cr,{modelValue:i.value,"onUpdate:modelValue":g[1]||(g[1]=f=>i.value=f),"search-input":l.value,"onUpdate:searchInput":g[2]||(g[2]=f=>l.value=f)},null,8,["modelValue","search-input"]),d.value?(t.openBlock(),t.createBlock(Nr,{key:0,modelValue:h.value,"onUpdate:modelValue":g[3]||(g[3]=f=>h.value=f),"search-input":m.value,"onUpdate:searchInput":g[4]||(g[4]=f=>m.value=f)},null,8,["modelValue","search-input"])):t.createCommentVNode("",!0),t.createVNode(Rr,{modelValue:s.value,"onUpdate:modelValue":g[5]||(g[5]=f=>s.value=f),"search-input":c.value,"onUpdate:searchInput":g[6]||(g[6]=f=>c.value=f)},null,8,["modelValue","search-input"]),t.createElementVNode("p",{class:"wbl-snl-copyright",innerHTML:t.unref(V)},null,8,Pr),S.value?(t.openBlock(),t.createBlock(Mr,{key:1},{default:t.withCtx(()=>[t.createElementVNode("span",{innerHTML:S.value},null,8,jr)],void 0,!0),_:1})):t.createCommentVNode("",!0),t.createElementVNode("div",null,[t.createVNode(t.unref(E.CdxButton),{class:"form-button-submit",action:"progressive",weight:"primary",type:"submit",disabled:p.value,onClick:t.withModifiers(y,["prevent"])},{default:t.withCtx(()=>[t.createTextVNode(t.toDisplayString(I.value),1)],void 0,!0),_:1},8,["disabled"])])]))}})),Gr=O(Dr,[["__scopeId","data-v-498489a1"]]),Wr=["innerHTML"],Hr=t.defineComponent({__name:"SearchExisting",setup(e){const r=T(),n=Xe(),a=N.useStore(),u=t.computed(()=>{const l=a.state.lemma;return n.getSearchUrlForLexeme(l)}),i=t.computed(()=>r.get("wikibaselexeme-newlexeme-search-existing",u.value));return(l,s)=>(t.openBlock(),t.createElementBlock("p",{class:"wbl-snl-search-existing",innerHTML:i.value},null,8,Wr))}}),Kr=O(Hr,[["__scopeId","data-v-40a94da9"]]),qr={class:"wbl-snl-app"},Xr=t.defineComponent({__name:"App",setup(e){return(r,n)=>(t.openBlock(),t.createElementBlock(t.Fragment,null,[(t.openBlock(),t.createBlock(t.Teleport,{to:"#wbl-snl-intro-text-wrapper"},[t.createVNode(Kr),t.createVNode(rt)])),t.createElementVNode("div",qr,[t.createVNode(Gr)])],64))}});class Yr{constructor(r){b(this,"validLanguages");this.validLanguages=r}getLanguages(){return this.validLanguages}isValid(r){return this.validLanguages.has(r)}}function Jr(e,r){const n=t.createApp(Xr),a=new Yr(e.wikibaseLexemeTermLanguages),u=ze(k(C({},r),{languageCodesProvider:a}));return n.use(u),e.initParams!==void 0&&u.dispatch(be,e.initParams),n.provide(pe,e),n.provide(Ee,new we(r.messagesRepository)),n.provide(me,r.itemSearcher),n.provide(Ae,r.languageItemSearcher),n.provide(fe,r.searchLinker),n.provide(he,r.authenticationLinker),n.provide(Re,r.urlLauncher),n.provide($e,a),n.mount(e.rootSelector)}class Qr{constructor(r,n,a=[]){b(this,"api");b(this,"getUrl");b(this,"tags");this.api=r,this.getUrl=n,this.tags=a}createLexeme(r,n,a,u){return w(this,null,function*(){const i=this.api.assertCurrentUser({action:"wbeditentity",new:"lexeme",tags:this.tags,data:JSON.stringify({lemmas:{[n]:{language:n,value:r}},language:a,lexicalCategory:u}),errorformat:"html",formatversion:2}),s=yield this.api.postWithEditToken(i).catch((d,h,m)=>{let p;try{m&&m.errors?p=m.errors.map(v=>{const L={type:v.code};return v.html&&(L.message=v.html),L}):p=[{type:d}]}catch(v){console.error("Unexpected API result",m,v),p=[{type:"assertionerror"}]}return Promise.reject(p)}),c=window.location.href;return s.tempuserredirect?new URL(s.tempuserredirect,c):new URL(this.getUrl(`Special:EntityPage/${s.entity.id}`),c)})}}class zr{constructor(r){b(this,"mwMessages");this.mwMessages=r}get(r,...n){return this.mwMessages(r,...n).parse()}getText(r,...n){return this.mwMessages(r,...n).text()}}class Zr{goToURL(r){return window.location.href=r.toString(),new Promise(n=>{})}}function ge(e){var r,n;switch(e.mainsnak.snaktype){case"value":break;case"somevalue":return!1;case"novalue":return null;default:throw new Error(`Unexpected snak type ${e.mainsnak.snaktype}!`)}if(((r=e.mainsnak.datavalue)==null?void 0:r.type)!=="string")throw new Error(`Expected ${e.mainsnak.property} to have DataValueType "string" but got "${(n=e.mainsnak.datavalue)==null?void 0:n.type}"!`);return e.mainsnak.datavalue.value}function en(e,r){if(!e.claims[r])return null;const n=e.claims[r].filter(u=>u.rank==="preferred");if(n.length!==0)return ge(n[0]);const a=e.claims[r].filter(u=>u.rank==="normal");return a.length!==0?ge(a[0]):null}class tn{constructor(r,n){b(this,"api");b(this,"languageCodeProperty");this.api=r,this.languageCodeProperty=n}getLanguageCodeFromItem(r){return w(this,null,function*(){if(!this.languageCodeProperty)return null;const n=yield this.api.get({action:"wbgetclaims",entity:r,property:this.languageCodeProperty,props:""}).catch((a,u,i)=>(console.warn(`Error while retrieving language code in ${this.languageCodeProperty} for item ${r}: ${a}`,i),!1));return n===!1?!1:en(n,this.languageCodeProperty)})}}class rn{constructor(r,n){b(this,"LANGUAGE_PROFILE_NAME","language");b(this,"itemSearcher");b(this,"useLanguageProfile");this.itemSearcher=r,this.useLanguageProfile=n.includes(this.LANGUAGE_PROFILE_NAME)}searchItems(r,n){return w(this,null,function*(){const a={};return this.useLanguageProfile&&(a.profile=this.LANGUAGE_PROFILE_NAME),this.itemSearcher.searchItems(r,n,a)})}}class nn{constructor(r,n){this.getUrl=r,this.currentPage=n}getCreateAccountLink(){return this.getUrl("Special:CreateAccount",{returnto:this.currentPage})}getLoginLink(){return this.getUrl("Special:UserLogin",{returnto:this.currentPage})}}function an(e,r){const n=r.config.get("wgUserLanguage"),a=new r.Api({parameters:{formatversion:2,uselang:n,errorformat:"html"}}),u=new We(a,n),i=new rn(u,e.availableSearchProfiles),l=new tn(a,r.config.get("LexemeLanguageCodePropertyId")),s=new zr(r.message),c=new Qr(a,r.util.getUrl,e.tags),d=new He(r.util.getUrl,r.config.get("wgNamespaceIds").lexeme),h=new nn(r.util.getUrl,r.config.get("wgPageName")),m=new Ke(r.track),p=new Zr;return Jr(e,{itemSearcher:u,languageItemSearcher:i,langCodeRetriever:l,messagesRepository:s,lexemeCreator:c,searchLinker:d,authenticationLinker:h,tracker:m,urlLauncher:p})}module.exports=an;
