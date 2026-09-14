import{S as ce}from"./sortable.esm-D-EvzYhP.js";(function(){const b=window.__LIVE_EDITOR__;if(!b)return;const E=document.querySelector('meta[name="csrf-token"]').content,I={banner:"Banner",dual_banner:"Banner Doble",product_carousel:"Carrusel de Productos",product_carousel_banner:"Carrusel con Banner",category_grid:"Grid de Categorías",brand_carousel:"Carrusel de Marcas",html_block:"Bloque HTML",faq:"Preguntas Frecuentes",rich_header:"Encabezado enriquecido",content_tabs:"Descripción por secciones",benefits_grid:"Beneficios / características",process_steps:"Proceso / cómo funciona",gallery_carousel:"Galería / carrusel",rating_reviews:"Rating y reseñas",cta_final:"CTA final",button:"Botón",table_block:"Tabla"},G={banner:'<rect width="18" height="12" x="3" y="6" rx="2"/><path d="M3 10h18"/>',dual_banner:'<rect width="8" height="14" x="3" y="5" rx="1.5"/><rect width="8" height="14" x="13" y="5" rx="1.5"/>',product_carousel:'<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',product_carousel_banner:'<rect width="18" height="12" x="3" y="6" rx="2"/><circle cx="9" cy="12" r="2"/>',category_grid:'<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/>',brand_carousel:'<path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/>',html_block:'<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',faq:'<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" x2="12.01" y1="17" y2="17"/>',rich_header:'<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="2" x2="22" y1="9" y2="9"/>',content_tabs:'<path d="M21 15V6"/><path d="M18.5 18a2.5 2.5 0 1 0 0-5H8a2 2 0 1 0 0 4h10"/><path d="M3 3v18"/><path d="M14 6H3"/>',benefits_grid:'<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',process_steps:'<path d="M4 17V9a2 2 0 0 1 2-2h2"/><path d="m18 8 4 4-4 4"/><path d="M4 21v-2a2 2 0 0 1 2-2h2"/><path d="M14 3h6v6"/>',gallery_carousel:'<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',rating_reviews:'<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',cta_final:'<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',button:'<rect width="18" height="7" x="3" y="8.5" rx="3.5"/>',table_block:'<path d="M3 3h18v18H3z"/><path d="M3 9h18"/><path d="M3 15h18"/><path d="M9 3v18"/>',default:'<rect width="18" height="18" x="3" y="3" rx="2"/>'},de={featured:"Destacados",new:"Nuevos",recommended:"Recomendados",category:"Por Categoría",brand:"Por Marca",collection:"Por Colección",manual:"Selección Manual"};function Te(t){switch(t){case"banner":return{image_url:"",link_url:"",alt:""};case"dual_banner":return{left:{image_url:"",link_url:"",alt:""},right:{image_url:"",link_url:"",alt:""}};case"product_carousel":return{source:"featured",category_id:null,brand_id:null,collection_id:null,product_ids:[],limit:10};case"product_carousel_banner":return{banner_image_url:"",banner_link_url:"",banner_alt:"",source:"featured",category_id:null,brand_id:null,collection_id:null,product_ids:[],limit:10};case"category_grid":return{category_ids:[]};case"brand_carousel":return{};case"html_block":return{html:""};case"faq":return{description:""};case"rich_header":return{badges:[],whatsapp_text:"Cotizar por WhatsApp",meta_lines:[],background_image_ids:[],price_label:""};case"content_tabs":return{tabs:[]};case"benefits_grid":return{items:[],layout:"horizontal"};case"process_steps":return{steps:[]};case"gallery_carousel":return{image_ids:[]};case"rating_reviews":return{description:"",reviews_per_page:3};case"cta_final":return{headline:"",subtext:"",whatsapp_text:"Cotizar por WhatsApp",background_image_id:null,secondary_button:{text:"",url:"",style:"outline",color:"#ff6213"}};case"button":return{buttons:[{text:"Cotizar ahora",url:"",style:"solid",color:"#ff6213"}],align:"center"};case"table_block":return{title:"",description:"",headers:[],rows:[],buttons:[],align:"left"};default:return{}}}let U=0;const ue=()=>"u"+ ++U,k=(b.sections||[]).map(t=>({_uid:ue(),id:t.id??null,type:t.type,title:t.title??"",config:t.config&&typeof t.config=="object"?t.config:{},is_active:t.is_active!==!1}));let C=null,L="empty",pe=!1,W="desktop";const p=Object.assign({name:"",slug:"",short_description:"",price:"",currency:"MXN",show_price:!0,background_color:null,seo_title:"",seo_description:"",canonical_url:"",is_active:!1,faqs:[],rating_average_displayed:"",rating_total_rated:"",rating_recommend_percent:"",rating_punctuality_average:"",rating_recurring_clients:"",rating_since_year:"",rating_distribution:{}},b.general||{}),S=(b.reviews||[]).map(t=>Object.assign({},t)),P=document.getElementById("leBlocksList"),Re=document.getElementById("leAddBlockType"),Ae=document.getElementById("leAddBlockBtn"),w=document.getElementById("leEditPanel"),H=document.getElementById("leIframe"),j=document.getElementById("leDirtyIndicator"),M=document.getElementById("leSaveBtn"),ve=document.getElementById("leViewportToggle"),V=document.getElementById("leGeneralInfoBtn"),K=document.getElementById("leGalleryBtn"),J=document.getElementById("leReviewsBtn"),ge=document.querySelector(".live-editor-heading-row__left h1"),F=document.getElementById("leDeleteModal"),Me=document.getElementById("leDeleteModalTitle"),Be=document.getElementById("leDeleteModalAvatar"),Ie=document.getElementById("leDeleteModalCancel"),Ge=document.getElementById("leDeleteModalConfirm");let X=null;function Q(t,e){X=e,Me.textContent=t,Be.textContent=(t||"?").charAt(0).toUpperCase(),F.classList.add("active")}function Y(){X=null,F.classList.remove("active")}Ie.addEventListener("click",Y),F.addEventListener("click",t=>{t.target===F&&Y()}),Ge.addEventListener("click",()=>{const t=X;Y(),typeof t=="function"&&t()});function s(t){return String(t??"").replace(/&/g,"&amp;").replace(/"/g,"&quot;").replace(/</g,"&lt;").replace(/>/g,"&gt;")}function Pe(t){return String(t??"").toLowerCase().normalize("NFD").replace(/[^\x00-\x7F]/g,"").replace(/[^a-z0-9\s-]/g,"").trim().replace(/\s+/g,"-")}const He=["#000000","#141516","#374151","#4b5563","#6b7280","#9ca3af","#d1d5db","#f3f4f6","#ffffff","#ef4444","#f97316","#ff6213","#f59e0b","#eab308","#84cc16","#22c55e","#10b981","#14b8a6","#06b6d4","#0ea5e9","#3b82f6","#6366f1","#8b5cf6","#a855f7","#d946ef","#ec4899","#f43f5e","#7c2d12","#78350f","#365314","#134e4a","#1e3a8a","#4c1d95"],me="emb-custom-colors";function je(){try{const t=window.localStorage.getItem(me),e=t?JSON.parse(t):[];return Array.isArray(e)?e:[]}catch{return[]}}function ye(t){try{window.localStorage.setItem(me,JSON.stringify(t))}catch{}}function he(t){return/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(t)}function Z(t,e,a){let r=je();function n(i,v){return`<button type="button" class="le-color-swatch ${e&&e.toLowerCase()===i.toLowerCase()?"is-active":""}" data-hex="${i}" title="${i}" style="background:${i}">
                ${v?'<span class="le-color-swatch-remove" data-remove="'+i+'" title="Quitar de mis colores">&times;</span>':""}
            </button>`}function l(){t.innerHTML=`
                <div class="le-color-swatches">${He.map(o=>n(o,!1)).join("")}</div>
                ${r.length?`
                    <div class="le-color-custom-label">Mis colores</div>
                    <div class="le-color-swatches">${r.map(o=>n(o,!0)).join("")}</div>
                `:""}
                <div class="le-color-custom-row">
                    <input type="color" class="le-color-native" value="${he(e)?e:"#ff6213"}">
                    <input type="text" class="users-manager-input le-color-hex" placeholder="#ff6213" value="${s(e||"")}">
                    <button type="button" class="live-editor-btn live-editor-btn--outline le-color-save">Guardar</button>
                </div>
            `,t.querySelectorAll(".le-color-swatch").forEach(o=>{o.addEventListener("click",h=>{h.target.closest(".le-color-swatch-remove")||(a(o.dataset.hex),l())})}),t.querySelectorAll(".le-color-swatch-remove").forEach(o=>{o.addEventListener("click",h=>{h.stopPropagation();const y=o.dataset.remove;r=r.filter(m=>m!==y),ye(r),l()})});const i=t.querySelector(".le-color-native"),v=t.querySelector(".le-color-hex");i.addEventListener("input",()=>{v.value=i.value}),t.querySelector(".le-color-save").addEventListener("click",()=>{let o=v.value.trim();o&&(o.startsWith("#")||(o="#"+o),he(o)&&(r.includes(o)||(r=[...r,o],ye(r)),a(o),l()))})}l()}function B(t,e,a){a=a||{};const r=a.tagChoices||null,n=a.onChange||(()=>{}),l="leStyle"+ ++U;function i(){c(),n(),d()}const v=r?u("Etiqueta de encabezado",`
            <select class="users-manager-select" id="${l}Tag">
                ${r.map(o=>`<option value="${o}" ${(e.heading_tag||r[0])===o?"selected":""}>${o.toUpperCase()}</option>`).join("")}
            </select>
        `):"";t.innerHTML=`
            <p class="live-editor-col-title" style="margin:14px 0 8px;">Estilo del texto</p>
            ${v}
            <div class="live-editor-field-row">
                ${u("Alineación",`
                    <div class="le-align-toggle" id="${l}Align">
                        <button type="button" data-align="left" class="${(e.text_align||"left")==="left"?"is-active":""}" title="Izquierda">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="15" x2="3" y1="12" y2="12"/><line x1="17" x2="3" y1="18" y2="18"/></svg>
                        </button>
                        <button type="button" data-align="center" class="${e.text_align==="center"?"is-active":""}" title="Centro">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="17" x2="7" y1="12" y2="12"/><line x1="19" x2="5" y1="18" y2="18"/></svg>
                        </button>
                        <button type="button" data-align="right" class="${e.text_align==="right"?"is-active":""}" title="Derecha">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="21" x2="9" y1="12" y2="12"/><line x1="21" x2="7" y1="18" y2="18"/></svg>
                        </button>
                    </div>
                `)}
                ${u("Tamaño (px)",`<input type="number" class="users-manager-input" id="${l}Size" min="10" max="72" placeholder="Auto" value="${s(e.font_size||"")}">`)}
            </div>
            ${u("Familia tipográfica",`
                <select class="users-manager-select" id="${l}Family">
                    <option value="" ${e.font_family?"":"selected"}>Predeterminada (Inter)</option>
                    <option value="Inter Tight" ${e.font_family==="Inter Tight"?"selected":""}>Inter Tight</option>
                </select>
            `)}
            ${u("Color de texto",`<div id="${l}Color"></div>`)}
        `,r&&t.querySelector("#"+l+"Tag").addEventListener("change",o=>{e.heading_tag=o.target.value,i()}),t.querySelector("#"+l+"Align").addEventListener("click",o=>{const h=o.target.closest("button[data-align]");h&&(e.text_align=h.dataset.align,t.querySelectorAll("#"+l+"Align button").forEach(y=>y.classList.toggle("is-active",y===h)),i())}),t.querySelector("#"+l+"Size").addEventListener("input",o=>{const h=parseInt(o.target.value,10);e.font_size=Number.isFinite(h)?h:null,i()}),t.querySelector("#"+l+"Family").addEventListener("change",o=>{e.font_family=o.target.value||null,i()}),Z(t.querySelector("#"+l+"Color"),e.text_color,o=>{e.text_color=o,i()})}function ee(t,e,a){a=a||{};const r=a.alignField!==!1,n="leBtn"+ ++U;t.innerHTML=`
            ${u("Texto del botón",`<input type="text" class="users-manager-input" id="${n}Text" value="${s(e.text)}" placeholder="Cotizar ahora">`)}
            ${u("Enlace",`
                <div style="display:flex;gap:8px;">
                    <input type="text" class="users-manager-input" id="${n}Url" value="${s(e.url)}" placeholder="https:// o /servicios/..." style="flex:1;">
                    <button type="button" class="live-editor-btn live-editor-btn--outline" id="${n}LinkPick">Elegir enlace</button>
                </div>
            `)}
            <div class="live-editor-field-row">
                ${u("Estilo",`
                    <select class="users-manager-select" id="${n}Style">
                        <option value="solid" ${(e.style||"solid")==="solid"?"selected":""}>Sólido</option>
                        <option value="outline" ${e.style==="outline"?"selected":""}>Contorno</option>
                    </select>
                `)}
                ${r?u("Alineación",`
                    <select class="users-manager-select" id="${n}Align">
                        <option value="left" ${e.align==="left"?"selected":""}>Izquierda</option>
                        <option value="center" ${(e.align||"center")==="center"?"selected":""}>Centro</option>
                        <option value="right" ${e.align==="right"?"selected":""}>Derecha</option>
                    </select>
                `):""}
            </div>
            ${u("Color",`<div id="${n}Color"></div>`)}
        `,t.querySelector("#"+n+"Text").addEventListener("input",i=>{e.text=i.target.value,c(),d()});const l=t.querySelector("#"+n+"Url");l.addEventListener("input",i=>{e.url=i.target.value,c(),d()}),t.querySelector("#"+n+"LinkPick").addEventListener("click",i=>{typeof window.LinkPicker!="function"&&!(window.LinkPicker&&window.LinkPicker.open)||window.LinkPicker.open({anchorEl:i.target,onSelect:v=>{l.value=v,e.url=v,c(),d()}})}),t.querySelector("#"+n+"Style").addEventListener("change",i=>{e.style=i.target.value,c(),d()}),r&&t.querySelector("#"+n+"Align").addEventListener("change",i=>{e.align=i.target.value,c(),d()}),Z(t.querySelector("#"+n+"Color"),e.color||"#ff6213",i=>{e.color=i,c(),d()})}function te(t,e){Array.isArray(e.buttons)||(e.buttons=e.text||e.url?[{text:e.text||"",url:e.url||"",style:e.style||"solid",color:e.color||"#ff6213"}]:[{text:"Cotizar ahora",url:"",style:"solid",color:"#ff6213"}]),e.align=e.align||"center",t.innerHTML=`
            <p class="hs-config-note">Uno o varios botones en la misma fila (ej. "Agendar revisión" + un teléfono de contacto).</p>
            ${u("Alineación de la fila",`
                <select class="users-manager-select" id="leBtnAlign">
                    <option value="left" ${e.align==="left"?"selected":""}>Izquierda</option>
                    <option value="center" ${e.align==="center"?"selected":""}>Centro</option>
                    <option value="right" ${e.align==="right"?"selected":""}>Derecha</option>
                </select>
            `)}
            <div id="leBtnRows" class="hs-repeat-rows"></div>
            <button type="button" class="button-secondary size-adjustment" id="leBtnAdd" style="margin-top:10px;">+ Agregar botón</button>
        `,t.querySelector("#leBtnAlign").addEventListener("change",r=>{e.align=r.target.value,c(),d()});const a=t.querySelector("#leBtnRows");e.buttons.forEach((r,n)=>{const l=document.createElement("div");l.className="hs-repeat-row";const i=document.createElement("div");i.className="hs-repeat-row-head",i.innerHTML=`<span class="hs-repeat-row-num">${n+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button>`,l.appendChild(i);const v=document.createElement("div");l.appendChild(v),ee(v,r,{alignField:!1}),i.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.buttons.splice(n,1),c(),te(t,e),d()}),a.appendChild(l)}),t.querySelector("#leBtnAdd").addEventListener("click",()=>{e.buttons.push({text:"",url:"",style:"outline",color:"#ff6213"}),c(),te(t,e),d()})}function T(t,e){e.headers=Array.isArray(e.headers)?e.headers:[],e.rows=Array.isArray(e.rows)?e.rows:[],e.buttons=Array.isArray(e.buttons)?e.buttons:[],e.align=e.align||"left";const a=Math.max(e.headers.length,1);t.innerHTML=`
            ${u("Descripción (opcional, arriba de la tabla)",`<textarea class="users-manager-input client-modal-textarea" id="leTbDesc" rows="2">${s(e.description)}</textarea>`)}
            <p class="hs-config-subtitle">Columnas</p>
            <div id="leTbHeaders" class="hs-repeat-rows"></div>
            <button type="button" class="button-secondary size-adjustment" id="leTbAddCol" style="margin-top:6px;">+ Agregar columna</button>

            <p class="hs-config-subtitle" style="margin-top:16px;">Filas</p>
            <div id="leTbRows" class="hs-repeat-rows"></div>
            <button type="button" class="button-secondary size-adjustment" id="leTbAddRow" style="margin-top:6px;" ${e.headers.length?"":'disabled title="Agrega al menos una columna primero"'}>+ Agregar fila</button>

            <p class="hs-config-subtitle" style="margin-top:16px;">Botones debajo de la tabla (opcional)</p>
            <div id="leTbButtons" class="hs-repeat-rows"></div>
            <button type="button" class="button-secondary size-adjustment" id="leTbAddBtn" style="margin-top:6px;">+ Agregar botón</button>
        `,t.querySelector("#leTbDesc").addEventListener("input",i=>{e.description=i.target.value,c(),d()});const r=t.querySelector("#leTbHeaders");e.headers.forEach((i,v)=>{const o=document.createElement("div");o.className="hs-repeat-row",o.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${v+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar columna">&times;</button></div>
                <input type="text" class="users-manager-input le-header" placeholder="Nombre de columna" value="${s(i)}">
            `,o.querySelector(".le-header").addEventListener("input",h=>{e.headers[v]=h.target.value,c(),d()}),o.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.headers.splice(v,1),e.rows.forEach(h=>h.splice(v,1)),c(),T(t,e),d()}),r.appendChild(o)}),t.querySelector("#leTbAddCol").addEventListener("click",()=>{e.headers.push(""),e.rows.forEach(i=>i.push("")),c(),T(t,e),d()});const n=t.querySelector("#leTbRows");e.rows.forEach((i,v)=>{for(;i.length<a;)i.push("");const o=document.createElement("div");o.className="hs-repeat-row";const h=i.map((y,m)=>`
                <input type="text" class="users-manager-input le-cell" data-ci="${m}" placeholder="${s(e.headers[m]||"Celda "+(m+1))}" value="${s(y)}" style="${m>0?"margin-top:6px;":""}">
            `).join("");o.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${v+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar fila">&times;</button></div>
                ${h}
            `,o.querySelectorAll(".le-cell").forEach(y=>{y.addEventListener("input",m=>{i[parseInt(m.target.dataset.ci,10)]=m.target.value,c(),d()})}),o.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.rows.splice(v,1),c(),T(t,e),d()}),n.appendChild(o)}),t.querySelector("#leTbAddRow").addEventListener("click",()=>{e.rows.push(new Array(a).fill("")),c(),T(t,e),d()});const l=t.querySelector("#leTbButtons");e.buttons.forEach((i,v)=>{const o=document.createElement("div");o.className="hs-repeat-row";const h=document.createElement("div");h.className="hs-repeat-row-head",h.innerHTML=`<span class="hs-repeat-row-num">${v+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button>`,o.appendChild(h);const y=document.createElement("div");o.appendChild(y),ee(y,i,{alignField:!1}),h.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.buttons.splice(v,1),c(),T(t,e),d()}),l.appendChild(o)}),t.querySelector("#leTbAddBtn").addEventListener("click",()=>{e.buttons.push({text:"",url:"",style:"outline",color:"#ff6213"}),c(),T(t,e),d()})}function c(){pe||(pe=!0,j.textContent="● Cambios sin guardar",j.className="live-editor-status is-dirty")}function be(t){return k.find(e=>e._uid===t)||null}let we=null;function $(){if(P.innerHTML="",!k.length){const t=document.createElement("div");t.className="live-editor-blocks-empty",t.textContent="Este servicio todavía no tiene bloques. Agrega uno abajo.",P.appendChild(t);return}k.forEach(t=>{const e=document.createElement("div");e.className="live-editor-block-row",L==="block"&&t._uid===C&&e.classList.add("is-selected"),t.is_active||e.classList.add("is-inactive"),e.dataset.uid=t._uid;const a=G[t.type]||G.default;e.innerHTML=`
                <span class="live-editor-block-drag-handle" title="Arrastrar para reordenar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                </span>
                <span class="live-editor-block-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${a}</svg>
                </span>
                <span class="live-editor-block-info">
                    <span class="live-editor-block-name">${s(t.title||I[t.type]||t.type)}</span>
                    <span class="live-editor-block-type">${s(I[t.type]||t.type)}</span>
                </span>
                <span class="live-editor-block-actions">
                    <button type="button" class="live-editor-toggle-active ${t.is_active?"is-on":""}" title="Activa/Inactiva"></button>
                    <button type="button" class="live-editor-block-delete" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    </button>
                </span>
            `,e.addEventListener("click",r=>{r.target.closest(".live-editor-toggle-active")||r.target.closest(".live-editor-block-delete")||ae(t._uid)}),e.querySelector(".live-editor-toggle-active").addEventListener("click",r=>{r.stopPropagation(),t.is_active=!t.is_active,c(),$(),d()}),e.querySelector(".live-editor-block-delete").addEventListener("click",r=>{r.stopPropagation();const n=t.title||I[t.type]||t.type;Q(n,()=>{const l=k.findIndex(i=>i._uid===t._uid);l!==-1&&k.splice(l,1),C===t._uid&&(C=null,R()),c(),$(),d()})}),P.appendChild(e)}),we||(we=new ce(P,{animation:150,handle:".live-editor-block-drag-handle",forceFallback:!0,ghostClass:"live-editor-block-row--ghost",dragClass:"live-editor-block-row--dragging",onEnd(t){if(t.oldIndex===t.newIndex)return;const[e]=k.splice(t.oldIndex,1);k.splice(t.newIndex,0,e),c(),$(),d()}}))}function D(){V.classList.remove("is-selected"),K.classList.remove("is-selected"),J.classList.remove("is-selected")}function ae(t){C=t,L="block",D(),$(),R()}function _e(){C=null,L="general",D(),V.classList.add("is-selected"),$(),R()}function Fe(){C=null,L="gallery",D(),K.classList.add("is-selected"),$(),R()}function De(){C=null,L="reviews",D(),J.classList.add("is-selected"),$(),R()}V.addEventListener("click",_e),K.addEventListener("click",Fe),J.addEventListener("click",De),Ae.addEventListener("click",()=>{const t=Re.value,e={_uid:ue(),id:null,type:t,title:"",config:Te(t),is_active:!0};k.push(e),c(),ae(e._uid),d()});function u(t,e,a){return`<div class="live-editor-field ${a||""}">
            <label>${s(t)}</label>
            ${e}
        </div>`}function N(t,e){const a=(t||[]).map(String);return(e||[]).map(r=>`<option value="${r.id}" ${a.includes(String(r.id))?"selected":""}>${s(r.alt_text||"Imagen #"+r.id)}</option>`).join("")}function xe(){w.innerHTML=`
            <div class="live-editor-panel-header">
                <span class="live-editor-panel-header-info">
                    <span class="live-editor-block-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                    </span>
                    <span>
                        <strong>Información general</strong>
                        <small>Nombre, slug, precio y SEO</small>
                    </span>
                </span>
            </div>
            ${u("Nombre",`<input type="text" class="users-manager-input" id="leGenName" value="${s(p.name)}">`)}
            ${u("Slug (URL)",`
                <div style="display:flex;gap:8px;">
                    <input type="text" class="users-manager-input" id="leGenSlug" value="${s(p.slug)}" style="flex:1;">
                    <button type="button" id="leGenSlugGenerate" class="live-editor-btn live-editor-btn--outline" title="Generar a partir del nombre">Generar</button>
                </div>
            `,"")}
            <div class="live-editor-field-row">
                ${u("Tipo de página",`
                    <select class="users-manager-select" id="leGenPageType">
                        <option value="service" ${p.page_type==="service"?"selected":""}>Servicio (nivel 3)</option>
                        <option value="category" ${p.page_type==="category"?"selected":""}>Categoría (nivel 2)</option>
                        <option value="hub" ${p.page_type==="hub"?"selected":""}>Hub — /servicios (nivel 1)</option>
                    </select>
                `)}
                ${u("Página padre",`
                    <select class="users-manager-select" id="leGenParentId">
                        <option value="">Sin padre (nivel raíz)</option>
                        ${(b.eligibleParents||[]).map(i=>`<option value="${i.id}" ${String(p.parent_id)===String(i.id)?"selected":""}>${s(i.name)} (${i.page_type==="hub"?"Hub":"Categoría"})</option>`).join("")}
                    </select>
                `,"leGenParentField")}
            </div>
            <p class="hs-config-note">/servicios → hub · /servicios/{categoría} → nivel 2 · /servicios/{categoría}/{servicio} → nivel 3. Un servicio sin padre se sirve en /servicio/{slug} (legacy).</p>
            ${u("Descripción corta",`<textarea class="users-manager-input client-modal-textarea" id="leGenShortDesc" rows="2">${s(p.short_description)}</textarea>`)}
            <div class="live-editor-field-row">
                ${u("Precio (opcional)",`<input type="number" step="0.01" min="0" class="users-manager-input" id="leGenPrice" value="${s(p.price)}">`)}
                ${u("Moneda",`<input type="text" class="users-manager-input" id="leGenCurrency" value="${s(p.currency)}" maxlength="10">`)}
            </div>
            ${u("Visibilidad del precio",`
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenShowPrice" ${p.show_price?"checked":""}> Mostrar el precio en el sitio público
                </label>
                <p class="hs-config-note" style="margin-top:4px;">Si lo desmarcas, el servicio se sigue publicando pero sin precio visible — útil para cotizar en privado.</p>
            `)}
            ${u("Estado",`
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenIsActive" ${p.is_active?"checked":""}> Publicado (visible en el sitio público)
                </label>
            `)}
            ${u("Color de fondo de la página (opcional)",`
                <div id="leGenBgColorPicker"></div>
                <button type="button" id="leGenBgColorClear" class="live-editor-btn live-editor-btn--outline" style="margin-top:8px;" ${p.background_color?"":"disabled"}>Quitar color (usar blanco)</button>
            `)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            ${u("Título SEO",`<input type="text" class="users-manager-input" id="leGenSeoTitle" value="${s(p.seo_title)}" maxlength="160">`)}
            ${u("Descripción SEO",`<textarea class="users-manager-input client-modal-textarea" id="leGenSeoDesc" rows="2" maxlength="500">${s(p.seo_description)}</textarea>`)}
            ${u("",`
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenIsCanonical" ${p.canonical_url?"":"checked"}> Es la URL Canónica de este servicio
                </label>
                <p class="hs-config-note" style="margin-top:4px;">Marcado (normal): Google usa la URL de este mismo servicio. Desmárcalo solo si este servicio es muy parecido a otro y quieres que Google indexe ese otro en su lugar.</p>
            `)}
            <div id="leGenCanonicalUrlWrap" style="${p.canonical_url?"":"display:none;"}">
                ${u("URL Canónica",`<input type="url" class="users-manager-input" id="leGenCanonicalUrl" value="${s(p.canonical_url)}" maxlength="255" placeholder="https://equitermindustries.com.mx/servicio/otro-servicio-similar">`)}
            </div>
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div class="live-editor-field">
                <label>Rating y reseñas — Promedio mostrado</label>
                <p class="hs-config-note" style="margin:0 0 8px;">Contenido curado por el equipo, igual que la FAQ. Estas cifras son de marketing (no tienen por qué coincidir con el número de reseñas capturadas en el panel "Reseñas") y nunca alimentan el marcado SEO — el marcado usa siempre el conteo real.</p>
            </div>
            <div class="live-editor-field-row">
                ${u("Promedio mostrado (0–5)",`<input type="number" step="0.1" min="0" max="5" class="users-manager-input" id="leGenRatingAvg" value="${s(p.rating_average_displayed)}">`)}
                ${u("Total de servicios calificados",`<input type="number" min="0" class="users-manager-input" id="leGenRatingTotal" value="${s(p.rating_total_rated)}">`)}
            </div>
            <div class="live-editor-field-row">
                ${u("% que recomendaría el servicio",`<input type="number" step="0.1" min="0" max="100" class="users-manager-input" id="leGenRatingRecommend" value="${s(p.rating_recommend_percent)}">`)}
                ${u("Puntualidad de cuadrilla (0–5)",`<input type="number" step="0.1" min="0" max="5" class="users-manager-input" id="leGenRatingPunctuality" value="${s(p.rating_punctuality_average)}">`)}
            </div>
            <div class="live-editor-field-row">
                ${u("Clientes recurrentes",`<input type="number" min="0" class="users-manager-input" id="leGenRatingRecurring" value="${s(p.rating_recurring_clients)}">`)}
                ${u("Calificando desde (año, opcional)",`<input type="number" min="2000" max="2100" class="users-manager-input" id="leGenRatingSince" value="${s(p.rating_since_year)}">`)}
            </div>
            ${u("Distribución por estrella",`
                <div class="live-editor-field-row" style="grid-template-columns:repeat(5,1fr);">
                    ${[5,4,3,2,1].map(i=>`
                        <div>
                            <label style="font-size:11px;color:#6b7280;display:block;margin-bottom:2px;">${i} ★</label>
                            <input type="number" min="0" class="users-manager-input le-gen-rating-dist" data-star="${i}" value="${s((p.rating_distribution||{})[i]??(p.rating_distribution||{})[String(i)]??"")}">
                        </div>
                    `).join("")}
                </div>
            `)}
            <div id="leGenErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
            <button type="button" id="leGenSaveBtn" class="live-editor-btn live-editor-btn--solid live-editor-btn--block">Guardar información general</button>
        `,w.querySelector("#leGenSaveBtn").addEventListener("click",()=>fe()),w.querySelector("#leGenSlugGenerate").addEventListener("click",()=>{const i=w.querySelector("#leGenName");w.querySelector("#leGenSlug").value=Pe(i.value),c()});const t=w.querySelector("#leGenIsCanonical"),e=w.querySelector("#leGenCanonicalUrlWrap");t.addEventListener("change",()=>{e.style.display=t.checked?"none":""});const a=w.querySelector("#leGenPageType"),r=w.querySelector(".leGenParentField"),n=()=>{const i=a.value==="hub"||a.value==="category";r&&(r.style.display=i?"none":"")};a.addEventListener("change",n),n();const l=w.querySelector("#leGenBgColorClear");Z(w.querySelector("#leGenBgColorPicker"),p.background_color||"",i=>{p.background_color=i,l.disabled=!1,c()}),l.addEventListener("click",()=>{p.background_color=null,l.disabled=!0,xe(),c()})}function re(t){p.faqs=p.faqs||[];const e=t;e&&(e.innerHTML="",p.faqs.forEach((a,r)=>{const n=document.createElement("div");n.className="hs-faq-row",n.innerHTML=`
                <div class="hs-faq-row-head">
                    <span class="hs-faq-row-num">${r+1}</span>
                    <div class="hs-faq-row-actions">
                        <button type="button" class="hs-faq-btn hs-faq-remove" title="Eliminar">&times;</button>
                    </div>
                </div>
                <input type="text" class="users-manager-input hs-faq-question" placeholder="Pregunta" value="${s(a.question)}">
                <textarea class="users-manager-input client-modal-textarea hs-faq-answer" rows="2" placeholder="Respuesta">${s(a.answer)}</textarea>
            `,n.querySelector(".hs-faq-question").addEventListener("input",l=>{a.question=l.target.value,c()}),n.querySelector(".hs-faq-answer").addEventListener("input",l=>{a.answer=l.target.value,c()}),n.querySelector(".hs-faq-remove").addEventListener("click",()=>{p.faqs.splice(r,1),c(),re(t)}),e.appendChild(n)}))}async function fe(t){t=t||{};const e=w.querySelector("#"+(t.btnId||"leGenSaveBtn")),a=w.querySelector("#"+(t.errorsBoxId||"leGenErrors"));a.style.display="none",a.innerHTML="",document.querySelectorAll("#leEditPanel .is-invalid").forEach(y=>y.classList.remove("is-invalid"));const r=y=>w.querySelector("#"+y),n=(y,m)=>{const g=r(y);return g?g.value:m},l=(y,m)=>{const g=r(y);return g?g.checked:m},i=n("leGenPageType",p.page_type),v=w.querySelectorAll(".le-gen-rating-dist"),o={name:n("leGenName",p.name),slug:n("leGenSlug",p.slug),page_type:i,parent_id:i==="hub"?"":n("leGenParentId",p.parent_id||""),short_description:n("leGenShortDesc",p.short_description),price:n("leGenPrice",p.price),currency:n("leGenCurrency",p.currency),show_price:l("leGenShowPrice",p.show_price),background_color:p.background_color||"",is_active:l("leGenIsActive",p.is_active),seo_title:n("leGenSeoTitle",p.seo_title),seo_description:n("leGenSeoDesc",p.seo_description),is_canonical:l("leGenIsCanonical",!p.canonical_url),canonical_url:n("leGenCanonicalUrl",p.canonical_url),faq_items:p.faqs||[],rating_average_displayed:n("leGenRatingAvg",p.rating_average_displayed),rating_total_rated:n("leGenRatingTotal",p.rating_total_rated),rating_recommend_percent:n("leGenRatingRecommend",p.rating_recommend_percent),rating_punctuality_average:n("leGenRatingPunctuality",p.rating_punctuality_average),rating_recurring_clients:n("leGenRatingRecurring",p.rating_recurring_clients),rating_since_year:n("leGenRatingSince",p.rating_since_year),rating_distribution:v.length?Array.from(v).reduce((y,m)=>(y[m.dataset.star]=m.value,y),{}):p.rating_distribution||{}};e.disabled=!0;const h=e.textContent;e.textContent="Guardando...";try{const y=await fetch(b.generalUrl,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify(o)}),m=await y.json();if(y.ok){Object.assign(p,m.servicePage),ge&&(ge.textContent=p.name),document.title="Editor en vivo - "+p.name+" - Admin";const g=document.getElementById("leBrowserUrl");g&&(g.textContent="equitermindustries.com.mx"+p.public_path);const A=document.getElementById("leViewLiveLink");if(A){const _=window.location.origin;A.href=_+p.public_path}window.showCenterToast&&showCenterToast("Información general guardada."),d()}else if(y.status===422){const g=m.errors||{};a.innerHTML=Object.values(g).flat().map(_=>`<p>${_}</p>`).join(""),a.style.display="block";const A={name:"leGenName",slug:"leGenSlug",page_type:"leGenPageType",parent_id:"leGenParentId",short_description:"leGenShortDesc",price:"leGenPrice",currency:"leGenCurrency",seo_title:"leGenSeoTitle",seo_description:"leGenSeoDesc",canonical_url:"leGenCanonicalUrl",rating_average_displayed:"leGenRatingAvg",rating_total_rated:"leGenRatingTotal",rating_recommend_percent:"leGenRatingRecommend",rating_punctuality_average:"leGenRatingPunctuality",rating_recurring_clients:"leGenRatingRecurring",rating_since_year:"leGenRatingSince"};Object.keys(g).forEach(_=>{const x=w.querySelector("#"+(A[_]||""));x&&x.classList.add("is-invalid")})}else throw new Error("save-general-failed")}catch{a.innerHTML="<p>No se pudo guardar. Intenta de nuevo.</p>",a.style.display="block"}finally{e.disabled=!1,e.textContent=h}}function Ne(){w.innerHTML=`
            <div class="live-editor-panel-header">
                <span class="live-editor-panel-header-info">
                    <span class="live-editor-block-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                    </span>
                    <span>
                        <strong>Galería del servicio</strong>
                        <small>La primera imagen se usa como portada. Arrastra para reordenar.</small>
                    </span>
                </span>
            </div>
            <div id="leGalleryGrid" class="service-gallery-grid"></div>
        `,O()}function O(){const t=w.querySelector("#leGalleryGrid");if(!t)return;t.innerHTML="",(b.images||[]).forEach((a,r)=>{const n=document.createElement("div");n.className="service-gallery-item",n.dataset.id=a.id,n.innerHTML=`
                <div class="service-gallery-item__drag" title="Arrastrar para reordenar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                </div>
                ${r===0?'<span class="service-gallery-item__badge">PORTADA</span>':""}
                <button type="button" class="service-gallery-item__remove" title="Quitar">&times;</button>
                <img src="${a.url}" alt="${s(a.alt_text||"")}">
                <input type="text" class="users-manager-input service-gallery-item__alt" placeholder="Texto alternativo" value="${s(a.alt_text||"")}">
            `,n.querySelector(".service-gallery-item__remove").addEventListener("click",()=>{Q(a.alt_text||"Imagen #"+a.id,()=>ze(a.id))});let l=null;n.querySelector(".service-gallery-item__alt").addEventListener("input",i=>{a.alt_text=i.target.value,clearTimeout(l),l=setTimeout(()=>Ue(a.id,a.alt_text),500)}),t.appendChild(n)});const e=document.createElement("div");e.className="service-gallery-item service-gallery-item--add",e.id="leGalleryAddTile",e.innerHTML=`
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            <span>Arrastra o selecciona</span>
        `,e.addEventListener("click",()=>{typeof window.openImagePicker=="function"&&window.openImagePicker(null,{onSelect:Oe})}),t.appendChild(e),new ce(t,{animation:150,handle:".service-gallery-item__drag",filter:".service-gallery-item--add",forceFallback:!0,ghostClass:"service-gallery-item--ghost",dragClass:"service-gallery-item--dragging",onEnd(a){if(a.oldIndex===a.newIndex)return;const[r]=b.images.splice(a.oldIndex,1);b.images.splice(a.newIndex,0,r),O(),We()}})}async function Oe(t){try{const e=await fetch(b.imagesStoreUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({image_url:t,alt_text:""})}),a=await e.json();e.ok?(b.images.push({id:a.image.id,url:a.image.url,alt_text:a.image.alt_text}),O(),window.showCenterToast&&showCenterToast("Imagen agregada."),d()):window.showCenterToast&&showCenterToast("No se pudo agregar la imagen.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al agregar la imagen.","error")}}async function ze(t){try{if((await fetch(b.imageDestroyUrlTemplate.replace("__IMAGE_ID__",t),{method:"DELETE",headers:{"X-CSRF-TOKEN":E,Accept:"application/json"}})).ok){const a=b.images.findIndex(r=>String(r.id)===String(t));a!==-1&&b.images.splice(a,1),O(),window.showCenterToast&&showCenterToast("Imagen eliminada."),d()}else window.showCenterToast&&showCenterToast("No se pudo eliminar la imagen.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al eliminar la imagen.","error")}}async function Ue(t,e){try{await fetch(b.imageUpdateUrlTemplate.replace("__IMAGE_ID__",t),{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({alt_text:e})})}catch{}}async function We(){const t=b.images.map(e=>e.id);try{(await fetch(b.imagesReorderUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({order:t})})).ok?(window.showCenterToast&&showCenterToast("Orden de galería actualizado."),d()):window.showCenterToast&&showCenterToast("No se pudo guardar el nuevo orden de la galería.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al reordenar la galería.","error")}}function ie(){const t=S.filter(e=>e.is_visible).length;w.innerHTML=`
            <div class="live-editor-panel-header">
                <span class="live-editor-panel-header-info">
                    <span class="live-editor-block-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </span>
                    <span>
                        <strong>Reseñas capturadas</strong>
                        <small>${t} visibles de ${S.length}</small>
                    </span>
                </span>
            </div>
            <div id="leReviewsList" class="hs-repeat-rows"></div>
            <button type="button" id="leReviewAddBtn" class="live-editor-btn live-editor-btn--outline live-editor-btn--block" style="margin-top:10px;">+ Agregar reseña</button>
            <div id="leReviewFormWrap"></div>
        `,Se(),w.querySelector("#leReviewAddBtn").addEventListener("click",()=>$e(null))}function Se(){const t=w.querySelector("#leReviewsList");if(t){if(t.innerHTML="",!S.length){t.innerHTML='<p class="hs-config-note">Todavía no hay reseñas capturadas para este servicio.</p>';return}S.forEach(e=>{const a=document.createElement("div");a.className="service-review-row",a.dataset.id=e.id;const r=e.comment||"";a.innerHTML=`
                <div class="service-review-row__drag" title="Arrastrar para reordenar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                </div>
                <div class="service-review-row__body">
                    <div class="service-review-row__head">
                        <strong>${s(e.customer_name)}</strong>
                        <span class="service-review-row__stars">${"★".repeat(e.rating||0)}${"☆".repeat(5-(e.rating||0))}</span>
                        ${e.is_verified?'<span class="users-manager-badge status" style="font-size:10px;">Verificado</span>':""}
                        <span class="users-manager-badge ${e.is_visible?"status":"status-inactive"}" style="font-size:10px;">${e.is_visible?"Visible":"Oculta"}</span>
                    </div>
                    <p class="service-review-row__comment">${s(r.length>140?r.slice(0,140)+"…":r)}</p>
                </div>
                <div class="header-right-user-manager">
                    <button type="button" class="table-users-manager-action-btn edit" title="Editar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                    </button>
                    <button type="button" class="table-users-manager-action-btn delete" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                    </button>
                </div>
            `,a.querySelector(".edit").addEventListener("click",()=>$e(e)),a.querySelector(".delete").addEventListener("click",()=>{Q(e.customer_name||"Reseña",()=>Ke(e.id))}),t.appendChild(a)}),new ce(t,{animation:150,handle:".service-review-row__drag",forceFallback:!0,ghostClass:"service-review-row--ghost",dragClass:"service-review-row--dragging",onEnd(e){if(e.oldIndex===e.newIndex)return;const[a]=S.splice(e.oldIndex,1);S.splice(e.newIndex,0,a),Se(),Je()}})}}function $e(t){const e=!!t,a=w.querySelector("#leReviewFormWrap");if(!a)return;const r=e?Object.assign({},t):{customer_name:"",customer_role:"",customer_company:"",customer_city:"",customer_state:"",review_date:"",rating:5,comment:"",categories:[],is_verified:!1,is_visible:!0,business_response:"",business_response_date:""};a.innerHTML=`
            <div class="show-user-divider" style="margin:14px 0 10px;"></div>
            <p class="live-editor-col-title">${e?"Editar reseña":"Nueva reseña"}</p>
            ${u("Cliente",`<input type="text" class="users-manager-input" id="leRevName" value="${s(r.customer_name)}">`)}
            <div class="live-editor-field-row">
                ${u("Puesto (opcional)",`<input type="text" class="users-manager-input" id="leRevRole" value="${s(r.customer_role)}" placeholder="Jefe de mantenimiento">`)}
                ${u("Empresa (opcional)",`<input type="text" class="users-manager-input" id="leRevCompany" value="${s(r.customer_company)}">`)}
            </div>
            <div class="live-editor-field-row">
                ${u("Ciudad (opcional)",`<input type="text" class="users-manager-input" id="leRevCity" value="${s(r.customer_city)}">`)}
                ${u("Estado (opcional)",`<input type="text" class="users-manager-input" id="leRevState" value="${s(r.customer_state)}" placeholder="JAL">`)}
            </div>
            ${u("Fecha",`<input type="date" class="users-manager-input" id="leRevDate" value="${s(r.review_date)}">`)}
            ${u("Calificación",`<div class="service-review-stars-input" id="leRevStars"></div><input type="hidden" id="leRevRating" value="${r.rating||5}">`)}
            <div class="live-editor-field">
                <label>Comentario &middot; <span id="leRevCommentCount">${(r.comment||"").length}</span>/240</label>
                <textarea class="users-manager-input client-modal-textarea" id="leRevComment" rows="3" maxlength="240">${s(r.comment)}</textarea>
            </div>
            ${u("Categorías",`
                <div class="hs-product-chips" style="flex-wrap:wrap;">
                    ${Object.entries(b.reviewCategories||{}).map(([i,v])=>`
                        <label style="display:inline-flex;align-items:center;gap:4px;border:1px solid #d1d5db;border-radius:999px;padding:4px 10px;font-size:12.5px;cursor:pointer;font-weight:400;">
                            <input type="checkbox" class="le-rev-category" value="${s(i)}" ${(r.categories||[]).includes(i)?"checked":""}> ${s(v)}
                        </label>
                    `).join("")}
                </div>
            `)}
            <div class="live-editor-field-row">
                ${u("",`<label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;"><input type="checkbox" id="leRevVerified" ${r.is_verified?"checked":""}> Cliente verificado</label>`)}
                ${u("",`<label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;"><input type="checkbox" id="leRevVisible" ${r.is_visible?"checked":""}> Visible en público</label>`)}
            </div>
            ${u("Respuesta de Equiterm (opcional)",`<textarea class="users-manager-input client-modal-textarea" id="leRevResponse" rows="2">${s(r.business_response)}</textarea>`)}
            ${u("Fecha de respuesta (opcional)",`<input type="date" class="users-manager-input" id="leRevResponseDate" value="${s(r.business_response_date)}">`)}
            <div id="leRevErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
            <div style="display:flex;gap:8px;">
                <button type="button" id="leRevCancel" class="live-editor-btn live-editor-btn--outline" style="flex:1;">Cancelar</button>
                <button type="button" id="leRevSave" class="live-editor-btn live-editor-btn--solid" style="flex:1;">${e?"Guardar cambios":"Crear reseña"}</button>
            </div>
        `;const n=a.querySelector("#leRevStars");function l(i){a.querySelector("#leRevRating").value=i,n.innerHTML="";for(let v=1;v<=5;v++){const o=document.createElement("button");o.type="button",o.className="service-review-star"+(v<=i?" is-active":""),o.textContent="★",o.addEventListener("click",()=>l(v)),n.appendChild(o)}}l(r.rating||5),a.querySelector("#leRevComment").addEventListener("input",function(){a.querySelector("#leRevCommentCount").textContent=this.value.length}),a.querySelector("#leRevCancel").addEventListener("click",()=>{a.innerHTML=""}),a.querySelector("#leRevSave").addEventListener("click",()=>Ve(e?t.id:null,a)),a.scrollIntoView({behavior:"smooth",block:"nearest"})}async function Ve(t,e){const a=e.querySelector("#leRevErrors");a.style.display="none",a.innerHTML="";const r={customer_name:e.querySelector("#leRevName").value,customer_role:e.querySelector("#leRevRole").value||null,customer_company:e.querySelector("#leRevCompany").value||null,customer_city:e.querySelector("#leRevCity").value||null,customer_state:e.querySelector("#leRevState").value||null,review_date:e.querySelector("#leRevDate").value||null,rating:parseInt(e.querySelector("#leRevRating").value,10)||5,comment:e.querySelector("#leRevComment").value,categories:Array.from(e.querySelectorAll(".le-rev-category:checked")).map(i=>i.value),is_verified:e.querySelector("#leRevVerified").checked,is_visible:e.querySelector("#leRevVisible").checked,business_response:e.querySelector("#leRevResponse").value||null,business_response_date:e.querySelector("#leRevResponseDate").value||null},n=e.querySelector("#leRevSave");n.disabled=!0;const l=n.textContent;n.textContent="Guardando...";try{const i=t?b.reviewUpdateUrlTemplate.replace("__REVIEW_ID__",t):b.reviewsStoreUrl,v=await fetch(i,{method:t?"PUT":"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify(r)}),o=await v.json();if(v.ok){if(t){const h=S.findIndex(y=>String(y.id)===String(t));h!==-1&&(S[h]=Object.assign({},S[h],o.review))}else S.push(o.review);ie(),window.showCenterToast&&showCenterToast(t?"Reseña actualizada.":"Reseña creada."),d()}else if(v.status===422){const h=o.errors||{};a.innerHTML=Object.values(h).flat().map(y=>`<p>${y}</p>`).join(""),a.style.display="block",n.disabled=!1,n.textContent=l}else throw new Error("save-review-failed")}catch{a.innerHTML="<p>No se pudo guardar. Intenta de nuevo.</p>",a.style.display="block",n.disabled=!1,n.textContent=l}}async function Ke(t){try{if((await fetch(b.reviewDestroyUrlTemplate.replace("__REVIEW_ID__",t),{method:"DELETE",headers:{"X-CSRF-TOKEN":E,Accept:"application/json"}})).ok){const a=S.findIndex(r=>String(r.id)===String(t));a!==-1&&S.splice(a,1),ie(),window.showCenterToast&&showCenterToast("Reseña eliminada."),d()}else window.showCenterToast&&showCenterToast("No se pudo eliminar la reseña.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al eliminar la reseña.","error")}}async function Je(){const t=S.map(e=>e.id);try{(await fetch(b.reviewsReorderUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({order:t})})).ok?window.showCenterToast&&showCenterToast("Orden de reseñas actualizado."):window.showCenterToast&&showCenterToast("No se pudo guardar el nuevo orden.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al guardar el orden.","error")}}function R(){if(L==="general"){xe();return}if(L==="gallery"){Ne();return}if(L==="reviews"){ie();return}const t=be(C);if(!t){w.innerHTML='<div class="live-editor-panel-empty">Selecciona un bloque de la izquierda para editarlo aquí, o "Información general" para nombre, slug, precio y SEO.</div>';return}const e=G[t.type]||G.default;if(w.innerHTML=`
            <div class="live-editor-panel-header">
                <span class="live-editor-panel-header-info">
                    <span class="live-editor-block-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${e}</svg>
                    </span>
                    <span>
                        <strong>${s(t.title||I[t.type]||t.type)}</strong>
                        <small>Editando bloque</small>
                    </span>
                </span>
                <button type="button" class="live-editor-panel-close" id="lePanelClose" title="Cerrar">&times;</button>
            </div>
            ${u("Título (opcional, puedes usar {servicio})",`<input type="text" class="users-manager-input" id="leTitle" value="${s(t.title)}" placeholder="Ej: Beneficios del servicio">`)}
            ${ke.includes(t.type)?'<div id="leTitleStyle"></div>':""}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div id="leTypeFields"></div>
        `,w.querySelector("#lePanelClose").addEventListener("click",()=>{C=null,$(),R()}),w.querySelector("#leTitle").addEventListener("input",a=>{t.title=a.target.value,c(),$(),d()}),ke.includes(t.type)){const a=t.config=t.config||{};a.title_style=a.title_style||{},B(w.querySelector("#leTitleStyle"),a.title_style,{tagChoices:["h2","h3"]})}Xe(w.querySelector("#leTypeFields"),t)}const ke=["benefits_grid","process_steps","content_tabs","gallery_carousel","faq"];function Xe(t,e){const a=e.config=e.config||{};switch(e.type){case"banner":z(t,a,"");break;case"dual_banner":t.innerHTML='<p class="hs-config-subtitle">Banner Izquierdo</p><div id="leDbLeft"></div><p class="hs-config-subtitle">Banner Derecho</p><div id="leDbRight"></div>',a.left=a.left||{image_url:"",link_url:"",alt:""},a.right=a.right||{image_url:"",link_url:"",alt:""},z(t.querySelector("#leDbLeft"),a.left,"Left"),z(t.querySelector("#leDbRight"),a.right,"Right");break;case"product_carousel":Ee(t,a,"");break;case"product_carousel_banner":t.innerHTML='<p class="hs-config-subtitle">Banner</p><div id="lePcbBanner"></div><p class="hs-config-subtitle">Productos del carrusel</p><div id="lePcbCarousel"></div>',z(t.querySelector("#lePcbBanner"),{image_url:a.banner_image_url,link_url:a.banner_link_url,alt:a.banner_alt},"PcbBanner",(r,n)=>{r==="image_url"&&(a.banner_image_url=n),r==="link_url"&&(a.banner_link_url=n),r==="alt"&&(a.banner_alt=n)}),Ee(t.querySelector("#lePcbCarousel"),a,"Pcb");break;case"category_grid":Ye(t,a);break;case"brand_carousel":t.innerHTML='<p class="hs-config-note">Este bloque muestra automáticamente todas las marcas activas. No requiere configuración adicional.</p>';break;case"html_block":Ze(t,a);break;case"faq":et(t,a);break;case"rich_header":tt(t,a);break;case"content_tabs":ne(t,a);break;case"benefits_grid":le(t,a);break;case"process_steps":se(t,a);break;case"gallery_carousel":at(t,a);break;case"rating_reviews":rt(t,a);break;case"cta_final":it(t,a);break;case"button":te(t,a);break;case"table_block":T(t,a);break;default:t.innerHTML='<p class="hs-config-note">Tipo de bloque desconocido.</p>'}}function z(t,e,a,r){const n="leBanner"+a+"Image",l="leBanner"+a+"Link",i="leBanner"+a+"Alt";t.innerHTML=`
            ${u("URL de Imagen",`
                <div class="img-picker-field">
                    <input type="text" class="users-manager-input" id="${n}" value="${s(e.image_url)}" placeholder="https://...">
                    <button type="button" class="img-picker-trigger-btn" data-target="${n}">Seleccionar</button>
                </div>
            `)}
            <div class="live-editor-field-row">
                ${u("URL de Enlace",`<input type="text" class="users-manager-input" id="${l}" value="${s(e.link_url)}" placeholder="/servicio/otro-servicio">`)}
                ${u("Texto Alternativo",`<input type="text" class="users-manager-input" id="${i}" value="${s(e.alt)}">`)}
            </div>
        `;const v=g=>r?r("image_url",g):e.image_url=g,o=g=>r?r("link_url",g):e.link_url=g,h=g=>r?r("alt",g):e.alt=g,y=t.querySelector("#"+n);y.addEventListener("input",()=>{v(y.value),c(),d()}),t.querySelector("#"+l).addEventListener("input",g=>{o(g.target.value),c(),d()}),t.querySelector("#"+i).addEventListener("input",g=>{h(g.target.value),c(),d()}),t.querySelector(".img-picker-trigger-btn").addEventListener("click",()=>{typeof window.openImagePicker=="function"&&window.openImagePicker(n)})}function Ee(t,e,a){const r="leSource"+a,n="leLimit"+a,l="leCategory"+a,i="leBrand"+a,v="leCollection"+a,o="leManualWrap"+a,h=Object.keys(de).map(m=>`<option value="${m}" ${e.source===m?"selected":""}>${de[m]}</option>`).join("");t.innerHTML=`
            <div class="live-editor-field-row">
                ${u("Origen de Productos",`<select class="users-manager-select" id="${r}">${h}</select>`)}
                ${u("Límite de Productos",`<input type="number" class="users-manager-input" id="${n}" min="1" max="50" value="${e.limit??10}">`)}
            </div>
            <div id="leSourceFields${a}"></div>
        `,t.querySelector("#"+r).addEventListener("change",m=>{e.source=m.target.value,c(),y(),d()}),t.querySelector("#"+n).addEventListener("input",m=>{e.limit=parseInt(m.target.value,10)||10,c(),d()});function y(){const m=t.querySelector("#leSourceFields"+a);e.source==="category"?(m.innerHTML=u("Categoría",`<select class="users-manager-select" id="${l}">
                    <option value="">Selecciona una categoría</option>
                    ${(b.categories||[]).map(g=>`<option value="${g.id}" ${String(e.category_id)===String(g.id)?"selected":""}>${s(g.name)}</option>`).join("")}
                </select>`),m.querySelector("#"+l).addEventListener("change",g=>{e.category_id=g.target.value||null,c(),d()})):e.source==="brand"?(m.innerHTML=u("Marca",`<select class="users-manager-select" id="${i}">
                    <option value="">Selecciona una marca</option>
                    ${(b.brands||[]).map(g=>`<option value="${g.id}" ${String(e.brand_id)===String(g.id)?"selected":""}>${s(g.name)}</option>`).join("")}
                </select>`),m.querySelector("#"+i).addEventListener("change",g=>{e.brand_id=g.target.value||null,c(),d()})):e.source==="collection"?(m.innerHTML=u("Colección",`<select class="users-manager-select" id="${v}">
                    <option value="">Selecciona una colección</option>
                    ${(b.collections||[]).map(g=>`<option value="${g.id}" ${String(e.collection_id)===String(g.id)?"selected":""}>${s(g.name)}</option>`).join("")}
                </select>`),m.querySelector("#"+v).addEventListener("change",g=>{e.collection_id=g.target.value||null,c(),d()})):e.source==="manual"?(m.innerHTML=`<div class="live-editor-field"><label>Productos</label><div id="${o}"></div></div>`,Qe(m.querySelector("#"+o),e.product_ids||[],g=>{e.product_ids=g,c(),d()})):m.innerHTML=""}y()}function Qe(t,e,a){t.innerHTML=`
            <div class="hs-product-search">
                <div class="hs-product-search__input-wrap">
                    <input type="text" class="hs-product-search__input" placeholder="Buscar producto por nombre o SKU..." autocomplete="off">
                </div>
                <div class="hs-product-search__dropdown" style="display:none;">
                    <div class="hs-product-search__empty" style="display:none;">Sin resultados</div>
                    <ul class="hs-product-search__list"></ul>
                </div>
            </div>
            <div class="hs-product-chips"></div>
        `;const r=t.querySelector(".hs-product-search__input"),n=t.querySelector(".hs-product-search__dropdown"),l=t.querySelector(".hs-product-search__list"),i=t.querySelector(".hs-product-search__empty"),v=t.querySelector(".hs-product-chips");let o=[],h=null;function y(){v.innerHTML="",o.forEach(_=>{const x=document.createElement("span");x.className="hs-product-chip",x.innerHTML=`<span>${s(_.name)}</span><small>${s(_.sku)}</small><button type="button" aria-label="Quitar">&times;</button>`,x.querySelector("button").addEventListener("click",()=>{o=o.filter(f=>f.id!==_.id),y(),a(o.map(f=>f.id))}),v.appendChild(x)})}function m(){n.style.display="none",l.innerHTML=""}function g(_){l.innerHTML="";const x=_.filter(f=>!o.some(q=>q.id===f.id));if(!x.length){i.style.display="block",l.style.display="none";return}i.style.display="none",l.style.display="block",x.forEach(f=>{const q=document.createElement("li");q.className="hs-product-search__item",q.innerHTML=`<span>${s(f.name)}</span><small>SKU: ${s(f.sku)}</small>`,q.addEventListener("click",()=>{o.push({id:f.id,name:f.name,sku:f.sku}),y(),a(o.map(oe=>oe.id)),r.value="",m()}),l.appendChild(q)})}async function A(_){try{const x=new URL(b.productsSearchUrl,window.location.origin);Object.entries(_).forEach(([q,oe])=>x.searchParams.set(q,oe));const f=await fetch(x.toString(),{headers:{Accept:"application/json"}});return f.ok?await f.json():[]}catch{return[]}}r.addEventListener("input",function(){const _=this.value.trim();if(clearTimeout(h),_.length<2){m();return}h=setTimeout(async()=>{const x=await A({q:_});n.style.display="block",g(x)},300)}),document.addEventListener("click",_=>{t.contains(_.target)||m()}),e&&e.length&&A({ids:e.join(",")}).then(_=>{o=_.map(x=>({id:x.id,name:x.name,sku:x.sku})),y()})}function Ye(t,e){t.innerHTML=u("Categorías a mostrar (vacío = todas las principales activas)",`
            <select class="users-manager-select" id="leCategoryIds" multiple size="6">
                ${(b.categories||[]).map(a=>`<option value="${a.id}" ${(e.category_ids||[]).map(String).includes(String(a.id))?"selected":""}>${s(a.name)}</option>`).join("")}
            </select>
        `),t.querySelector("#leCategoryIds").addEventListener("change",a=>{e.category_ids=Array.from(a.target.selectedOptions).map(r=>parseInt(r.value,10)),c(),d()})}function Ze(t,e){t.innerHTML=u("Contenido HTML",`<textarea class="users-manager-input client-modal-textarea" id="leHtml" rows="8" placeholder="<div>...</div>">${s(e.html)}</textarea>`),t.querySelector("#leHtml").addEventListener("input",a=>{e.html=a.target.value,c(),d()})}function et(t,e){t.innerHTML=`
            ${u("Texto descriptivo (opcional)",`<textarea class="users-manager-input client-modal-textarea" id="leFaqDescription" rows="2">${s(e.description)}</textarea>`)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div class="live-editor-field">
                <label>Preguntas frecuentes</label>
                <p class="hs-config-note" style="margin:0 0 8px;">Alimentan el <code>FAQPage</code> de Google y el acordeón de este bloque. Se comparten con toda la página, aunque haya más de un bloque "Preguntas Frecuentes".</p>
                <div id="leFaqRows" class="hs-faq-items"></div>
                <button type="button" class="button-secondary size-adjustment" id="leFaqAdd" style="margin-top:10px;">+ Agregar pregunta</button>
            </div>
            <div id="leFaqErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
            <button type="button" id="leFaqSaveBtn" class="live-editor-btn live-editor-btn--solid live-editor-btn--block">Guardar preguntas frecuentes</button>
        `,t.querySelector("#leFaqDescription").addEventListener("input",a=>{e.description=a.target.value,c(),d()}),t.querySelector("#leFaqAdd").addEventListener("click",()=>{p.faqs=p.faqs||[],p.faqs.push({question:"",answer:""}),c(),re(t.querySelector("#leFaqRows"))}),re(t.querySelector("#leFaqRows")),t.querySelector("#leFaqSaveBtn").addEventListener("click",()=>fe({btnId:"leFaqSaveBtn",errorsBoxId:"leFaqErrors"}))}function tt(t,e){t.innerHTML=`
            ${u("Badges cortos (separados por ·, máx. 3)",`<input type="text" class="users-manager-input" id="leRhBadges" value="${s((e.badges||[]).join(" · "))}" placeholder="Garantía 6 meses · Reporte técnico incluido">`)}
            ${u("Líneas de meta (una por línea)",`<textarea class="users-manager-input client-modal-textarea" id="leRhMetaLines" rows="2">${s((e.meta_lines||[]).join(`
`))}</textarea>`)}
            ${u("Texto del botón",`<input type="text" class="users-manager-input" id="leRhWhatsapp" value="${s(e.whatsapp_text||"Cotizar por WhatsApp")}">`)}
            <p class="hs-config-note">El CTA siempre abre WhatsApp; no existe botón de llamada.</p>
            ${u("Precio mostrado (opcional)",`<input type="text" class="users-manager-input" id="leRhPriceLabel" value="${s(e.price_label)}" placeholder="$8,500 MXN + IVA">`)}
            ${u("Imágenes de fondo (galería del servicio)",`<select class="users-manager-select" id="leRhBgImages" multiple size="4">${N(e.background_image_ids,b.images)}</select>`)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <p class="hs-config-note">El título y la descripción corta se editan en "Información general" — aquí solo se controla su estilo.</p>
            <p class="live-editor-col-title" style="margin:6px 0 0;">Estilo del título (H1, fijo)</p>
            <div id="leRhTitleStyle"></div>
            <p class="live-editor-col-title" style="margin:14px 0 0;">Estilo de la descripción corta</p>
            <div id="leRhSubtitleStyle"></div>
        `,t.querySelector("#leRhBadges").addEventListener("input",a=>{e.badges=a.target.value.split("·").map(r=>r.trim()).filter(Boolean).slice(0,3),c(),d()}),t.querySelector("#leRhMetaLines").addEventListener("input",a=>{e.meta_lines=a.target.value.split(`
`).map(r=>r.trim()).filter(Boolean),c(),d()}),t.querySelector("#leRhWhatsapp").addEventListener("input",a=>{e.whatsapp_text=a.target.value,c(),d()}),t.querySelector("#leRhPriceLabel").addEventListener("input",a=>{e.price_label=a.target.value,c(),d()}),t.querySelector("#leRhBgImages").addEventListener("change",a=>{e.background_image_ids=Array.from(a.target.selectedOptions).map(r=>parseInt(r.value,10)),c(),d()}),e.title_style=e.title_style||{},e.subtitle_style=e.subtitle_style||{},B(t.querySelector("#leRhTitleStyle"),e.title_style,{}),B(t.querySelector("#leRhSubtitleStyle"),e.subtitle_style,{})}function ne(t,e){e.tabs=e.tabs||[];let a='<p class="hs-config-note">Cada pestaña se muestra como pestaña horizontal en público.</p><div id="leCtRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="leCtAdd" style="margin-top:10px;">+ Agregar pestaña</button>';t.innerHTML=a;const r=t.querySelector("#leCtRows");e.tabs.forEach((n,l)=>{const i=document.createElement("div");i.className="hs-repeat-row",i.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${l+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-label" placeholder="Título de pestaña" value="${s(n.label)}">
                <input type="text" class="users-manager-input le-subtitle" placeholder="Subtítulo (opcional)" style="margin-top:6px;" value="${s(n.subtitle)}">
                <textarea class="users-manager-input client-modal-textarea le-body" rows="2" placeholder="Párrafo" style="margin-top:6px;">${s(n.body)}</textarea>
                <textarea class="users-manager-input client-modal-textarea le-bullets" rows="2" placeholder="Viñetas, una por línea" style="margin-top:6px;">${s((n.bullets||[]).join(`
`))}</textarea>
                <select class="users-manager-select le-image" style="margin-top:6px;">
                    <option value="">Sin imagen</option>
                    ${N(n.image_id?[n.image_id]:[],b.images)}
                </select>
                <div class="le-tab-style" style="margin-top:6px;"></div>
            `,i.querySelector(".le-label").addEventListener("input",v=>{n.label=v.target.value,c(),$(),d()}),i.querySelector(".le-subtitle").addEventListener("input",v=>{n.subtitle=v.target.value,c(),d()}),i.querySelector(".le-body").addEventListener("input",v=>{n.body=v.target.value,c(),d()}),i.querySelector(".le-bullets").addEventListener("input",v=>{n.bullets=v.target.value.split(`
`).map(o=>o.trim()).filter(Boolean),c(),d()}),i.querySelector(".le-image").addEventListener("change",v=>{n.image_id=v.target.value||null,c(),d()}),i.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.tabs.splice(l,1),c(),ne(t,e),d()}),n.style=n.style||{},B(i.querySelector(".le-tab-style"),n.style,{}),r.appendChild(i)}),t.querySelector("#leCtAdd").addEventListener("click",()=>{e.tabs.push({label:"",subtitle:"",body:"",bullets:[],image_id:null}),c(),ne(t,e),d()})}function le(t,e){e.items=e.items||[],e.layout=e.layout==="vertical"?"vertical":"horizontal",t.innerHTML=`
            ${u("Diseño de las tarjetas",`
                <select class="users-manager-select" id="leBgLayout">
                    <option value="horizontal" ${e.layout==="horizontal"?"selected":""}>Horizontal (en fila)</option>
                    <option value="vertical" ${e.layout==="vertical"?"selected":""}>Vertical (apiladas)</option>
                </select>
            `)}
            <p class="hs-config-note">Tarjetas de cifra + título + descripción.</p><div id="leBgRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="leBgAdd" style="margin-top:10px;">+ Agregar beneficio</button>
        `,t.querySelector("#leBgLayout").addEventListener("change",r=>{e.layout=r.target.value,c(),d()});const a=t.querySelector("#leBgRows");e.items.forEach((r,n)=>{const l=document.createElement("div");l.className="hs-repeat-row",l.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${n+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-figure" placeholder="Cifra (ej. -12%)" value="${s(r.figure)}">
                <input type="text" class="users-manager-input le-title" placeholder="Título" style="margin-top:6px;" value="${s(r.title)}">
                <textarea class="users-manager-input client-modal-textarea le-description" rows="2" placeholder="Descripción corta" style="margin-top:6px;">${s(r.description)}</textarea>
            `,l.querySelector(".le-figure").addEventListener("input",i=>{r.figure=i.target.value,c(),d()}),l.querySelector(".le-title").addEventListener("input",i=>{r.title=i.target.value,c(),$(),d()}),l.querySelector(".le-description").addEventListener("input",i=>{r.description=i.target.value,c(),d()}),l.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.items.splice(n,1),c(),le(t,e),d()}),a.appendChild(l)}),t.querySelector("#leBgAdd").addEventListener("click",()=>{e.items.push({figure:"",title:"",description:""}),c(),le(t,e),d()})}function se(t,e){e.steps=e.steps||[],t.innerHTML='<p class="hs-config-note">Pasos numerados del proceso.</p><div id="lePsRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="lePsAdd" style="margin-top:10px;">+ Agregar paso</button>';const a=t.querySelector("#lePsRows");e.steps.forEach((r,n)=>{const l=document.createElement("div");l.className="hs-repeat-row",l.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${n+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-title" placeholder="Título del paso" value="${s(r.title)}">
                <textarea class="users-manager-input client-modal-textarea le-description" rows="2" placeholder="Descripción" style="margin-top:6px;">${s(r.description)}</textarea>
                <input type="text" class="users-manager-input le-duration" placeholder="Duración (ej. 1 h)" style="margin-top:6px;" value="${s(r.duration)}">
            `,l.querySelector(".le-title").addEventListener("input",i=>{r.title=i.target.value,c(),$(),d()}),l.querySelector(".le-description").addEventListener("input",i=>{r.description=i.target.value,c(),d()}),l.querySelector(".le-duration").addEventListener("input",i=>{r.duration=i.target.value,c(),d()}),l.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.steps.splice(n,1),c(),se(t,e),d()}),a.appendChild(l)}),t.querySelector("#lePsAdd").addEventListener("click",()=>{e.steps.push({title:"",description:"",duration:""}),c(),se(t,e),d()})}function at(t,e){t.innerHTML=u("Imágenes a mostrar (vacío = toda la galería)",`
            <select class="users-manager-select" id="leGcImages" multiple size="6">${N(e.image_ids,b.images)}</select>
            ${!b.images||!b.images.length?'<p class="hs-config-note" style="margin-top:6px;">Este servicio todavía no tiene imágenes en su galería.</p>':""}
        `),t.querySelector("#leGcImages").addEventListener("change",a=>{e.image_ids=Array.from(a.target.selectedOptions).map(r=>parseInt(r.value,10)),c(),d()})}function rt(t,e){t.innerHTML=`
            ${u("Texto descriptivo (opcional)",`<textarea class="users-manager-input client-modal-textarea" id="leRrDescription" rows="2">${s(e.description)}</textarea>`)}
            ${u('Reseñas visibles antes de "Ver más"',`<input type="number" class="users-manager-input" id="leRrPerPage" min="1" max="20" value="${e.reviews_per_page??3}">`)}
            <p class="hs-config-note">Las reseñas se capturan en el panel <strong>Reseñas</strong> del sidebar, y las estadísticas de "Promedio mostrado" en <strong>Información general</strong>. Esta sección solo define dónde aparecen y su texto descriptivo.</p>
        `,t.querySelector("#leRrDescription").addEventListener("input",a=>{e.description=a.target.value,c(),d()}),t.querySelector("#leRrPerPage").addEventListener("input",a=>{e.reviews_per_page=parseInt(a.target.value,10)||3,c(),d()})}function it(t,e){t.innerHTML=`
            ${u("Título",`<input type="text" class="users-manager-input" id="leCtaHeadline" value="${s(e.headline)}" placeholder="¿Listo para cotizar tu servicio?">`)}
            <div id="leCtaHeadlineStyle"></div>
            ${u("Texto de apoyo",`<textarea class="users-manager-input client-modal-textarea" id="leCtaSubtext" rows="2">${s(e.subtext)}</textarea>`)}
            <div id="leCtaSubtextStyle"></div>
            <div class="show-user-divider" style="margin:10px 0;"></div>
            ${u("Texto del botón",`<input type="text" class="users-manager-input" id="leCtaWhatsapp" value="${s(e.whatsapp_text||"Cotizar por WhatsApp")}">`)}
            <p class="hs-config-note">Deja este campo vacío para ocultar el botón de WhatsApp.</p>
            ${u("Imagen de fondo (opcional)",`<select class="users-manager-select" id="leCtaBg"><option value="">Sin imagen</option>${N(e.background_image_id?[e.background_image_id]:[],b.images)}</select>`)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <p class="live-editor-col-title">Botón secundario (opcional)</p>
            <p class="hs-config-note">Se muestra junto al de WhatsApp (o solo, si dejaste ese campo vacío) — útil para un enlace que no sea WhatsApp.</p>
            <div id="leCtaSecondaryBtn"></div>
        `,t.querySelector("#leCtaHeadline").addEventListener("input",a=>{e.headline=a.target.value,c(),d()}),t.querySelector("#leCtaSubtext").addEventListener("input",a=>{e.subtext=a.target.value,c(),d()}),t.querySelector("#leCtaWhatsapp").addEventListener("input",a=>{e.whatsapp_text=a.target.value,c(),d()}),t.querySelector("#leCtaBg").addEventListener("change",a=>{e.background_image_id=a.target.value||null,c(),d()}),e.headline_style=e.headline_style||{},e.subtext_style=e.subtext_style||{},B(t.querySelector("#leCtaHeadlineStyle"),e.headline_style,{tagChoices:["h2","h3"]}),B(t.querySelector("#leCtaSubtextStyle"),e.subtext_style,{}),e.secondary_button=e.secondary_button||{text:"",url:"",style:"outline",color:"#ff6213"},ee(t.querySelector("#leCtaSecondaryBtn"),e.secondary_button,{alignField:!1})}let Ce=null;function d(){clearTimeout(Ce),Ce=setTimeout(Le,400)}async function Le(){try{const e=await(await fetch(b.previewUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({sections:k.filter(a=>a.is_active).map((a,r)=>({...a,sort_order:r}))})})).json();H.srcdoc=e.html??""}catch(t){console.error("Error generando el preview:",t)}}H.addEventListener("load",()=>{try{const t=H.contentDocument;if(!t)return;const e=be(C);if(e&&e.id){const a=t.createElement("style");a.textContent=`[data-section-id="${e.id}"] { outline: 3px solid #ff6213; outline-offset: 2px; cursor: pointer; }`,t.head.appendChild(a)}t.body.addEventListener("click",a=>{const r=a.target.closest("[data-section-id]");if(!r)return;const n=r.getAttribute("data-section-id"),l=k.find(i=>String(i.id)===String(n));l&&(a.preventDefault(),ae(l._uid))},!0)}catch{}});const qe=document.getElementById("leViewportCaption");function nt(){qe&&(qe.textContent=W==="mobile"?"Móvil · 375px":"Escritorio · 1440px")}ve.addEventListener("click",t=>{const e=t.target.closest("button[data-viewport]");e&&(W=e.dataset.viewport,ve.querySelectorAll("button").forEach(a=>a.classList.toggle("is-active",a===e)),H.classList.toggle("is-mobile",W==="mobile"),nt())}),M.addEventListener("click",async()=>{M.disabled=!0;const t=M.textContent;M.textContent="Guardando...",j.textContent="Guardando…",j.className="live-editor-status is-saving";try{if(!(await fetch(b.saveUrl,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({sections:k.map((a,r)=>({...a,sort_order:r}))})})).ok)throw new Error("save request failed");window.location.reload()}catch(e){console.error("Error guardando la página:",e),alert("No se pudieron guardar los cambios. Intenta de nuevo."),M.disabled=!1,M.textContent=t,c()}}),k.length?($(),R()):_e(),Le()})();
