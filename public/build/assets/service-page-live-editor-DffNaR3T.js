(function(){const b=window.__LIVE_EDITOR__;if(!b)return;const $=document.querySelector('meta[name="csrf-token"]').content,H={banner:"Banner",dual_banner:"Banner Doble",product_carousel:"Carrusel de Productos",product_carousel_banner:"Carrusel con Banner",category_grid:"Grid de Categorías",brand_carousel:"Carrusel de Marcas",html_block:"Bloque HTML",faq:"Preguntas Frecuentes",rich_header:"Encabezado enriquecido",content_tabs:"Descripción por secciones",benefits_grid:"Beneficios / características",process_steps:"Proceso / cómo funciona",gallery_carousel:"Galería / carrusel",rating_reviews:"Rating y reseñas",cta_final:"CTA final",button:"Botón",table_block:"Tabla"},j={banner:'<rect width="18" height="12" x="3" y="6" rx="2"/><path d="M3 10h18"/>',dual_banner:'<rect width="8" height="14" x="3" y="5" rx="1.5"/><rect width="8" height="14" x="13" y="5" rx="1.5"/>',product_carousel:'<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',product_carousel_banner:'<rect width="18" height="12" x="3" y="6" rx="2"/><circle cx="9" cy="12" r="2"/>',category_grid:'<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/>',brand_carousel:'<path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/>',html_block:'<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',faq:'<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" x2="12.01" y1="17" y2="17"/>',rich_header:'<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="2" x2="22" y1="9" y2="9"/>',content_tabs:'<path d="M21 15V6"/><path d="M18.5 18a2.5 2.5 0 1 0 0-5H8a2 2 0 1 0 0 4h10"/><path d="M3 3v18"/><path d="M14 6H3"/>',benefits_grid:'<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',process_steps:'<path d="M4 17V9a2 2 0 0 1 2-2h2"/><path d="m18 8 4 4-4 4"/><path d="M4 21v-2a2 2 0 0 1 2-2h2"/><path d="M14 3h6v6"/>',gallery_carousel:'<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',rating_reviews:'<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',cta_final:'<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',button:'<rect width="18" height="7" x="3" y="8.5" rx="3.5"/>',table_block:'<path d="M3 3h18v18H3z"/><path d="M3 9h18"/><path d="M3 15h18"/><path d="M9 3v18"/>',default:'<rect width="18" height="18" x="3" y="3" rx="2"/>'},pe={featured:"Destacados",new:"Nuevos",recommended:"Recomendados",category:"Por Categoría",brand:"Por Marca",collection:"Por Colección",manual:"Selección Manual"};function Re(t){switch(t){case"banner":return{image_url:"",link_url:"",alt:""};case"dual_banner":return{left:{image_url:"",link_url:"",alt:""},right:{image_url:"",link_url:"",alt:""}};case"product_carousel":return{source:"featured",category_id:null,brand_id:null,collection_id:null,product_ids:[],limit:10};case"product_carousel_banner":return{banner_image_url:"",banner_link_url:"",banner_alt:"",source:"featured",category_id:null,brand_id:null,collection_id:null,product_ids:[],limit:10};case"category_grid":return{category_ids:[]};case"brand_carousel":return{};case"html_block":return{html:""};case"faq":return{description:""};case"rich_header":return{badges:[],whatsapp_text:"Cotizar por WhatsApp",meta_lines:[],background_image_ids:[],price_label:""};case"content_tabs":return{tabs:[]};case"benefits_grid":return{items:[],layout:"horizontal"};case"process_steps":return{steps:[]};case"gallery_carousel":return{image_ids:[]};case"rating_reviews":return{description:"",reviews_per_page:3};case"cta_final":return{headline:"",subtext:"",whatsapp_text:"Cotizar por WhatsApp",background_image_id:null,secondary_button:{text:"",url:"",style:"outline",color:"#ff6213"}};case"button":return{buttons:[{text:"Cotizar ahora",url:"",style:"solid",color:"#ff6213"}],align:"center"};case"table_block":return{title:"",description:"",headers:[],rows:[],buttons:[],align:"left"};default:return{}}}let V=0;const ve=()=>"u"+ ++V,S=(b.sections||[]).map(t=>({_uid:ve(),id:t.id??null,type:t.type,title:t.title??"",config:t.config&&typeof t.config=="object"?t.config:{},is_active:t.is_active!==!1}));let k=null,C="empty",ge=!1,K="desktop";const v=Object.assign({name:"",slug:"",short_description:"",price:"",currency:"MXN",show_price:!0,background_color:null,seo_title:"",seo_description:"",canonical_url:"",is_active:!1,faqs:[],rating_average_displayed:"",rating_total_rated:"",rating_recommend_percent:"",rating_punctuality_average:"",rating_recurring_clients:"",rating_since_year:"",rating_distribution:{}},b.general||{}),x=(b.reviews||[]).map(t=>Object.assign({},t)),J=document.getElementById("leBlocksList"),Ae=document.getElementById("leAddBlockType"),Me=document.getElementById("leAddBlockBtn"),_=document.getElementById("leEditPanel"),D=document.getElementById("leIframe"),F=document.getElementById("leDirtyIndicator"),G=document.getElementById("leSaveBtn"),me=document.getElementById("leViewportToggle"),X=document.getElementById("leGeneralInfoBtn"),Q=document.getElementById("leGalleryBtn"),Y=document.getElementById("leReviewsBtn"),ye=document.querySelector(".live-editor-heading-row__left h1"),N=document.getElementById("leDeleteModal"),Be=document.getElementById("leDeleteModalTitle"),Ie=document.getElementById("leDeleteModalAvatar"),Ge=document.getElementById("leDeleteModalCancel"),Pe=document.getElementById("leDeleteModalConfirm");let Z=null;function ee(t,e){Z=e,Be.textContent=t,Ie.textContent=(t||"?").charAt(0).toUpperCase(),N.classList.add("active")}function te(){Z=null,N.classList.remove("active")}Ge.addEventListener("click",te),N.addEventListener("click",t=>{t.target===N&&te()}),Pe.addEventListener("click",()=>{const t=Z;te(),typeof t=="function"&&t()});function o(t){return String(t??"").replace(/&/g,"&amp;").replace(/"/g,"&quot;").replace(/</g,"&lt;").replace(/>/g,"&gt;")}function He(t){return String(t??"").toLowerCase().normalize("NFD").replace(/[^\x00-\x7F]/g,"").replace(/[^a-z0-9\s-]/g,"").trim().replace(/\s+/g,"-")}const je=["#000000","#141516","#374151","#4b5563","#6b7280","#9ca3af","#d1d5db","#f3f4f6","#ffffff","#ef4444","#f97316","#ff6213","#f59e0b","#eab308","#84cc16","#22c55e","#10b981","#14b8a6","#06b6d4","#0ea5e9","#3b82f6","#6366f1","#8b5cf6","#a855f7","#d946ef","#ec4899","#f43f5e","#7c2d12","#78350f","#365314","#134e4a","#1e3a8a","#4c1d95"],he="emb-custom-colors";function De(){try{const t=window.localStorage.getItem(he),e=t?JSON.parse(t):[];return Array.isArray(e)?e:[]}catch{return[]}}function be(t){try{window.localStorage.setItem(he,JSON.stringify(t))}catch{}}function _e(t){return/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(t)}function ae(t,e,a){let r=De();function i(n,p){return`<button type="button" class="le-color-swatch ${e&&e.toLowerCase()===n.toLowerCase()?"is-active":""}" data-hex="${n}" title="${n}" style="background:${n}">
                ${p?'<span class="le-color-swatch-remove" data-remove="'+n+'" title="Quitar de mis colores">&times;</span>':""}
            </button>`}function l(){t.innerHTML=`
                <div class="le-color-swatches">${je.map(s=>i(s,!1)).join("")}</div>
                ${r.length?`
                    <div class="le-color-custom-label">Mis colores</div>
                    <div class="le-color-swatches">${r.map(s=>i(s,!0)).join("")}</div>
                `:""}
                <div class="le-color-custom-row">
                    <input type="color" class="le-color-native" value="${_e(e)?e:"#ff6213"}">
                    <input type="text" class="users-manager-input le-color-hex" placeholder="#ff6213" value="${o(e||"")}">
                    <button type="button" class="live-editor-btn live-editor-btn--outline le-color-save">Guardar</button>
                </div>
            `,t.querySelectorAll(".le-color-swatch").forEach(s=>{s.addEventListener("click",h=>{h.target.closest(".le-color-swatch-remove")||(a(s.dataset.hex),l())})}),t.querySelectorAll(".le-color-swatch-remove").forEach(s=>{s.addEventListener("click",h=>{h.stopPropagation();const m=s.dataset.remove;r=r.filter(y=>y!==m),be(r),l()})});const n=t.querySelector(".le-color-native"),p=t.querySelector(".le-color-hex");n.addEventListener("input",()=>{p.value=n.value}),t.querySelector(".le-color-save").addEventListener("click",()=>{let s=p.value.trim();s&&(s.startsWith("#")||(s="#"+s),_e(s)&&(r.includes(s)||(r=[...r,s],be(r)),a(s),l()))})}l()}function P(t,e,a){a=a||{};const r=a.tagChoices||null,i=a.onChange||(()=>{}),l="leStyle"+ ++V;function n(){c(),i(),d()}const p=r?u("Etiqueta de encabezado",`
            <select class="users-manager-select" id="${l}Tag">
                ${r.map(s=>`<option value="${s}" ${(e.heading_tag||r[0])===s?"selected":""}>${s.toUpperCase()}</option>`).join("")}
            </select>
        `):"";t.innerHTML=`
            <p class="live-editor-col-title" style="margin:14px 0 8px;">Estilo del texto</p>
            ${p}
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
                ${u("Tamaño (px)",`<input type="number" class="users-manager-input" id="${l}Size" min="10" max="72" placeholder="Auto" value="${o(e.font_size||"")}">`)}
            </div>
            ${u("Familia tipográfica",`
                <select class="users-manager-select" id="${l}Family">
                    <option value="" ${e.font_family?"":"selected"}>Predeterminada (Inter)</option>
                    <option value="Inter Tight" ${e.font_family==="Inter Tight"?"selected":""}>Inter Tight</option>
                </select>
            `)}
            ${u("Color de texto",`<div id="${l}Color"></div>`)}
        `,r&&t.querySelector("#"+l+"Tag").addEventListener("change",s=>{e.heading_tag=s.target.value,n()}),t.querySelector("#"+l+"Align").addEventListener("click",s=>{const h=s.target.closest("button[data-align]");h&&(e.text_align=h.dataset.align,t.querySelectorAll("#"+l+"Align button").forEach(m=>m.classList.toggle("is-active",m===h)),n())}),t.querySelector("#"+l+"Size").addEventListener("input",s=>{const h=parseInt(s.target.value,10);e.font_size=Number.isFinite(h)?h:null,n()}),t.querySelector("#"+l+"Family").addEventListener("change",s=>{e.font_family=s.target.value||null,n()}),ae(t.querySelector("#"+l+"Color"),e.text_color,s=>{e.text_color=s,n()})}function re(t,e,a){a=a||{};const r=a.alignField!==!1,i="leBtn"+ ++V;t.innerHTML=`
            ${u("Texto del botón",`<input type="text" class="users-manager-input" id="${i}Text" value="${o(e.text)}" placeholder="Cotizar ahora">`)}
            ${u("Enlace",`
                <div style="display:flex;gap:8px;">
                    <input type="text" class="users-manager-input" id="${i}Url" value="${o(e.url)}" placeholder="https:// o /servicios/..." style="flex:1;">
                    <button type="button" class="live-editor-btn live-editor-btn--outline" id="${i}LinkPick">Elegir enlace</button>
                </div>
            `)}
            <div class="live-editor-field-row">
                ${u("Estilo",`
                    <select class="users-manager-select" id="${i}Style">
                        <option value="solid" ${(e.style||"solid")==="solid"?"selected":""}>Sólido</option>
                        <option value="outline" ${e.style==="outline"?"selected":""}>Contorno</option>
                    </select>
                `)}
                ${r?u("Alineación",`
                    <select class="users-manager-select" id="${i}Align">
                        <option value="left" ${e.align==="left"?"selected":""}>Izquierda</option>
                        <option value="center" ${(e.align||"center")==="center"?"selected":""}>Centro</option>
                        <option value="right" ${e.align==="right"?"selected":""}>Derecha</option>
                    </select>
                `):""}
            </div>
            ${u("Color",`<div id="${i}Color"></div>`)}
        `,t.querySelector("#"+i+"Text").addEventListener("input",n=>{e.text=n.target.value,c(),d()});const l=t.querySelector("#"+i+"Url");l.addEventListener("input",n=>{e.url=n.target.value,c(),d()}),t.querySelector("#"+i+"LinkPick").addEventListener("click",n=>{typeof window.LinkPicker!="function"&&!(window.LinkPicker&&window.LinkPicker.open)||window.LinkPicker.open({anchorEl:n.target,onSelect:p=>{l.value=p,e.url=p,c(),d()}})}),t.querySelector("#"+i+"Style").addEventListener("change",n=>{e.style=n.target.value,c(),d()}),r&&t.querySelector("#"+i+"Align").addEventListener("change",n=>{e.align=n.target.value,c(),d()}),ae(t.querySelector("#"+i+"Color"),e.color||"#ff6213",n=>{e.color=n,c(),d()})}function ie(t,e){Array.isArray(e.buttons)||(e.buttons=e.text||e.url?[{text:e.text||"",url:e.url||"",style:e.style||"solid",color:e.color||"#ff6213"}]:[{text:"Cotizar ahora",url:"",style:"solid",color:"#ff6213"}]),e.align=e.align||"center",t.innerHTML=`
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
        `,t.querySelector("#leBtnAlign").addEventListener("change",r=>{e.align=r.target.value,c(),d()});const a=t.querySelector("#leBtnRows");e.buttons.forEach((r,i)=>{const l=document.createElement("div");l.className="hs-repeat-row";const n=document.createElement("div");n.className="hs-repeat-row-head",n.innerHTML=`<span class="hs-repeat-row-num">${i+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button>`,l.appendChild(n);const p=document.createElement("div");l.appendChild(p),re(p,r,{alignField:!1}),n.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.buttons.splice(i,1),c(),ie(t,e),d()}),a.appendChild(l)}),t.querySelector("#leBtnAdd").addEventListener("click",()=>{e.buttons.push({text:"",url:"",style:"outline",color:"#ff6213"}),c(),ie(t,e),d()})}function T(t,e){e.headers=Array.isArray(e.headers)?e.headers:[],e.rows=Array.isArray(e.rows)?e.rows:[],e.buttons=Array.isArray(e.buttons)?e.buttons:[],e.align=e.align||"left";const a=Math.max(e.headers.length,1);t.innerHTML=`
            ${u("Descripción (opcional, arriba de la tabla)",`<textarea class="users-manager-input client-modal-textarea" id="leTbDesc" rows="2">${o(e.description)}</textarea>`)}
            <p class="hs-config-subtitle">Columnas</p>
            <div id="leTbHeaders" class="hs-repeat-rows"></div>
            <button type="button" class="button-secondary size-adjustment" id="leTbAddCol" style="margin-top:6px;">+ Agregar columna</button>

            <p class="hs-config-subtitle" style="margin-top:16px;">Filas</p>
            <div id="leTbRows" class="hs-repeat-rows"></div>
            <button type="button" class="button-secondary size-adjustment" id="leTbAddRow" style="margin-top:6px;" ${e.headers.length?"":'disabled title="Agrega al menos una columna primero"'}>+ Agregar fila</button>

            <p class="hs-config-subtitle" style="margin-top:16px;">Botones debajo de la tabla (opcional)</p>
            <div id="leTbButtons" class="hs-repeat-rows"></div>
            <button type="button" class="button-secondary size-adjustment" id="leTbAddBtn" style="margin-top:6px;">+ Agregar botón</button>
        `,t.querySelector("#leTbDesc").addEventListener("input",n=>{e.description=n.target.value,c(),d()});const r=t.querySelector("#leTbHeaders");e.headers.forEach((n,p)=>{const s=document.createElement("div");s.className="hs-repeat-row",s.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${p+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar columna">&times;</button></div>
                <input type="text" class="users-manager-input le-header" placeholder="Nombre de columna" value="${o(n)}">
            `,s.querySelector(".le-header").addEventListener("input",h=>{e.headers[p]=h.target.value,c(),d()}),s.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.headers.splice(p,1),e.rows.forEach(h=>h.splice(p,1)),c(),T(t,e),d()}),r.appendChild(s)}),t.querySelector("#leTbAddCol").addEventListener("click",()=>{e.headers.push(""),e.rows.forEach(n=>n.push("")),c(),T(t,e),d()});const i=t.querySelector("#leTbRows");e.rows.forEach((n,p)=>{for(;n.length<a;)n.push("");const s=document.createElement("div");s.className="hs-repeat-row";const h=n.map((m,y)=>`
                <input type="text" class="users-manager-input le-cell" data-ci="${y}" placeholder="${o(e.headers[y]||"Celda "+(y+1))}" value="${o(m)}" style="${y>0?"margin-top:6px;":""}">
            `).join("");s.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${p+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar fila">&times;</button></div>
                ${h}
            `,s.querySelectorAll(".le-cell").forEach(m=>{m.addEventListener("input",y=>{n[parseInt(y.target.dataset.ci,10)]=y.target.value,c(),d()})}),s.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.rows.splice(p,1),c(),T(t,e),d()}),i.appendChild(s)}),t.querySelector("#leTbAddRow").addEventListener("click",()=>{e.rows.push(new Array(a).fill("")),c(),T(t,e),d()});const l=t.querySelector("#leTbButtons");e.buttons.forEach((n,p)=>{const s=document.createElement("div");s.className="hs-repeat-row";const h=document.createElement("div");h.className="hs-repeat-row-head",h.innerHTML=`<span class="hs-repeat-row-num">${p+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button>`,s.appendChild(h);const m=document.createElement("div");s.appendChild(m),re(m,n,{alignField:!1}),h.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.buttons.splice(p,1),c(),T(t,e),d()}),l.appendChild(s)}),t.querySelector("#leTbAddBtn").addEventListener("click",()=>{e.buttons.push({text:"",url:"",style:"outline",color:"#ff6213"}),c(),T(t,e),d()})}function c(){ge||(ge=!0,F.textContent="● Cambios sin guardar",F.className="live-editor-status is-dirty")}function we(t){return S.find(e=>e._uid===t)||null}let R=null;function L(){if(J.innerHTML="",!S.length){const t=document.createElement("div");t.className="live-editor-blocks-empty",t.textContent="Este servicio todavía no tiene bloques. Agrega uno abajo.",J.appendChild(t);return}S.forEach(t=>{const e=document.createElement("div");e.className="live-editor-block-row",C==="block"&&t._uid===k&&e.classList.add("is-selected"),t.is_active||e.classList.add("is-inactive"),e.draggable=!0,e.dataset.uid=t._uid;const a=j[t.type]||j.default;e.innerHTML=`
                <span class="live-editor-block-drag-handle" title="Arrastrar para reordenar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                </span>
                <span class="live-editor-block-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${a}</svg>
                </span>
                <span class="live-editor-block-info">
                    <span class="live-editor-block-name">${o(t.title||H[t.type]||t.type)}</span>
                    <span class="live-editor-block-type">${o(H[t.type]||t.type)}</span>
                </span>
                <span class="live-editor-block-actions">
                    <button type="button" class="live-editor-toggle-active ${t.is_active?"is-on":""}" title="Activa/Inactiva"></button>
                    <button type="button" class="live-editor-block-delete" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    </button>
                </span>
            `,e.addEventListener("click",r=>{r.target.closest(".live-editor-toggle-active")||r.target.closest(".live-editor-block-delete")||ne(t._uid)}),e.querySelector(".live-editor-toggle-active").addEventListener("click",r=>{r.stopPropagation(),t.is_active=!t.is_active,c(),L(),d()}),e.querySelector(".live-editor-block-delete").addEventListener("click",r=>{r.stopPropagation();const i=t.title||H[t.type]||t.type;ee(i,()=>{const l=S.findIndex(n=>n._uid===t._uid);l!==-1&&S.splice(l,1),k===t._uid&&(k=null,B()),c(),L(),d()})}),e.addEventListener("dragstart",()=>{R=t._uid,e.classList.add("is-dragging")}),e.addEventListener("dragend",()=>{e.classList.remove("is-dragging")}),e.addEventListener("dragover",r=>{r.preventDefault(),!(R===null||R===t._uid)&&e.classList.add("drag-over")}),e.addEventListener("dragleave",()=>{e.classList.remove("drag-over")}),e.addEventListener("drop",r=>{if(r.preventDefault(),e.classList.remove("drag-over"),R===null||R===t._uid)return;const i=S.findIndex(p=>p._uid===R),l=S.findIndex(p=>p._uid===t._uid);if(R=null,i===-1||l===-1)return;const[n]=S.splice(i,1);S.splice(l,0,n),c(),L(),d()}),J.appendChild(e)})}function O(){X.classList.remove("is-selected"),Q.classList.remove("is-selected"),Y.classList.remove("is-selected")}function ne(t){k=t,C="block",O(),L(),B()}function fe(){k=null,C="general",O(),X.classList.add("is-selected"),L(),B()}function Fe(){k=null,C="gallery",O(),Q.classList.add("is-selected"),L(),B()}function Ne(){k=null,C="reviews",O(),Y.classList.add("is-selected"),L(),B()}X.addEventListener("click",fe),Q.addEventListener("click",Fe),Y.addEventListener("click",Ne),Me.addEventListener("click",()=>{const t=Ae.value,e={_uid:ve(),id:null,type:t,title:"",config:Re(t),is_active:!0};S.push(e),c(),ne(e._uid),d()});function u(t,e,a){return`<div class="live-editor-field ${a||""}">
            <label>${o(t)}</label>
            ${e}
        </div>`}function z(t,e){const a=(t||[]).map(String);return(e||[]).map(r=>`<option value="${r.id}" ${a.includes(String(r.id))?"selected":""}>${o(r.alt_text||"Imagen #"+r.id)}</option>`).join("")}function xe(){_.innerHTML=`
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
            ${u("Nombre",`<input type="text" class="users-manager-input" id="leGenName" value="${o(v.name)}">`)}
            ${u("Slug (URL)",`
                <div style="display:flex;gap:8px;">
                    <input type="text" class="users-manager-input" id="leGenSlug" value="${o(v.slug)}" style="flex:1;">
                    <button type="button" id="leGenSlugGenerate" class="live-editor-btn live-editor-btn--outline" title="Generar a partir del nombre">Generar</button>
                </div>
            `,"")}
            <div class="live-editor-field-row">
                ${u("Tipo de página",`
                    <select class="users-manager-select" id="leGenPageType">
                        <option value="service" ${v.page_type==="service"?"selected":""}>Servicio (nivel 3)</option>
                        <option value="category" ${v.page_type==="category"?"selected":""}>Categoría (nivel 2)</option>
                        <option value="hub" ${v.page_type==="hub"?"selected":""}>Hub — /servicios (nivel 1)</option>
                    </select>
                `)}
                ${u("Página padre",`
                    <select class="users-manager-select" id="leGenParentId">
                        <option value="">Sin padre (nivel raíz)</option>
                        ${(b.eligibleParents||[]).map(n=>`<option value="${n.id}" ${String(v.parent_id)===String(n.id)?"selected":""}>${o(n.name)} (${n.page_type==="hub"?"Hub":"Categoría"})</option>`).join("")}
                    </select>
                `,"leGenParentField")}
            </div>
            <p class="hs-config-note">/servicios → hub · /servicios/{categoría} → nivel 2 · /servicios/{categoría}/{servicio} → nivel 3. Un servicio sin padre se sirve en /servicio/{slug} (legacy).</p>
            ${u("Descripción corta",`<textarea class="users-manager-input client-modal-textarea" id="leGenShortDesc" rows="2">${o(v.short_description)}</textarea>`)}
            <div class="live-editor-field-row">
                ${u("Precio (opcional)",`<input type="number" step="0.01" min="0" class="users-manager-input" id="leGenPrice" value="${o(v.price)}">`)}
                ${u("Moneda",`<input type="text" class="users-manager-input" id="leGenCurrency" value="${o(v.currency)}" maxlength="10">`)}
            </div>
            ${u("Visibilidad del precio",`
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenShowPrice" ${v.show_price?"checked":""}> Mostrar el precio en el sitio público
                </label>
                <p class="hs-config-note" style="margin-top:4px;">Si lo desmarcas, el servicio se sigue publicando pero sin precio visible — útil para cotizar en privado.</p>
            `)}
            ${u("Estado",`
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenIsActive" ${v.is_active?"checked":""}> Publicado (visible en el sitio público)
                </label>
            `)}
            ${u("Color de fondo de la página (opcional)",`
                <div id="leGenBgColorPicker"></div>
                <button type="button" id="leGenBgColorClear" class="live-editor-btn live-editor-btn--outline" style="margin-top:8px;" ${v.background_color?"":"disabled"}>Quitar color (usar blanco)</button>
            `)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            ${u("Título SEO",`<input type="text" class="users-manager-input" id="leGenSeoTitle" value="${o(v.seo_title)}" maxlength="160">`)}
            ${u("Descripción SEO",`<textarea class="users-manager-input client-modal-textarea" id="leGenSeoDesc" rows="2" maxlength="500">${o(v.seo_description)}</textarea>`)}
            ${u("",`
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenIsCanonical" ${v.canonical_url?"":"checked"}> Es la URL Canónica de este servicio
                </label>
                <p class="hs-config-note" style="margin-top:4px;">Marcado (normal): Google usa la URL de este mismo servicio. Desmárcalo solo si este servicio es muy parecido a otro y quieres que Google indexe ese otro en su lugar.</p>
            `)}
            <div id="leGenCanonicalUrlWrap" style="${v.canonical_url?"":"display:none;"}">
                ${u("URL Canónica",`<input type="url" class="users-manager-input" id="leGenCanonicalUrl" value="${o(v.canonical_url)}" maxlength="255" placeholder="https://equitermindustries.com.mx/servicio/otro-servicio-similar">`)}
            </div>
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div class="live-editor-field">
                <label>Rating y reseñas — Promedio mostrado</label>
                <p class="hs-config-note" style="margin:0 0 8px;">Contenido curado por el equipo, igual que la FAQ. Estas cifras son de marketing (no tienen por qué coincidir con el número de reseñas capturadas en el panel "Reseñas") y nunca alimentan el marcado SEO — el marcado usa siempre el conteo real.</p>
            </div>
            <div class="live-editor-field-row">
                ${u("Promedio mostrado (0–5)",`<input type="number" step="0.1" min="0" max="5" class="users-manager-input" id="leGenRatingAvg" value="${o(v.rating_average_displayed)}">`)}
                ${u("Total de servicios calificados",`<input type="number" min="0" class="users-manager-input" id="leGenRatingTotal" value="${o(v.rating_total_rated)}">`)}
            </div>
            <div class="live-editor-field-row">
                ${u("% que recomendaría el servicio",`<input type="number" step="0.1" min="0" max="100" class="users-manager-input" id="leGenRatingRecommend" value="${o(v.rating_recommend_percent)}">`)}
                ${u("Puntualidad de cuadrilla (0–5)",`<input type="number" step="0.1" min="0" max="5" class="users-manager-input" id="leGenRatingPunctuality" value="${o(v.rating_punctuality_average)}">`)}
            </div>
            <div class="live-editor-field-row">
                ${u("Clientes recurrentes",`<input type="number" min="0" class="users-manager-input" id="leGenRatingRecurring" value="${o(v.rating_recurring_clients)}">`)}
                ${u("Calificando desde (año, opcional)",`<input type="number" min="2000" max="2100" class="users-manager-input" id="leGenRatingSince" value="${o(v.rating_since_year)}">`)}
            </div>
            ${u("Distribución por estrella",`
                <div class="live-editor-field-row" style="grid-template-columns:repeat(5,1fr);">
                    ${[5,4,3,2,1].map(n=>`
                        <div>
                            <label style="font-size:11px;color:#6b7280;display:block;margin-bottom:2px;">${n} ★</label>
                            <input type="number" min="0" class="users-manager-input le-gen-rating-dist" data-star="${n}" value="${o((v.rating_distribution||{})[n]??(v.rating_distribution||{})[String(n)]??"")}">
                        </div>
                    `).join("")}
                </div>
            `)}
            <div id="leGenErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
            <button type="button" id="leGenSaveBtn" class="live-editor-btn live-editor-btn--solid live-editor-btn--block">Guardar información general</button>
        `,_.querySelector("#leGenSaveBtn").addEventListener("click",()=>Se()),_.querySelector("#leGenSlugGenerate").addEventListener("click",()=>{const n=_.querySelector("#leGenName");_.querySelector("#leGenSlug").value=He(n.value),c()});const t=_.querySelector("#leGenIsCanonical"),e=_.querySelector("#leGenCanonicalUrlWrap");t.addEventListener("change",()=>{e.style.display=t.checked?"none":""});const a=_.querySelector("#leGenPageType"),r=_.querySelector(".leGenParentField"),i=()=>{const n=a.value==="hub"||a.value==="category";r&&(r.style.display=n?"none":"")};a.addEventListener("change",i),i();const l=_.querySelector("#leGenBgColorClear");ae(_.querySelector("#leGenBgColorPicker"),v.background_color||"",n=>{v.background_color=n,l.disabled=!1,c()}),l.addEventListener("click",()=>{v.background_color=null,l.disabled=!0,xe(),c()})}function le(t){v.faqs=v.faqs||[];const e=t;e&&(e.innerHTML="",v.faqs.forEach((a,r)=>{const i=document.createElement("div");i.className="hs-faq-row",i.innerHTML=`
                <div class="hs-faq-row-head">
                    <span class="hs-faq-row-num">${r+1}</span>
                    <div class="hs-faq-row-actions">
                        <button type="button" class="hs-faq-btn hs-faq-remove" title="Eliminar">&times;</button>
                    </div>
                </div>
                <input type="text" class="users-manager-input hs-faq-question" placeholder="Pregunta" value="${o(a.question)}">
                <textarea class="users-manager-input client-modal-textarea hs-faq-answer" rows="2" placeholder="Respuesta">${o(a.answer)}</textarea>
            `,i.querySelector(".hs-faq-question").addEventListener("input",l=>{a.question=l.target.value,c()}),i.querySelector(".hs-faq-answer").addEventListener("input",l=>{a.answer=l.target.value,c()}),i.querySelector(".hs-faq-remove").addEventListener("click",()=>{v.faqs.splice(r,1),c(),le(t)}),e.appendChild(i)}))}async function Se(t){t=t||{};const e=_.querySelector("#"+(t.btnId||"leGenSaveBtn")),a=_.querySelector("#"+(t.errorsBoxId||"leGenErrors"));a.style.display="none",a.innerHTML="",document.querySelectorAll("#leEditPanel .is-invalid").forEach(m=>m.classList.remove("is-invalid"));const r=m=>_.querySelector("#"+m),i=(m,y)=>{const g=r(m);return g?g.value:y},l=(m,y)=>{const g=r(m);return g?g.checked:y},n=i("leGenPageType",v.page_type),p=_.querySelectorAll(".le-gen-rating-dist"),s={name:i("leGenName",v.name),slug:i("leGenSlug",v.slug),page_type:n,parent_id:n==="hub"?"":i("leGenParentId",v.parent_id||""),short_description:i("leGenShortDesc",v.short_description),price:i("leGenPrice",v.price),currency:i("leGenCurrency",v.currency),show_price:l("leGenShowPrice",v.show_price),background_color:v.background_color||"",is_active:l("leGenIsActive",v.is_active),seo_title:i("leGenSeoTitle",v.seo_title),seo_description:i("leGenSeoDesc",v.seo_description),is_canonical:l("leGenIsCanonical",!v.canonical_url),canonical_url:i("leGenCanonicalUrl",v.canonical_url),faq_items:v.faqs||[],rating_average_displayed:i("leGenRatingAvg",v.rating_average_displayed),rating_total_rated:i("leGenRatingTotal",v.rating_total_rated),rating_recommend_percent:i("leGenRatingRecommend",v.rating_recommend_percent),rating_punctuality_average:i("leGenRatingPunctuality",v.rating_punctuality_average),rating_recurring_clients:i("leGenRatingRecurring",v.rating_recurring_clients),rating_since_year:i("leGenRatingSince",v.rating_since_year),rating_distribution:p.length?Array.from(p).reduce((m,y)=>(m[y.dataset.star]=y.value,m),{}):v.rating_distribution||{}};e.disabled=!0;const h=e.textContent;e.textContent="Guardando...";try{const m=await fetch(b.generalUrl,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":$,Accept:"application/json"},body:JSON.stringify(s)}),y=await m.json();if(m.ok){Object.assign(v,y.servicePage),ye&&(ye.textContent=v.name),document.title="Editor en vivo - "+v.name+" - Admin";const g=document.getElementById("leBrowserUrl");g&&(g.textContent="equitermindustries.com.mx"+v.public_path);const I=document.getElementById("leViewLiveLink");if(I){const w=window.location.origin;I.href=w+v.public_path}window.showCenterToast&&showCenterToast("Información general guardada."),d()}else if(m.status===422){const g=y.errors||{};a.innerHTML=Object.values(g).flat().map(w=>`<p>${w}</p>`).join(""),a.style.display="block";const I={name:"leGenName",slug:"leGenSlug",page_type:"leGenPageType",parent_id:"leGenParentId",short_description:"leGenShortDesc",price:"leGenPrice",currency:"leGenCurrency",seo_title:"leGenSeoTitle",seo_description:"leGenSeoDesc",canonical_url:"leGenCanonicalUrl",rating_average_displayed:"leGenRatingAvg",rating_total_rated:"leGenRatingTotal",rating_recommend_percent:"leGenRatingRecommend",rating_punctuality_average:"leGenRatingPunctuality",rating_recurring_clients:"leGenRatingRecurring",rating_since_year:"leGenRatingSince"};Object.keys(g).forEach(w=>{const f=_.querySelector("#"+(I[w]||""));f&&f.classList.add("is-invalid")})}else throw new Error("save-general-failed")}catch{a.innerHTML="<p>No se pudo guardar. Intenta de nuevo.</p>",a.style.display="block"}finally{e.disabled=!1,e.textContent=h}}let A=null;function Oe(){_.innerHTML=`
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
        `,U()}function U(){const t=_.querySelector("#leGalleryGrid");if(!t)return;t.innerHTML="",(b.images||[]).forEach((a,r)=>{const i=document.createElement("div");i.className="service-gallery-item",i.dataset.id=a.id,i.draggable=!0,i.innerHTML=`
                <div class="service-gallery-item__drag" title="Arrastrar para reordenar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                </div>
                ${r===0?'<span class="service-gallery-item__badge">PORTADA</span>':""}
                <button type="button" class="service-gallery-item__remove" title="Quitar">&times;</button>
                <img src="${a.url}" alt="${o(a.alt_text||"")}">
                <input type="text" class="users-manager-input service-gallery-item__alt" placeholder="Texto alternativo" value="${o(a.alt_text||"")}">
            `,i.querySelector(".service-gallery-item__remove").addEventListener("click",()=>{ee(a.alt_text||"Imagen #"+a.id,()=>Ue(a.id))});let l=null;i.querySelector(".service-gallery-item__alt").addEventListener("input",n=>{a.alt_text=n.target.value,clearTimeout(l),l=setTimeout(()=>We(a.id,a.alt_text),500)}),i.addEventListener("dragstart",()=>{A=a.id,i.classList.add("is-dragging")}),i.addEventListener("dragend",()=>{i.classList.remove("is-dragging")}),i.addEventListener("dragover",n=>{n.preventDefault(),!(A===null||A===a.id)&&i.classList.add("drag-over")}),i.addEventListener("dragleave",()=>{i.classList.remove("drag-over")}),i.addEventListener("drop",n=>{if(n.preventDefault(),i.classList.remove("drag-over"),A===null||A===a.id)return;const p=b.images.findIndex(m=>String(m.id)===String(A)),s=b.images.findIndex(m=>String(m.id)===String(a.id));if(A=null,p===-1||s===-1)return;const[h]=b.images.splice(p,1);b.images.splice(s,0,h),U(),Ve()}),t.appendChild(i)});const e=document.createElement("div");e.className="service-gallery-item service-gallery-item--add",e.id="leGalleryAddTile",e.innerHTML=`
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            <span>Arrastra o selecciona</span>
        `,e.addEventListener("click",()=>{typeof window.openImagePicker=="function"&&window.openImagePicker(null,{onSelect:ze})}),t.appendChild(e)}async function ze(t){try{const e=await fetch(b.imagesStoreUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":$,Accept:"application/json"},body:JSON.stringify({image_url:t,alt_text:""})}),a=await e.json();e.ok?(b.images.push({id:a.image.id,url:a.image.url,alt_text:a.image.alt_text}),U(),window.showCenterToast&&showCenterToast("Imagen agregada."),d()):window.showCenterToast&&showCenterToast("No se pudo agregar la imagen.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al agregar la imagen.","error")}}async function Ue(t){try{if((await fetch(b.imageDestroyUrlTemplate.replace("__IMAGE_ID__",t),{method:"DELETE",headers:{"X-CSRF-TOKEN":$,Accept:"application/json"}})).ok){const a=b.images.findIndex(r=>String(r.id)===String(t));a!==-1&&b.images.splice(a,1),U(),window.showCenterToast&&showCenterToast("Imagen eliminada."),d()}else window.showCenterToast&&showCenterToast("No se pudo eliminar la imagen.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al eliminar la imagen.","error")}}async function We(t,e){try{await fetch(b.imageUpdateUrlTemplate.replace("__IMAGE_ID__",t),{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":$,Accept:"application/json"},body:JSON.stringify({alt_text:e})})}catch{}}async function Ve(){const t=b.images.map(e=>e.id);try{(await fetch(b.imagesReorderUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":$,Accept:"application/json"},body:JSON.stringify({order:t})})).ok?(window.showCenterToast&&showCenterToast("Orden de galería actualizado."),d()):window.showCenterToast&&showCenterToast("No se pudo guardar el nuevo orden de la galería.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al reordenar la galería.","error")}}let M=null;function se(){const t=x.filter(e=>e.is_visible).length;_.innerHTML=`
            <div class="live-editor-panel-header">
                <span class="live-editor-panel-header-info">
                    <span class="live-editor-block-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </span>
                    <span>
                        <strong>Reseñas capturadas</strong>
                        <small>${t} visibles de ${x.length}</small>
                    </span>
                </span>
            </div>
            <div id="leReviewsList" class="hs-repeat-rows"></div>
            <button type="button" id="leReviewAddBtn" class="live-editor-btn live-editor-btn--outline live-editor-btn--block" style="margin-top:10px;">+ Agregar reseña</button>
            <div id="leReviewFormWrap"></div>
        `,Ee(),_.querySelector("#leReviewAddBtn").addEventListener("click",()=>Le(null))}function Ee(){const t=_.querySelector("#leReviewsList");if(t){if(t.innerHTML="",!x.length){t.innerHTML='<p class="hs-config-note">Todavía no hay reseñas capturadas para este servicio.</p>';return}x.forEach(e=>{const a=document.createElement("div");a.className="service-review-row",a.draggable=!0,a.dataset.id=e.id;const r=e.comment||"";a.innerHTML=`
                <div class="service-review-row__drag" title="Arrastrar para reordenar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                </div>
                <div class="service-review-row__body">
                    <div class="service-review-row__head">
                        <strong>${o(e.customer_name)}</strong>
                        <span class="service-review-row__stars">${"★".repeat(e.rating||0)}${"☆".repeat(5-(e.rating||0))}</span>
                        ${e.is_verified?'<span class="users-manager-badge status" style="font-size:10px;">Verificado</span>':""}
                        <span class="users-manager-badge ${e.is_visible?"status":"status-inactive"}" style="font-size:10px;">${e.is_visible?"Visible":"Oculta"}</span>
                    </div>
                    <p class="service-review-row__comment">${o(r.length>140?r.slice(0,140)+"…":r)}</p>
                </div>
                <div class="header-right-user-manager">
                    <button type="button" class="table-users-manager-action-btn edit" title="Editar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                    </button>
                    <button type="button" class="table-users-manager-action-btn delete" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                    </button>
                </div>
            `,a.querySelector(".edit").addEventListener("click",()=>Le(e)),a.querySelector(".delete").addEventListener("click",()=>{ee(e.customer_name||"Reseña",()=>Je(e.id))}),a.addEventListener("dragstart",()=>{M=e.id,a.classList.add("is-dragging")}),a.addEventListener("dragend",()=>a.classList.remove("is-dragging")),a.addEventListener("dragover",i=>{i.preventDefault(),!(M===null||M===e.id)&&a.classList.add("drag-over")}),a.addEventListener("dragleave",()=>a.classList.remove("drag-over")),a.addEventListener("drop",i=>{if(i.preventDefault(),a.classList.remove("drag-over"),M===null||M===e.id)return;const l=x.findIndex(s=>String(s.id)===String(M)),n=x.findIndex(s=>String(s.id)===String(e.id));if(M=null,l===-1||n===-1)return;const[p]=x.splice(l,1);x.splice(n,0,p),Ee(),Xe()}),t.appendChild(a)})}}function Le(t){const e=!!t,a=_.querySelector("#leReviewFormWrap");if(!a)return;const r=e?Object.assign({},t):{customer_name:"",customer_role:"",customer_company:"",customer_city:"",customer_state:"",review_date:"",rating:5,comment:"",categories:[],is_verified:!1,is_visible:!0,business_response:"",business_response_date:""};a.innerHTML=`
            <div class="show-user-divider" style="margin:14px 0 10px;"></div>
            <p class="live-editor-col-title">${e?"Editar reseña":"Nueva reseña"}</p>
            ${u("Cliente",`<input type="text" class="users-manager-input" id="leRevName" value="${o(r.customer_name)}">`)}
            <div class="live-editor-field-row">
                ${u("Puesto (opcional)",`<input type="text" class="users-manager-input" id="leRevRole" value="${o(r.customer_role)}" placeholder="Jefe de mantenimiento">`)}
                ${u("Empresa (opcional)",`<input type="text" class="users-manager-input" id="leRevCompany" value="${o(r.customer_company)}">`)}
            </div>
            <div class="live-editor-field-row">
                ${u("Ciudad (opcional)",`<input type="text" class="users-manager-input" id="leRevCity" value="${o(r.customer_city)}">`)}
                ${u("Estado (opcional)",`<input type="text" class="users-manager-input" id="leRevState" value="${o(r.customer_state)}" placeholder="JAL">`)}
            </div>
            ${u("Fecha",`<input type="date" class="users-manager-input" id="leRevDate" value="${o(r.review_date)}">`)}
            ${u("Calificación",`<div class="service-review-stars-input" id="leRevStars"></div><input type="hidden" id="leRevRating" value="${r.rating||5}">`)}
            <div class="live-editor-field">
                <label>Comentario &middot; <span id="leRevCommentCount">${(r.comment||"").length}</span>/240</label>
                <textarea class="users-manager-input client-modal-textarea" id="leRevComment" rows="3" maxlength="240">${o(r.comment)}</textarea>
            </div>
            ${u("Categorías",`
                <div class="hs-product-chips" style="flex-wrap:wrap;">
                    ${Object.entries(b.reviewCategories||{}).map(([n,p])=>`
                        <label style="display:inline-flex;align-items:center;gap:4px;border:1px solid #d1d5db;border-radius:999px;padding:4px 10px;font-size:12.5px;cursor:pointer;font-weight:400;">
                            <input type="checkbox" class="le-rev-category" value="${o(n)}" ${(r.categories||[]).includes(n)?"checked":""}> ${o(p)}
                        </label>
                    `).join("")}
                </div>
            `)}
            <div class="live-editor-field-row">
                ${u("",`<label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;"><input type="checkbox" id="leRevVerified" ${r.is_verified?"checked":""}> Cliente verificado</label>`)}
                ${u("",`<label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;"><input type="checkbox" id="leRevVisible" ${r.is_visible?"checked":""}> Visible en público</label>`)}
            </div>
            ${u("Respuesta de Equiterm (opcional)",`<textarea class="users-manager-input client-modal-textarea" id="leRevResponse" rows="2">${o(r.business_response)}</textarea>`)}
            ${u("Fecha de respuesta (opcional)",`<input type="date" class="users-manager-input" id="leRevResponseDate" value="${o(r.business_response_date)}">`)}
            <div id="leRevErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
            <div style="display:flex;gap:8px;">
                <button type="button" id="leRevCancel" class="live-editor-btn live-editor-btn--outline" style="flex:1;">Cancelar</button>
                <button type="button" id="leRevSave" class="live-editor-btn live-editor-btn--solid" style="flex:1;">${e?"Guardar cambios":"Crear reseña"}</button>
            </div>
        `;const i=a.querySelector("#leRevStars");function l(n){a.querySelector("#leRevRating").value=n,i.innerHTML="";for(let p=1;p<=5;p++){const s=document.createElement("button");s.type="button",s.className="service-review-star"+(p<=n?" is-active":""),s.textContent="★",s.addEventListener("click",()=>l(p)),i.appendChild(s)}}l(r.rating||5),a.querySelector("#leRevComment").addEventListener("input",function(){a.querySelector("#leRevCommentCount").textContent=this.value.length}),a.querySelector("#leRevCancel").addEventListener("click",()=>{a.innerHTML=""}),a.querySelector("#leRevSave").addEventListener("click",()=>Ke(e?t.id:null,a)),a.scrollIntoView({behavior:"smooth",block:"nearest"})}async function Ke(t,e){const a=e.querySelector("#leRevErrors");a.style.display="none",a.innerHTML="";const r={customer_name:e.querySelector("#leRevName").value,customer_role:e.querySelector("#leRevRole").value||null,customer_company:e.querySelector("#leRevCompany").value||null,customer_city:e.querySelector("#leRevCity").value||null,customer_state:e.querySelector("#leRevState").value||null,review_date:e.querySelector("#leRevDate").value||null,rating:parseInt(e.querySelector("#leRevRating").value,10)||5,comment:e.querySelector("#leRevComment").value,categories:Array.from(e.querySelectorAll(".le-rev-category:checked")).map(n=>n.value),is_verified:e.querySelector("#leRevVerified").checked,is_visible:e.querySelector("#leRevVisible").checked,business_response:e.querySelector("#leRevResponse").value||null,business_response_date:e.querySelector("#leRevResponseDate").value||null},i=e.querySelector("#leRevSave");i.disabled=!0;const l=i.textContent;i.textContent="Guardando...";try{const n=t?b.reviewUpdateUrlTemplate.replace("__REVIEW_ID__",t):b.reviewsStoreUrl,p=await fetch(n,{method:t?"PUT":"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":$,Accept:"application/json"},body:JSON.stringify(r)}),s=await p.json();if(p.ok){if(t){const h=x.findIndex(m=>String(m.id)===String(t));h!==-1&&(x[h]=Object.assign({},x[h],s.review))}else x.push(s.review);se(),window.showCenterToast&&showCenterToast(t?"Reseña actualizada.":"Reseña creada."),d()}else if(p.status===422){const h=s.errors||{};a.innerHTML=Object.values(h).flat().map(m=>`<p>${m}</p>`).join(""),a.style.display="block",i.disabled=!1,i.textContent=l}else throw new Error("save-review-failed")}catch{a.innerHTML="<p>No se pudo guardar. Intenta de nuevo.</p>",a.style.display="block",i.disabled=!1,i.textContent=l}}async function Je(t){try{if((await fetch(b.reviewDestroyUrlTemplate.replace("__REVIEW_ID__",t),{method:"DELETE",headers:{"X-CSRF-TOKEN":$,Accept:"application/json"}})).ok){const a=x.findIndex(r=>String(r.id)===String(t));a!==-1&&x.splice(a,1),se(),window.showCenterToast&&showCenterToast("Reseña eliminada."),d()}else window.showCenterToast&&showCenterToast("No se pudo eliminar la reseña.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al eliminar la reseña.","error")}}async function Xe(){const t=x.map(e=>e.id);try{(await fetch(b.reviewsReorderUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":$,Accept:"application/json"},body:JSON.stringify({order:t})})).ok?window.showCenterToast&&showCenterToast("Orden de reseñas actualizado."):window.showCenterToast&&showCenterToast("No se pudo guardar el nuevo orden.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al guardar el orden.","error")}}function B(){if(C==="general"){xe();return}if(C==="gallery"){Oe();return}if(C==="reviews"){se();return}const t=we(k);if(!t){_.innerHTML='<div class="live-editor-panel-empty">Selecciona un bloque de la izquierda para editarlo aquí, o "Información general" para nombre, slug, precio y SEO.</div>';return}const e=j[t.type]||j.default;if(_.innerHTML=`
            <div class="live-editor-panel-header">
                <span class="live-editor-panel-header-info">
                    <span class="live-editor-block-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${e}</svg>
                    </span>
                    <span>
                        <strong>${o(t.title||H[t.type]||t.type)}</strong>
                        <small>Editando bloque</small>
                    </span>
                </span>
                <button type="button" class="live-editor-panel-close" id="lePanelClose" title="Cerrar">&times;</button>
            </div>
            ${u("Título (opcional, puedes usar {servicio})",`<input type="text" class="users-manager-input" id="leTitle" value="${o(t.title)}" placeholder="Ej: Beneficios del servicio">`)}
            ${$e.includes(t.type)?'<div id="leTitleStyle"></div>':""}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div id="leTypeFields"></div>
        `,_.querySelector("#lePanelClose").addEventListener("click",()=>{k=null,L(),B()}),_.querySelector("#leTitle").addEventListener("input",a=>{t.title=a.target.value,c(),L(),d()}),$e.includes(t.type)){const a=t.config=t.config||{};a.title_style=a.title_style||{},P(_.querySelector("#leTitleStyle"),a.title_style,{tagChoices:["h2","h3"]})}Qe(_.querySelector("#leTypeFields"),t)}const $e=["benefits_grid","process_steps","content_tabs","gallery_carousel","faq"];function Qe(t,e){const a=e.config=e.config||{};switch(e.type){case"banner":W(t,a,"");break;case"dual_banner":t.innerHTML='<p class="hs-config-subtitle">Banner Izquierdo</p><div id="leDbLeft"></div><p class="hs-config-subtitle">Banner Derecho</p><div id="leDbRight"></div>',a.left=a.left||{image_url:"",link_url:"",alt:""},a.right=a.right||{image_url:"",link_url:"",alt:""},W(t.querySelector("#leDbLeft"),a.left,"Left"),W(t.querySelector("#leDbRight"),a.right,"Right");break;case"product_carousel":ke(t,a,"");break;case"product_carousel_banner":t.innerHTML='<p class="hs-config-subtitle">Banner</p><div id="lePcbBanner"></div><p class="hs-config-subtitle">Productos del carrusel</p><div id="lePcbCarousel"></div>',W(t.querySelector("#lePcbBanner"),{image_url:a.banner_image_url,link_url:a.banner_link_url,alt:a.banner_alt},"PcbBanner",(r,i)=>{r==="image_url"&&(a.banner_image_url=i),r==="link_url"&&(a.banner_link_url=i),r==="alt"&&(a.banner_alt=i)}),ke(t.querySelector("#lePcbCarousel"),a,"Pcb");break;case"category_grid":Ze(t,a);break;case"brand_carousel":t.innerHTML='<p class="hs-config-note">Este bloque muestra automáticamente todas las marcas activas. No requiere configuración adicional.</p>';break;case"html_block":et(t,a);break;case"faq":tt(t,a);break;case"rich_header":at(t,a);break;case"content_tabs":oe(t,a);break;case"benefits_grid":ce(t,a);break;case"process_steps":de(t,a);break;case"gallery_carousel":rt(t,a);break;case"rating_reviews":it(t,a);break;case"cta_final":nt(t,a);break;case"button":ie(t,a);break;case"table_block":T(t,a);break;default:t.innerHTML='<p class="hs-config-note">Tipo de bloque desconocido.</p>'}}function W(t,e,a,r){const i="leBanner"+a+"Image",l="leBanner"+a+"Link",n="leBanner"+a+"Alt";t.innerHTML=`
            ${u("URL de Imagen",`
                <div class="img-picker-field">
                    <input type="text" class="users-manager-input" id="${i}" value="${o(e.image_url)}" placeholder="https://...">
                    <button type="button" class="img-picker-trigger-btn" data-target="${i}">Seleccionar</button>
                </div>
            `)}
            <div class="live-editor-field-row">
                ${u("URL de Enlace",`<input type="text" class="users-manager-input" id="${l}" value="${o(e.link_url)}" placeholder="/servicio/otro-servicio">`)}
                ${u("Texto Alternativo",`<input type="text" class="users-manager-input" id="${n}" value="${o(e.alt)}">`)}
            </div>
        `;const p=g=>r?r("image_url",g):e.image_url=g,s=g=>r?r("link_url",g):e.link_url=g,h=g=>r?r("alt",g):e.alt=g,m=t.querySelector("#"+i);m.addEventListener("input",()=>{p(m.value),c(),d()}),t.querySelector("#"+l).addEventListener("input",g=>{s(g.target.value),c(),d()}),t.querySelector("#"+n).addEventListener("input",g=>{h(g.target.value),c(),d()}),t.querySelector(".img-picker-trigger-btn").addEventListener("click",()=>{typeof window.openImagePicker=="function"&&window.openImagePicker(i)})}function ke(t,e,a){const r="leSource"+a,i="leLimit"+a,l="leCategory"+a,n="leBrand"+a,p="leCollection"+a,s="leManualWrap"+a,h=Object.keys(pe).map(y=>`<option value="${y}" ${e.source===y?"selected":""}>${pe[y]}</option>`).join("");t.innerHTML=`
            <div class="live-editor-field-row">
                ${u("Origen de Productos",`<select class="users-manager-select" id="${r}">${h}</select>`)}
                ${u("Límite de Productos",`<input type="number" class="users-manager-input" id="${i}" min="1" max="50" value="${e.limit??10}">`)}
            </div>
            <div id="leSourceFields${a}"></div>
        `,t.querySelector("#"+r).addEventListener("change",y=>{e.source=y.target.value,c(),m(),d()}),t.querySelector("#"+i).addEventListener("input",y=>{e.limit=parseInt(y.target.value,10)||10,c(),d()});function m(){const y=t.querySelector("#leSourceFields"+a);e.source==="category"?(y.innerHTML=u("Categoría",`<select class="users-manager-select" id="${l}">
                    <option value="">Selecciona una categoría</option>
                    ${(b.categories||[]).map(g=>`<option value="${g.id}" ${String(e.category_id)===String(g.id)?"selected":""}>${o(g.name)}</option>`).join("")}
                </select>`),y.querySelector("#"+l).addEventListener("change",g=>{e.category_id=g.target.value||null,c(),d()})):e.source==="brand"?(y.innerHTML=u("Marca",`<select class="users-manager-select" id="${n}">
                    <option value="">Selecciona una marca</option>
                    ${(b.brands||[]).map(g=>`<option value="${g.id}" ${String(e.brand_id)===String(g.id)?"selected":""}>${o(g.name)}</option>`).join("")}
                </select>`),y.querySelector("#"+n).addEventListener("change",g=>{e.brand_id=g.target.value||null,c(),d()})):e.source==="collection"?(y.innerHTML=u("Colección",`<select class="users-manager-select" id="${p}">
                    <option value="">Selecciona una colección</option>
                    ${(b.collections||[]).map(g=>`<option value="${g.id}" ${String(e.collection_id)===String(g.id)?"selected":""}>${o(g.name)}</option>`).join("")}
                </select>`),y.querySelector("#"+p).addEventListener("change",g=>{e.collection_id=g.target.value||null,c(),d()})):e.source==="manual"?(y.innerHTML=`<div class="live-editor-field"><label>Productos</label><div id="${s}"></div></div>`,Ye(y.querySelector("#"+s),e.product_ids||[],g=>{e.product_ids=g,c(),d()})):y.innerHTML=""}m()}function Ye(t,e,a){t.innerHTML=`
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
        `;const r=t.querySelector(".hs-product-search__input"),i=t.querySelector(".hs-product-search__dropdown"),l=t.querySelector(".hs-product-search__list"),n=t.querySelector(".hs-product-search__empty"),p=t.querySelector(".hs-product-chips");let s=[],h=null;function m(){p.innerHTML="",s.forEach(w=>{const f=document.createElement("span");f.className="hs-product-chip",f.innerHTML=`<span>${o(w.name)}</span><small>${o(w.sku)}</small><button type="button" aria-label="Quitar">&times;</button>`,f.querySelector("button").addEventListener("click",()=>{s=s.filter(E=>E.id!==w.id),m(),a(s.map(E=>E.id))}),p.appendChild(f)})}function y(){i.style.display="none",l.innerHTML=""}function g(w){l.innerHTML="";const f=w.filter(E=>!s.some(q=>q.id===E.id));if(!f.length){n.style.display="block",l.style.display="none";return}n.style.display="none",l.style.display="block",f.forEach(E=>{const q=document.createElement("li");q.className="hs-product-search__item",q.innerHTML=`<span>${o(E.name)}</span><small>SKU: ${o(E.sku)}</small>`,q.addEventListener("click",()=>{s.push({id:E.id,name:E.name,sku:E.sku}),m(),a(s.map(ue=>ue.id)),r.value="",y()}),l.appendChild(q)})}async function I(w){try{const f=new URL(b.productsSearchUrl,window.location.origin);Object.entries(w).forEach(([q,ue])=>f.searchParams.set(q,ue));const E=await fetch(f.toString(),{headers:{Accept:"application/json"}});return E.ok?await E.json():[]}catch{return[]}}r.addEventListener("input",function(){const w=this.value.trim();if(clearTimeout(h),w.length<2){y();return}h=setTimeout(async()=>{const f=await I({q:w});i.style.display="block",g(f)},300)}),document.addEventListener("click",w=>{t.contains(w.target)||y()}),e&&e.length&&I({ids:e.join(",")}).then(w=>{s=w.map(f=>({id:f.id,name:f.name,sku:f.sku})),m()})}function Ze(t,e){t.innerHTML=u("Categorías a mostrar (vacío = todas las principales activas)",`
            <select class="users-manager-select" id="leCategoryIds" multiple size="6">
                ${(b.categories||[]).map(a=>`<option value="${a.id}" ${(e.category_ids||[]).map(String).includes(String(a.id))?"selected":""}>${o(a.name)}</option>`).join("")}
            </select>
        `),t.querySelector("#leCategoryIds").addEventListener("change",a=>{e.category_ids=Array.from(a.target.selectedOptions).map(r=>parseInt(r.value,10)),c(),d()})}function et(t,e){t.innerHTML=u("Contenido HTML",`<textarea class="users-manager-input client-modal-textarea" id="leHtml" rows="8" placeholder="<div>...</div>">${o(e.html)}</textarea>`),t.querySelector("#leHtml").addEventListener("input",a=>{e.html=a.target.value,c(),d()})}function tt(t,e){t.innerHTML=`
            ${u("Texto descriptivo (opcional)",`<textarea class="users-manager-input client-modal-textarea" id="leFaqDescription" rows="2">${o(e.description)}</textarea>`)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div class="live-editor-field">
                <label>Preguntas frecuentes</label>
                <p class="hs-config-note" style="margin:0 0 8px;">Alimentan el <code>FAQPage</code> de Google y el acordeón de este bloque. Se comparten con toda la página, aunque haya más de un bloque "Preguntas Frecuentes".</p>
                <div id="leFaqRows" class="hs-faq-items"></div>
                <button type="button" class="button-secondary size-adjustment" id="leFaqAdd" style="margin-top:10px;">+ Agregar pregunta</button>
            </div>
            <div id="leFaqErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
            <button type="button" id="leFaqSaveBtn" class="live-editor-btn live-editor-btn--solid live-editor-btn--block">Guardar preguntas frecuentes</button>
        `,t.querySelector("#leFaqDescription").addEventListener("input",a=>{e.description=a.target.value,c(),d()}),t.querySelector("#leFaqAdd").addEventListener("click",()=>{v.faqs=v.faqs||[],v.faqs.push({question:"",answer:""}),c(),le(t.querySelector("#leFaqRows"))}),le(t.querySelector("#leFaqRows")),t.querySelector("#leFaqSaveBtn").addEventListener("click",()=>Se({btnId:"leFaqSaveBtn",errorsBoxId:"leFaqErrors"}))}function at(t,e){t.innerHTML=`
            ${u("Badges cortos (separados por ·, máx. 3)",`<input type="text" class="users-manager-input" id="leRhBadges" value="${o((e.badges||[]).join(" · "))}" placeholder="Garantía 6 meses · Reporte técnico incluido">`)}
            ${u("Líneas de meta (una por línea)",`<textarea class="users-manager-input client-modal-textarea" id="leRhMetaLines" rows="2">${o((e.meta_lines||[]).join(`
`))}</textarea>`)}
            ${u("Texto del botón",`<input type="text" class="users-manager-input" id="leRhWhatsapp" value="${o(e.whatsapp_text||"Cotizar por WhatsApp")}">`)}
            <p class="hs-config-note">El CTA siempre abre WhatsApp; no existe botón de llamada.</p>
            ${u("Precio mostrado (opcional)",`<input type="text" class="users-manager-input" id="leRhPriceLabel" value="${o(e.price_label)}" placeholder="$8,500 MXN + IVA">`)}
            ${u("Imágenes de fondo (galería del servicio)",`<select class="users-manager-select" id="leRhBgImages" multiple size="4">${z(e.background_image_ids,b.images)}</select>`)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <p class="hs-config-note">El título y la descripción corta se editan en "Información general" — aquí solo se controla su estilo.</p>
            <p class="live-editor-col-title" style="margin:6px 0 0;">Estilo del título (H1, fijo)</p>
            <div id="leRhTitleStyle"></div>
            <p class="live-editor-col-title" style="margin:14px 0 0;">Estilo de la descripción corta</p>
            <div id="leRhSubtitleStyle"></div>
        `,t.querySelector("#leRhBadges").addEventListener("input",a=>{e.badges=a.target.value.split("·").map(r=>r.trim()).filter(Boolean).slice(0,3),c(),d()}),t.querySelector("#leRhMetaLines").addEventListener("input",a=>{e.meta_lines=a.target.value.split(`
`).map(r=>r.trim()).filter(Boolean),c(),d()}),t.querySelector("#leRhWhatsapp").addEventListener("input",a=>{e.whatsapp_text=a.target.value,c(),d()}),t.querySelector("#leRhPriceLabel").addEventListener("input",a=>{e.price_label=a.target.value,c(),d()}),t.querySelector("#leRhBgImages").addEventListener("change",a=>{e.background_image_ids=Array.from(a.target.selectedOptions).map(r=>parseInt(r.value,10)),c(),d()}),e.title_style=e.title_style||{},e.subtitle_style=e.subtitle_style||{},P(t.querySelector("#leRhTitleStyle"),e.title_style,{}),P(t.querySelector("#leRhSubtitleStyle"),e.subtitle_style,{})}function oe(t,e){e.tabs=e.tabs||[];let a='<p class="hs-config-note">Cada pestaña se muestra como pestaña horizontal en público.</p><div id="leCtRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="leCtAdd" style="margin-top:10px;">+ Agregar pestaña</button>';t.innerHTML=a;const r=t.querySelector("#leCtRows");e.tabs.forEach((i,l)=>{const n=document.createElement("div");n.className="hs-repeat-row",n.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${l+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-label" placeholder="Título de pestaña" value="${o(i.label)}">
                <input type="text" class="users-manager-input le-subtitle" placeholder="Subtítulo (opcional)" style="margin-top:6px;" value="${o(i.subtitle)}">
                <textarea class="users-manager-input client-modal-textarea le-body" rows="2" placeholder="Párrafo" style="margin-top:6px;">${o(i.body)}</textarea>
                <textarea class="users-manager-input client-modal-textarea le-bullets" rows="2" placeholder="Viñetas, una por línea" style="margin-top:6px;">${o((i.bullets||[]).join(`
`))}</textarea>
                <select class="users-manager-select le-image" style="margin-top:6px;">
                    <option value="">Sin imagen</option>
                    ${z(i.image_id?[i.image_id]:[],b.images)}
                </select>
                <div class="le-tab-style" style="margin-top:6px;"></div>
            `,n.querySelector(".le-label").addEventListener("input",p=>{i.label=p.target.value,c(),L(),d()}),n.querySelector(".le-subtitle").addEventListener("input",p=>{i.subtitle=p.target.value,c(),d()}),n.querySelector(".le-body").addEventListener("input",p=>{i.body=p.target.value,c(),d()}),n.querySelector(".le-bullets").addEventListener("input",p=>{i.bullets=p.target.value.split(`
`).map(s=>s.trim()).filter(Boolean),c(),d()}),n.querySelector(".le-image").addEventListener("change",p=>{i.image_id=p.target.value||null,c(),d()}),n.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.tabs.splice(l,1),c(),oe(t,e),d()}),i.style=i.style||{},P(n.querySelector(".le-tab-style"),i.style,{}),r.appendChild(n)}),t.querySelector("#leCtAdd").addEventListener("click",()=>{e.tabs.push({label:"",subtitle:"",body:"",bullets:[],image_id:null}),c(),oe(t,e),d()})}function ce(t,e){e.items=e.items||[],e.layout=e.layout==="vertical"?"vertical":"horizontal",t.innerHTML=`
            ${u("Diseño de las tarjetas",`
                <select class="users-manager-select" id="leBgLayout">
                    <option value="horizontal" ${e.layout==="horizontal"?"selected":""}>Horizontal (en fila)</option>
                    <option value="vertical" ${e.layout==="vertical"?"selected":""}>Vertical (apiladas)</option>
                </select>
            `)}
            <p class="hs-config-note">Tarjetas de cifra + título + descripción.</p><div id="leBgRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="leBgAdd" style="margin-top:10px;">+ Agregar beneficio</button>
        `,t.querySelector("#leBgLayout").addEventListener("change",r=>{e.layout=r.target.value,c(),d()});const a=t.querySelector("#leBgRows");e.items.forEach((r,i)=>{const l=document.createElement("div");l.className="hs-repeat-row",l.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${i+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-figure" placeholder="Cifra (ej. -12%)" value="${o(r.figure)}">
                <input type="text" class="users-manager-input le-title" placeholder="Título" style="margin-top:6px;" value="${o(r.title)}">
                <textarea class="users-manager-input client-modal-textarea le-description" rows="2" placeholder="Descripción corta" style="margin-top:6px;">${o(r.description)}</textarea>
            `,l.querySelector(".le-figure").addEventListener("input",n=>{r.figure=n.target.value,c(),d()}),l.querySelector(".le-title").addEventListener("input",n=>{r.title=n.target.value,c(),L(),d()}),l.querySelector(".le-description").addEventListener("input",n=>{r.description=n.target.value,c(),d()}),l.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.items.splice(i,1),c(),ce(t,e),d()}),a.appendChild(l)}),t.querySelector("#leBgAdd").addEventListener("click",()=>{e.items.push({figure:"",title:"",description:""}),c(),ce(t,e),d()})}function de(t,e){e.steps=e.steps||[],t.innerHTML='<p class="hs-config-note">Pasos numerados del proceso.</p><div id="lePsRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="lePsAdd" style="margin-top:10px;">+ Agregar paso</button>';const a=t.querySelector("#lePsRows");e.steps.forEach((r,i)=>{const l=document.createElement("div");l.className="hs-repeat-row",l.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${i+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-title" placeholder="Título del paso" value="${o(r.title)}">
                <textarea class="users-manager-input client-modal-textarea le-description" rows="2" placeholder="Descripción" style="margin-top:6px;">${o(r.description)}</textarea>
                <input type="text" class="users-manager-input le-duration" placeholder="Duración (ej. 1 h)" style="margin-top:6px;" value="${o(r.duration)}">
            `,l.querySelector(".le-title").addEventListener("input",n=>{r.title=n.target.value,c(),L(),d()}),l.querySelector(".le-description").addEventListener("input",n=>{r.description=n.target.value,c(),d()}),l.querySelector(".le-duration").addEventListener("input",n=>{r.duration=n.target.value,c(),d()}),l.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.steps.splice(i,1),c(),de(t,e),d()}),a.appendChild(l)}),t.querySelector("#lePsAdd").addEventListener("click",()=>{e.steps.push({title:"",description:"",duration:""}),c(),de(t,e),d()})}function rt(t,e){t.innerHTML=u("Imágenes a mostrar (vacío = toda la galería)",`
            <select class="users-manager-select" id="leGcImages" multiple size="6">${z(e.image_ids,b.images)}</select>
            ${!b.images||!b.images.length?'<p class="hs-config-note" style="margin-top:6px;">Este servicio todavía no tiene imágenes en su galería.</p>':""}
        `),t.querySelector("#leGcImages").addEventListener("change",a=>{e.image_ids=Array.from(a.target.selectedOptions).map(r=>parseInt(r.value,10)),c(),d()})}function it(t,e){t.innerHTML=`
            ${u("Texto descriptivo (opcional)",`<textarea class="users-manager-input client-modal-textarea" id="leRrDescription" rows="2">${o(e.description)}</textarea>`)}
            ${u('Reseñas visibles antes de "Ver más"',`<input type="number" class="users-manager-input" id="leRrPerPage" min="1" max="20" value="${e.reviews_per_page??3}">`)}
            <p class="hs-config-note">Las reseñas se capturan en el panel <strong>Reseñas</strong> del sidebar, y las estadísticas de "Promedio mostrado" en <strong>Información general</strong>. Esta sección solo define dónde aparecen y su texto descriptivo.</p>
        `,t.querySelector("#leRrDescription").addEventListener("input",a=>{e.description=a.target.value,c(),d()}),t.querySelector("#leRrPerPage").addEventListener("input",a=>{e.reviews_per_page=parseInt(a.target.value,10)||3,c(),d()})}function nt(t,e){t.innerHTML=`
            ${u("Título",`<input type="text" class="users-manager-input" id="leCtaHeadline" value="${o(e.headline)}" placeholder="¿Listo para cotizar tu servicio?">`)}
            <div id="leCtaHeadlineStyle"></div>
            ${u("Texto de apoyo",`<textarea class="users-manager-input client-modal-textarea" id="leCtaSubtext" rows="2">${o(e.subtext)}</textarea>`)}
            <div id="leCtaSubtextStyle"></div>
            <div class="show-user-divider" style="margin:10px 0;"></div>
            ${u("Texto del botón",`<input type="text" class="users-manager-input" id="leCtaWhatsapp" value="${o(e.whatsapp_text||"Cotizar por WhatsApp")}">`)}
            <p class="hs-config-note">Deja este campo vacío para ocultar el botón de WhatsApp.</p>
            ${u("Imagen de fondo (opcional)",`<select class="users-manager-select" id="leCtaBg"><option value="">Sin imagen</option>${z(e.background_image_id?[e.background_image_id]:[],b.images)}</select>`)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <p class="live-editor-col-title">Botón secundario (opcional)</p>
            <p class="hs-config-note">Se muestra junto al de WhatsApp (o solo, si dejaste ese campo vacío) — útil para un enlace que no sea WhatsApp.</p>
            <div id="leCtaSecondaryBtn"></div>
        `,t.querySelector("#leCtaHeadline").addEventListener("input",a=>{e.headline=a.target.value,c(),d()}),t.querySelector("#leCtaSubtext").addEventListener("input",a=>{e.subtext=a.target.value,c(),d()}),t.querySelector("#leCtaWhatsapp").addEventListener("input",a=>{e.whatsapp_text=a.target.value,c(),d()}),t.querySelector("#leCtaBg").addEventListener("change",a=>{e.background_image_id=a.target.value||null,c(),d()}),e.headline_style=e.headline_style||{},e.subtext_style=e.subtext_style||{},P(t.querySelector("#leCtaHeadlineStyle"),e.headline_style,{tagChoices:["h2","h3"]}),P(t.querySelector("#leCtaSubtextStyle"),e.subtext_style,{}),e.secondary_button=e.secondary_button||{text:"",url:"",style:"outline",color:"#ff6213"},re(t.querySelector("#leCtaSecondaryBtn"),e.secondary_button,{alignField:!1})}let Ce=null;function d(){clearTimeout(Ce),Ce=setTimeout(qe,400)}async function qe(){try{const e=await(await fetch(b.previewUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":$,Accept:"application/json"},body:JSON.stringify({sections:S.filter(a=>a.is_active).map((a,r)=>({...a,sort_order:r}))})})).json();D.srcdoc=e.html??""}catch(t){console.error("Error generando el preview:",t)}}D.addEventListener("load",()=>{try{const t=D.contentDocument;if(!t)return;const e=we(k);if(e&&e.id){const a=t.createElement("style");a.textContent=`[data-section-id="${e.id}"] { outline: 3px solid #ff6213; outline-offset: 2px; cursor: pointer; }`,t.head.appendChild(a)}t.body.addEventListener("click",a=>{const r=a.target.closest("[data-section-id]");if(!r)return;const i=r.getAttribute("data-section-id"),l=S.find(n=>String(n.id)===String(i));l&&(a.preventDefault(),ne(l._uid))},!0)}catch{}});const Te=document.getElementById("leViewportCaption");function lt(){Te&&(Te.textContent=K==="mobile"?"Móvil · 375px":"Escritorio · 1440px")}me.addEventListener("click",t=>{const e=t.target.closest("button[data-viewport]");e&&(K=e.dataset.viewport,me.querySelectorAll("button").forEach(a=>a.classList.toggle("is-active",a===e)),D.classList.toggle("is-mobile",K==="mobile"),lt())}),G.addEventListener("click",async()=>{G.disabled=!0;const t=G.textContent;G.textContent="Guardando...",F.textContent="Guardando…",F.className="live-editor-status is-saving";try{if(!(await fetch(b.saveUrl,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":$,Accept:"application/json"},body:JSON.stringify({sections:S.map((a,r)=>({...a,sort_order:r}))})})).ok)throw new Error("save request failed");window.location.reload()}catch(e){console.error("Error guardando la página:",e),alert("No se pudieron guardar los cambios. Intenta de nuevo."),G.disabled=!1,G.textContent=t,c()}}),S.length?(L(),B()):fe(),qe()})();
