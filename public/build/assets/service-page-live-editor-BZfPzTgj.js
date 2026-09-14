(function(){const g=window.__LIVE_EDITOR__;if(!g)return;const E=document.querySelector('meta[name="csrf-token"]').content,B={banner:"Banner",dual_banner:"Banner Doble",product_carousel:"Carrusel de Productos",product_carousel_banner:"Carrusel con Banner",category_grid:"Grid de Categorías",brand_carousel:"Carrusel de Marcas",html_block:"Bloque HTML",faq:"Preguntas Frecuentes",rich_header:"Encabezado enriquecido",content_tabs:"Descripción por secciones",benefits_grid:"Beneficios / características",process_steps:"Proceso / cómo funciona",gallery_carousel:"Galería / carrusel",rating_reviews:"Rating y reseñas",cta_final:"CTA final",button:"Botón"},A={banner:'<rect width="18" height="12" x="3" y="6" rx="2"/><path d="M3 10h18"/>',dual_banner:'<rect width="8" height="14" x="3" y="5" rx="1.5"/><rect width="8" height="14" x="13" y="5" rx="1.5"/>',product_carousel:'<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',product_carousel_banner:'<rect width="18" height="12" x="3" y="6" rx="2"/><circle cx="9" cy="12" r="2"/>',category_grid:'<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/>',brand_carousel:'<path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/>',html_block:'<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',faq:'<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" x2="12.01" y1="17" y2="17"/>',rich_header:'<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="2" x2="22" y1="9" y2="9"/>',content_tabs:'<path d="M21 15V6"/><path d="M18.5 18a2.5 2.5 0 1 0 0-5H8a2 2 0 1 0 0 4h10"/><path d="M3 3v18"/><path d="M14 6H3"/>',benefits_grid:'<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',process_steps:'<path d="M4 17V9a2 2 0 0 1 2-2h2"/><path d="m18 8 4 4-4 4"/><path d="M4 21v-2a2 2 0 0 1 2-2h2"/><path d="M14 3h6v6"/>',gallery_carousel:'<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',rating_reviews:'<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',cta_final:'<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',button:'<rect width="18" height="7" x="3" y="8.5" rx="3.5"/>',default:'<rect width="18" height="18" x="3" y="3" rx="2"/>'},se={featured:"Destacados",new:"Nuevos",recommended:"Recomendados",category:"Por Categoría",brand:"Por Marca",collection:"Por Colección",manual:"Selección Manual"};function Ce(t){switch(t){case"banner":return{image_url:"",link_url:"",alt:""};case"dual_banner":return{left:{image_url:"",link_url:"",alt:""},right:{image_url:"",link_url:"",alt:""}};case"product_carousel":return{source:"featured",category_id:null,brand_id:null,collection_id:null,product_ids:[],limit:10};case"product_carousel_banner":return{banner_image_url:"",banner_link_url:"",banner_alt:"",source:"featured",category_id:null,brand_id:null,collection_id:null,product_ids:[],limit:10};case"category_grid":return{category_ids:[]};case"brand_carousel":return{};case"html_block":return{html:""};case"faq":return{description:""};case"rich_header":return{badges:[],whatsapp_text:"Cotizar por WhatsApp",meta_lines:[],background_image_ids:[],price_label:""};case"content_tabs":return{tabs:[]};case"benefits_grid":return{items:[]};case"process_steps":return{steps:[]};case"gallery_carousel":return{image_ids:[]};case"rating_reviews":return{description:"",reviews_per_page:3};case"cta_final":return{headline:"",subtext:"",whatsapp_text:"Cotizar por WhatsApp",background_image_id:null,secondary_button:{text:"",url:"",style:"outline",color:"#ff6213"}};case"button":return{text:"Cotizar ahora",url:"",style:"solid",color:"#ff6213",align:"center"};default:return{}}}let z=0;const oe=()=>"u"+ ++z,x=(g.sections||[]).map(t=>({_uid:oe(),id:t.id??null,type:t.type,title:t.title??"",config:t.config&&typeof t.config=="object"?t.config:{},is_active:t.is_active!==!1}));let C=null,k="empty",ce=!1,V="desktop";const m=Object.assign({name:"",slug:"",short_description:"",price:"",currency:"MXN",show_price:!0,seo_title:"",seo_description:"",canonical_url:"",is_active:!1,faqs:[],rating_average_displayed:"",rating_total_rated:"",rating_recommend_percent:"",rating_punctuality_average:"",rating_recurring_clients:"",rating_since_year:"",rating_distribution:{}},g.general||{}),w=(g.reviews||[]).map(t=>Object.assign({},t)),W=document.getElementById("leBlocksList"),ke=document.getElementById("leAddBlockType"),qe=document.getElementById("leAddBlockBtn"),v=document.getElementById("leEditPanel"),H=document.getElementById("leIframe"),j=document.getElementById("leDirtyIndicator"),M=document.getElementById("leSaveBtn"),de=document.getElementById("leViewportToggle"),K=document.getElementById("leGeneralInfoBtn"),J=document.getElementById("leGalleryBtn"),X=document.getElementById("leReviewsBtn"),ue=document.querySelector(".live-editor-heading-row__left h1"),D=document.getElementById("leDeleteModal"),Te=document.getElementById("leDeleteModalTitle"),Re=document.getElementById("leDeleteModalAvatar"),Ge=document.getElementById("leDeleteModalCancel"),Ie=document.getElementById("leDeleteModalConfirm");let Q=null;function Y(t,e){Q=e,Te.textContent=t,Re.textContent=(t||"?").charAt(0).toUpperCase(),D.classList.add("active")}function Z(){Q=null,D.classList.remove("active")}Ge.addEventListener("click",Z),D.addEventListener("click",t=>{t.target===D&&Z()}),Ie.addEventListener("click",()=>{const t=Q;Z(),typeof t=="function"&&t()});function s(t){return String(t??"").replace(/&/g,"&amp;").replace(/"/g,"&quot;").replace(/</g,"&lt;").replace(/>/g,"&gt;")}function Me(t){return String(t??"").toLowerCase().normalize("NFD").replace(/[^\x00-\x7F]/g,"").replace(/[^a-z0-9\s-]/g,"").trim().replace(/\s+/g,"-")}const Pe=["#000000","#141516","#374151","#4b5563","#6b7280","#9ca3af","#d1d5db","#f3f4f6","#ffffff","#ef4444","#f97316","#ff6213","#f59e0b","#eab308","#84cc16","#22c55e","#10b981","#14b8a6","#06b6d4","#0ea5e9","#3b82f6","#6366f1","#8b5cf6","#a855f7","#d946ef","#ec4899","#f43f5e","#7c2d12","#78350f","#365314","#134e4a","#1e3a8a","#4c1d95"],pe="emb-custom-colors";function Be(){try{const t=window.localStorage.getItem(pe),e=t?JSON.parse(t):[];return Array.isArray(e)?e:[]}catch{return[]}}function ve(t){try{window.localStorage.setItem(pe,JSON.stringify(t))}catch{}}function ge(t){return/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(t)}function me(t,e,r){let a=Be();function i(l,p){return`<button type="button" class="le-color-swatch ${e&&e.toLowerCase()===l.toLowerCase()?"is-active":""}" data-hex="${l}" title="${l}" style="background:${l}">
                ${p?'<span class="le-color-swatch-remove" data-remove="'+l+'" title="Quitar de mis colores">&times;</span>':""}
            </button>`}function n(){t.innerHTML=`
                <div class="le-color-swatches">${Pe.map(o=>i(o,!1)).join("")}</div>
                ${a.length?`
                    <div class="le-color-custom-label">Mis colores</div>
                    <div class="le-color-swatches">${a.map(o=>i(o,!0)).join("")}</div>
                `:""}
                <div class="le-color-custom-row">
                    <input type="color" class="le-color-native" value="${ge(e)?e:"#ff6213"}">
                    <input type="text" class="users-manager-input le-color-hex" placeholder="#ff6213" value="${s(e||"")}">
                    <button type="button" class="live-editor-btn live-editor-btn--outline le-color-save">Guardar</button>
                </div>
            `,t.querySelectorAll(".le-color-swatch").forEach(o=>{o.addEventListener("click",h=>{h.target.closest(".le-color-swatch-remove")||(r(o.dataset.hex),n())})}),t.querySelectorAll(".le-color-swatch-remove").forEach(o=>{o.addEventListener("click",h=>{h.stopPropagation();const b=o.dataset.remove;a=a.filter(f=>f!==b),ve(a),n()})});const l=t.querySelector(".le-color-native"),p=t.querySelector(".le-color-hex");l.addEventListener("input",()=>{p.value=l.value}),t.querySelector(".le-color-save").addEventListener("click",()=>{let o=p.value.trim();o&&(o.startsWith("#")||(o="#"+o),ge(o)&&(a.includes(o)||(a=[...a,o],ve(a)),r(o),n()))})}n()}function P(t,e,r){r=r||{};const a=r.tagChoices||null,i=r.onChange||(()=>{}),n="leStyle"+ ++z;function l(){d(),i(),u()}const p=a?c("Etiqueta de encabezado",`
            <select class="users-manager-select" id="${n}Tag">
                ${a.map(o=>`<option value="${o}" ${(e.heading_tag||a[0])===o?"selected":""}>${o.toUpperCase()}</option>`).join("")}
            </select>
        `):"";t.innerHTML=`
            <p class="live-editor-col-title" style="margin:14px 0 8px;">Estilo del texto</p>
            ${p}
            <div class="live-editor-field-row">
                ${c("Alineación",`
                    <div class="le-align-toggle" id="${n}Align">
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
                ${c("Tamaño (px)",`<input type="number" class="users-manager-input" id="${n}Size" min="10" max="72" placeholder="Auto" value="${s(e.font_size||"")}">`)}
            </div>
            ${c("Familia tipográfica",`
                <select class="users-manager-select" id="${n}Family">
                    <option value="" ${e.font_family?"":"selected"}>Predeterminada (Inter)</option>
                    <option value="Inter Tight" ${e.font_family==="Inter Tight"?"selected":""}>Inter Tight</option>
                </select>
            `)}
            ${c("Color de texto",`<div id="${n}Color"></div>`)}
        `,a&&t.querySelector("#"+n+"Tag").addEventListener("change",o=>{e.heading_tag=o.target.value,l()}),t.querySelector("#"+n+"Align").addEventListener("click",o=>{const h=o.target.closest("button[data-align]");h&&(e.text_align=h.dataset.align,t.querySelectorAll("#"+n+"Align button").forEach(b=>b.classList.toggle("is-active",b===h)),l())}),t.querySelector("#"+n+"Size").addEventListener("input",o=>{const h=parseInt(o.target.value,10);e.font_size=Number.isFinite(h)?h:null,l()}),t.querySelector("#"+n+"Family").addEventListener("change",o=>{e.font_family=o.target.value||null,l()}),me(t.querySelector("#"+n+"Color"),e.text_color,o=>{e.text_color=o,l()})}function ye(t,e,r){r=r||{};const a=r.alignField!==!1,i="leBtn"+ ++z;t.innerHTML=`
            ${c("Texto del botón",`<input type="text" class="users-manager-input" id="${i}Text" value="${s(e.text)}" placeholder="Cotizar ahora">`)}
            ${c("Enlace",`
                <div style="display:flex;gap:8px;">
                    <input type="text" class="users-manager-input" id="${i}Url" value="${s(e.url)}" placeholder="https:// o /servicios/..." style="flex:1;">
                    <button type="button" class="live-editor-btn live-editor-btn--outline" id="${i}LinkPick">Elegir enlace</button>
                </div>
            `)}
            <div class="live-editor-field-row">
                ${c("Estilo",`
                    <select class="users-manager-select" id="${i}Style">
                        <option value="solid" ${(e.style||"solid")==="solid"?"selected":""}>Sólido</option>
                        <option value="outline" ${e.style==="outline"?"selected":""}>Contorno</option>
                    </select>
                `)}
                ${a?c("Alineación",`
                    <select class="users-manager-select" id="${i}Align">
                        <option value="left" ${e.align==="left"?"selected":""}>Izquierda</option>
                        <option value="center" ${(e.align||"center")==="center"?"selected":""}>Centro</option>
                        <option value="right" ${e.align==="right"?"selected":""}>Derecha</option>
                    </select>
                `):""}
            </div>
            ${c("Color",`<div id="${i}Color"></div>`)}
        `,t.querySelector("#"+i+"Text").addEventListener("input",l=>{e.text=l.target.value,d(),u()});const n=t.querySelector("#"+i+"Url");n.addEventListener("input",l=>{e.url=l.target.value,d(),u()}),t.querySelector("#"+i+"LinkPick").addEventListener("click",l=>{typeof window.LinkPicker!="function"&&!(window.LinkPicker&&window.LinkPicker.open)||window.LinkPicker.open({anchorEl:l.target,onSelect:p=>{n.value=p,e.url=p,d(),u()}})}),t.querySelector("#"+i+"Style").addEventListener("change",l=>{e.style=l.target.value,d(),u()}),a&&t.querySelector("#"+i+"Align").addEventListener("change",l=>{e.align=l.target.value,d(),u()}),me(t.querySelector("#"+i+"Color"),e.color||"#ff6213",l=>{e.color=l,d(),u()})}function Ae(t,e){ye(t,e,{alignField:!0})}function d(){ce||(ce=!0,j.textContent="● Cambios sin guardar",j.className="live-editor-status is-dirty")}function he(t){return x.find(e=>e._uid===t)||null}let T=null;function $(){if(W.innerHTML="",!x.length){const t=document.createElement("div");t.className="live-editor-blocks-empty",t.textContent="Este servicio todavía no tiene bloques. Agrega uno abajo.",W.appendChild(t);return}x.forEach(t=>{const e=document.createElement("div");e.className="live-editor-block-row",k==="block"&&t._uid===C&&e.classList.add("is-selected"),t.is_active||e.classList.add("is-inactive"),e.draggable=!0,e.dataset.uid=t._uid;const r=A[t.type]||A.default;e.innerHTML=`
                <span class="live-editor-block-drag-handle" title="Arrastrar para reordenar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                </span>
                <span class="live-editor-block-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${r}</svg>
                </span>
                <span class="live-editor-block-info">
                    <span class="live-editor-block-name">${s(t.title||B[t.type]||t.type)}</span>
                    <span class="live-editor-block-type">${s(B[t.type]||t.type)}</span>
                </span>
                <span class="live-editor-block-actions">
                    <button type="button" class="live-editor-toggle-active ${t.is_active?"is-on":""}" title="Activa/Inactiva"></button>
                    <button type="button" class="live-editor-block-delete" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    </button>
                </span>
            `,e.addEventListener("click",a=>{a.target.closest(".live-editor-toggle-active")||a.target.closest(".live-editor-block-delete")||ee(t._uid)}),e.querySelector(".live-editor-toggle-active").addEventListener("click",a=>{a.stopPropagation(),t.is_active=!t.is_active,d(),$(),u()}),e.querySelector(".live-editor-block-delete").addEventListener("click",a=>{a.stopPropagation();const i=t.title||B[t.type]||t.type;Y(i,()=>{const n=x.findIndex(l=>l._uid===t._uid);n!==-1&&x.splice(n,1),C===t._uid&&(C=null,I()),d(),$(),u()})}),e.addEventListener("dragstart",()=>{T=t._uid,e.classList.add("is-dragging")}),e.addEventListener("dragend",()=>{e.classList.remove("is-dragging")}),e.addEventListener("dragover",a=>{a.preventDefault(),!(T===null||T===t._uid)&&e.classList.add("drag-over")}),e.addEventListener("dragleave",()=>{e.classList.remove("drag-over")}),e.addEventListener("drop",a=>{if(a.preventDefault(),e.classList.remove("drag-over"),T===null||T===t._uid)return;const i=x.findIndex(p=>p._uid===T),n=x.findIndex(p=>p._uid===t._uid);if(T=null,i===-1||n===-1)return;const[l]=x.splice(i,1);x.splice(n,0,l),d(),$(),u()}),W.appendChild(e)})}function F(){K.classList.remove("is-selected"),J.classList.remove("is-selected"),X.classList.remove("is-selected")}function ee(t){C=t,k="block",F(),$(),I()}function be(){C=null,k="general",F(),K.classList.add("is-selected"),$(),I()}function He(){C=null,k="gallery",F(),J.classList.add("is-selected"),$(),I()}function je(){C=null,k="reviews",F(),X.classList.add("is-selected"),$(),I()}K.addEventListener("click",be),J.addEventListener("click",He),X.addEventListener("click",je),qe.addEventListener("click",()=>{const t=ke.value,e={_uid:oe(),id:null,type:t,title:"",config:Ce(t),is_active:!0};x.push(e),d(),ee(e._uid),u()});function c(t,e,r){return`<div class="live-editor-field ${r||""}">
            <label>${s(t)}</label>
            ${e}
        </div>`}function O(t,e){const r=(t||[]).map(String);return(e||[]).map(a=>`<option value="${a.id}" ${r.includes(String(a.id))?"selected":""}>${s(a.alt_text||"Imagen #"+a.id)}</option>`).join("")}function De(){v.innerHTML=`
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
            ${c("Nombre",`<input type="text" class="users-manager-input" id="leGenName" value="${s(m.name)}">`)}
            ${c("Slug (URL)",`
                <div style="display:flex;gap:8px;">
                    <input type="text" class="users-manager-input" id="leGenSlug" value="${s(m.slug)}" style="flex:1;">
                    <button type="button" id="leGenSlugGenerate" class="live-editor-btn live-editor-btn--outline" title="Generar a partir del nombre">Generar</button>
                </div>
            `,"")}
            <div class="live-editor-field-row">
                ${c("Tipo de página",`
                    <select class="users-manager-select" id="leGenPageType">
                        <option value="service" ${m.page_type==="service"?"selected":""}>Servicio (nivel 3)</option>
                        <option value="category" ${m.page_type==="category"?"selected":""}>Categoría (nivel 2)</option>
                        <option value="hub" ${m.page_type==="hub"?"selected":""}>Hub — /servicios (nivel 1)</option>
                    </select>
                `)}
                ${c("Página padre",`
                    <select class="users-manager-select" id="leGenParentId">
                        <option value="">Sin padre (nivel raíz)</option>
                        ${(g.eligibleParents||[]).map(n=>`<option value="${n.id}" ${String(m.parent_id)===String(n.id)?"selected":""}>${s(n.name)} (${n.page_type==="hub"?"Hub":"Categoría"})</option>`).join("")}
                    </select>
                `,"leGenParentField")}
            </div>
            <p class="hs-config-note">/servicios → hub · /servicios/{categoría} → nivel 2 · /servicios/{categoría}/{servicio} → nivel 3. Un servicio sin padre se sirve en /servicio/{slug} (legacy).</p>
            ${c("Descripción corta",`<textarea class="users-manager-input client-modal-textarea" id="leGenShortDesc" rows="2">${s(m.short_description)}</textarea>`)}
            <div class="live-editor-field-row">
                ${c("Precio (opcional)",`<input type="number" step="0.01" min="0" class="users-manager-input" id="leGenPrice" value="${s(m.price)}">`)}
                ${c("Moneda",`<input type="text" class="users-manager-input" id="leGenCurrency" value="${s(m.currency)}" maxlength="10">`)}
            </div>
            ${c("Visibilidad del precio",`
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenShowPrice" ${m.show_price?"checked":""}> Mostrar el precio en el sitio público
                </label>
                <p class="hs-config-note" style="margin-top:4px;">Si lo desmarcas, el servicio se sigue publicando pero sin precio visible — útil para cotizar en privado.</p>
            `)}
            ${c("Estado",`
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenIsActive" ${m.is_active?"checked":""}> Publicado (visible en el sitio público)
                </label>
            `)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            ${c("Título SEO",`<input type="text" class="users-manager-input" id="leGenSeoTitle" value="${s(m.seo_title)}" maxlength="160">`)}
            ${c("Descripción SEO",`<textarea class="users-manager-input client-modal-textarea" id="leGenSeoDesc" rows="2" maxlength="500">${s(m.seo_description)}</textarea>`)}
            ${c("",`
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenIsCanonical" ${m.canonical_url?"":"checked"}> Es la URL Canónica de este servicio
                </label>
                <p class="hs-config-note" style="margin-top:4px;">Marcado (normal): Google usa la URL de este mismo servicio. Desmárcalo solo si este servicio es muy parecido a otro y quieres que Google indexe ese otro en su lugar.</p>
            `)}
            <div id="leGenCanonicalUrlWrap" style="${m.canonical_url?"":"display:none;"}">
                ${c("URL Canónica",`<input type="url" class="users-manager-input" id="leGenCanonicalUrl" value="${s(m.canonical_url)}" maxlength="255" placeholder="https://equitermindustries.com.mx/servicio/otro-servicio-similar">`)}
            </div>
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div class="live-editor-field">
                <label>Rating y reseñas — Promedio mostrado</label>
                <p class="hs-config-note" style="margin:0 0 8px;">Contenido curado por el equipo, igual que la FAQ. Estas cifras son de marketing (no tienen por qué coincidir con el número de reseñas capturadas en el panel "Reseñas") y nunca alimentan el marcado SEO — el marcado usa siempre el conteo real.</p>
            </div>
            <div class="live-editor-field-row">
                ${c("Promedio mostrado (0–5)",`<input type="number" step="0.1" min="0" max="5" class="users-manager-input" id="leGenRatingAvg" value="${s(m.rating_average_displayed)}">`)}
                ${c("Total de servicios calificados",`<input type="number" min="0" class="users-manager-input" id="leGenRatingTotal" value="${s(m.rating_total_rated)}">`)}
            </div>
            <div class="live-editor-field-row">
                ${c("% que recomendaría el servicio",`<input type="number" step="0.1" min="0" max="100" class="users-manager-input" id="leGenRatingRecommend" value="${s(m.rating_recommend_percent)}">`)}
                ${c("Puntualidad de cuadrilla (0–5)",`<input type="number" step="0.1" min="0" max="5" class="users-manager-input" id="leGenRatingPunctuality" value="${s(m.rating_punctuality_average)}">`)}
            </div>
            <div class="live-editor-field-row">
                ${c("Clientes recurrentes",`<input type="number" min="0" class="users-manager-input" id="leGenRatingRecurring" value="${s(m.rating_recurring_clients)}">`)}
                ${c("Calificando desde (año, opcional)",`<input type="number" min="2000" max="2100" class="users-manager-input" id="leGenRatingSince" value="${s(m.rating_since_year)}">`)}
            </div>
            ${c("Distribución por estrella",`
                <div class="live-editor-field-row" style="grid-template-columns:repeat(5,1fr);">
                    ${[5,4,3,2,1].map(n=>`
                        <div>
                            <label style="font-size:11px;color:#6b7280;display:block;margin-bottom:2px;">${n} ★</label>
                            <input type="number" min="0" class="users-manager-input le-gen-rating-dist" data-star="${n}" value="${s((m.rating_distribution||{})[n]??(m.rating_distribution||{})[String(n)]??"")}">
                        </div>
                    `).join("")}
                </div>
            `)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div class="live-editor-field">
                <label>Preguntas frecuentes</label>
                <p class="hs-config-note" style="margin:0 0 8px;">Alimentan el <code>FAQPage</code> de Google y el acordeón visible cuando hay un bloque "Preguntas Frecuentes" en esta página.</p>
                <div id="leGenFaqRows" class="hs-faq-items"></div>
                <button type="button" class="button-secondary size-adjustment" id="leGenFaqAdd" style="margin-top:10px;">+ Agregar pregunta</button>
            </div>
            <div id="leGenErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
            <button type="button" id="leGenSaveBtn" class="live-editor-btn live-editor-btn--solid live-editor-btn--block">Guardar información general</button>
        `,v.querySelector("#leGenSaveBtn").addEventListener("click",Fe),v.querySelector("#leGenFaqAdd").addEventListener("click",()=>{m.faqs=m.faqs||[],m.faqs.push({question:"",answer:""}),d(),te()}),te(),v.querySelector("#leGenSlugGenerate").addEventListener("click",()=>{const n=v.querySelector("#leGenName");v.querySelector("#leGenSlug").value=Me(n.value),d()});const t=v.querySelector("#leGenIsCanonical"),e=v.querySelector("#leGenCanonicalUrlWrap");t.addEventListener("change",()=>{e.style.display=t.checked?"none":""});const r=v.querySelector("#leGenPageType"),a=v.querySelector(".leGenParentField"),i=()=>{const n=r.value==="hub"||r.value==="category";a&&(a.style.display=n?"none":"")};r.addEventListener("change",i),i()}function te(){m.faqs=m.faqs||[];const t=v.querySelector("#leGenFaqRows");t&&(t.innerHTML="",m.faqs.forEach((e,r)=>{const a=document.createElement("div");a.className="hs-faq-row",a.innerHTML=`
                <div class="hs-faq-row-head">
                    <span class="hs-faq-row-num">${r+1}</span>
                    <div class="hs-faq-row-actions">
                        <button type="button" class="hs-faq-btn hs-faq-remove" title="Eliminar">&times;</button>
                    </div>
                </div>
                <input type="text" class="users-manager-input hs-faq-question" placeholder="Pregunta" value="${s(e.question)}">
                <textarea class="users-manager-input client-modal-textarea hs-faq-answer" rows="2" placeholder="Respuesta">${s(e.answer)}</textarea>
            `,a.querySelector(".hs-faq-question").addEventListener("input",i=>{e.question=i.target.value,d()}),a.querySelector(".hs-faq-answer").addEventListener("input",i=>{e.answer=i.target.value,d()}),a.querySelector(".hs-faq-remove").addEventListener("click",()=>{m.faqs.splice(r,1),d(),te()}),t.appendChild(a)}))}async function Fe(){const t=v.querySelector("#leGenSaveBtn"),e=v.querySelector("#leGenErrors");e.style.display="none",e.innerHTML="",document.querySelectorAll("#leEditPanel .is-invalid").forEach(n=>n.classList.remove("is-invalid"));const r=v.querySelector("#leGenPageType").value,a={name:v.querySelector("#leGenName").value,slug:v.querySelector("#leGenSlug").value,page_type:r,parent_id:r==="hub"?"":v.querySelector("#leGenParentId").value,short_description:v.querySelector("#leGenShortDesc").value,price:v.querySelector("#leGenPrice").value,currency:v.querySelector("#leGenCurrency").value,show_price:v.querySelector("#leGenShowPrice").checked,is_active:v.querySelector("#leGenIsActive").checked,seo_title:v.querySelector("#leGenSeoTitle").value,seo_description:v.querySelector("#leGenSeoDesc").value,is_canonical:v.querySelector("#leGenIsCanonical").checked,canonical_url:v.querySelector("#leGenCanonicalUrl").value,faq_items:m.faqs||[],rating_average_displayed:v.querySelector("#leGenRatingAvg").value,rating_total_rated:v.querySelector("#leGenRatingTotal").value,rating_recommend_percent:v.querySelector("#leGenRatingRecommend").value,rating_punctuality_average:v.querySelector("#leGenRatingPunctuality").value,rating_recurring_clients:v.querySelector("#leGenRatingRecurring").value,rating_since_year:v.querySelector("#leGenRatingSince").value,rating_distribution:Array.from(v.querySelectorAll(".le-gen-rating-dist")).reduce((n,l)=>(n[l.dataset.star]=l.value,n),{})};t.disabled=!0;const i=t.textContent;t.textContent="Guardando...";try{const n=await fetch(g.generalUrl,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify(a)}),l=await n.json();if(n.ok){Object.assign(m,l.servicePage),ue&&(ue.textContent=m.name),document.title="Editor en vivo - "+m.name+" - Admin";const p=document.getElementById("leBrowserUrl");p&&(p.textContent="equitermindustries.com.mx"+m.public_path);const o=document.getElementById("leViewLiveLink");if(o){const h=window.location.origin;o.href=h+m.public_path}window.showCenterToast&&showCenterToast("Información general guardada."),u()}else if(n.status===422){const p=l.errors||{};e.innerHTML=Object.values(p).flat().map(h=>`<p>${h}</p>`).join(""),e.style.display="block";const o={name:"leGenName",slug:"leGenSlug",page_type:"leGenPageType",parent_id:"leGenParentId",short_description:"leGenShortDesc",price:"leGenPrice",currency:"leGenCurrency",seo_title:"leGenSeoTitle",seo_description:"leGenSeoDesc",canonical_url:"leGenCanonicalUrl",rating_average_displayed:"leGenRatingAvg",rating_total_rated:"leGenRatingTotal",rating_recommend_percent:"leGenRatingRecommend",rating_punctuality_average:"leGenRatingPunctuality",rating_recurring_clients:"leGenRatingRecurring",rating_since_year:"leGenRatingSince"};Object.keys(p).forEach(h=>{const b=v.querySelector("#"+(o[h]||""));b&&b.classList.add("is-invalid")})}else throw new Error("save-general-failed")}catch{e.innerHTML="<p>No se pudo guardar. Intenta de nuevo.</p>",e.style.display="block"}finally{t.disabled=!1,t.textContent=i}}let R=null;function Oe(){v.innerHTML=`
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
        `,N()}function N(){const t=v.querySelector("#leGalleryGrid");if(!t)return;t.innerHTML="",(g.images||[]).forEach((r,a)=>{const i=document.createElement("div");i.className="service-gallery-item",i.dataset.id=r.id,i.draggable=!0,i.innerHTML=`
                <div class="service-gallery-item__drag" title="Arrastrar para reordenar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                </div>
                ${a===0?'<span class="service-gallery-item__badge">PORTADA</span>':""}
                <button type="button" class="service-gallery-item__remove" title="Quitar">&times;</button>
                <img src="${r.url}" alt="${s(r.alt_text||"")}">
                <input type="text" class="users-manager-input service-gallery-item__alt" placeholder="Texto alternativo" value="${s(r.alt_text||"")}">
            `,i.querySelector(".service-gallery-item__remove").addEventListener("click",()=>{Y(r.alt_text||"Imagen #"+r.id,()=>Ue(r.id))});let n=null;i.querySelector(".service-gallery-item__alt").addEventListener("input",l=>{r.alt_text=l.target.value,clearTimeout(n),n=setTimeout(()=>ze(r.id,r.alt_text),500)}),i.addEventListener("dragstart",()=>{R=r.id,i.classList.add("is-dragging")}),i.addEventListener("dragend",()=>{i.classList.remove("is-dragging")}),i.addEventListener("dragover",l=>{l.preventDefault(),!(R===null||R===r.id)&&i.classList.add("drag-over")}),i.addEventListener("dragleave",()=>{i.classList.remove("drag-over")}),i.addEventListener("drop",l=>{if(l.preventDefault(),i.classList.remove("drag-over"),R===null||R===r.id)return;const p=g.images.findIndex(b=>String(b.id)===String(R)),o=g.images.findIndex(b=>String(b.id)===String(r.id));if(R=null,p===-1||o===-1)return;const[h]=g.images.splice(p,1);g.images.splice(o,0,h),N(),Ve()}),t.appendChild(i)});const e=document.createElement("div");e.className="service-gallery-item service-gallery-item--add",e.id="leGalleryAddTile",e.innerHTML=`
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            <span>Arrastra o selecciona</span>
        `,e.addEventListener("click",()=>{typeof window.openImagePicker=="function"&&window.openImagePicker(null,{onSelect:Ne})}),t.appendChild(e)}async function Ne(t){try{const e=await fetch(g.imagesStoreUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({image_url:t,alt_text:""})}),r=await e.json();e.ok?(g.images.push({id:r.image.id,url:r.image.url,alt_text:r.image.alt_text}),N(),window.showCenterToast&&showCenterToast("Imagen agregada."),u()):window.showCenterToast&&showCenterToast("No se pudo agregar la imagen.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al agregar la imagen.","error")}}async function Ue(t){try{if((await fetch(g.imageDestroyUrlTemplate.replace("__IMAGE_ID__",t),{method:"DELETE",headers:{"X-CSRF-TOKEN":E,Accept:"application/json"}})).ok){const r=g.images.findIndex(a=>String(a.id)===String(t));r!==-1&&g.images.splice(r,1),N(),window.showCenterToast&&showCenterToast("Imagen eliminada."),u()}else window.showCenterToast&&showCenterToast("No se pudo eliminar la imagen.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al eliminar la imagen.","error")}}async function ze(t,e){try{await fetch(g.imageUpdateUrlTemplate.replace("__IMAGE_ID__",t),{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({alt_text:e})})}catch{}}async function Ve(){const t=g.images.map(e=>e.id);try{(await fetch(g.imagesReorderUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({order:t})})).ok?(window.showCenterToast&&showCenterToast("Orden de galería actualizado."),u()):window.showCenterToast&&showCenterToast("No se pudo guardar el nuevo orden de la galería.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al reordenar la galería.","error")}}let G=null;function re(){const t=w.filter(e=>e.is_visible).length;v.innerHTML=`
            <div class="live-editor-panel-header">
                <span class="live-editor-panel-header-info">
                    <span class="live-editor-block-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </span>
                    <span>
                        <strong>Reseñas capturadas</strong>
                        <small>${t} visibles de ${w.length}</small>
                    </span>
                </span>
            </div>
            <div id="leReviewsList" class="hs-repeat-rows"></div>
            <button type="button" id="leReviewAddBtn" class="live-editor-btn live-editor-btn--outline live-editor-btn--block" style="margin-top:10px;">+ Agregar reseña</button>
            <div id="leReviewFormWrap"></div>
        `,fe(),v.querySelector("#leReviewAddBtn").addEventListener("click",()=>_e(null))}function fe(){const t=v.querySelector("#leReviewsList");if(t){if(t.innerHTML="",!w.length){t.innerHTML='<p class="hs-config-note">Todavía no hay reseñas capturadas para este servicio.</p>';return}w.forEach(e=>{const r=document.createElement("div");r.className="service-review-row",r.draggable=!0,r.dataset.id=e.id;const a=e.comment||"";r.innerHTML=`
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
                    <p class="service-review-row__comment">${s(a.length>140?a.slice(0,140)+"…":a)}</p>
                </div>
                <div class="header-right-user-manager">
                    <button type="button" class="table-users-manager-action-btn edit" title="Editar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/></svg>
                    </button>
                    <button type="button" class="table-users-manager-action-btn delete" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                    </button>
                </div>
            `,r.querySelector(".edit").addEventListener("click",()=>_e(e)),r.querySelector(".delete").addEventListener("click",()=>{Y(e.customer_name||"Reseña",()=>Ke(e.id))}),r.addEventListener("dragstart",()=>{G=e.id,r.classList.add("is-dragging")}),r.addEventListener("dragend",()=>r.classList.remove("is-dragging")),r.addEventListener("dragover",i=>{i.preventDefault(),!(G===null||G===e.id)&&r.classList.add("drag-over")}),r.addEventListener("dragleave",()=>r.classList.remove("drag-over")),r.addEventListener("drop",i=>{if(i.preventDefault(),r.classList.remove("drag-over"),G===null||G===e.id)return;const n=w.findIndex(o=>String(o.id)===String(G)),l=w.findIndex(o=>String(o.id)===String(e.id));if(G=null,n===-1||l===-1)return;const[p]=w.splice(n,1);w.splice(l,0,p),fe(),Je()}),t.appendChild(r)})}}function _e(t){const e=!!t,r=v.querySelector("#leReviewFormWrap");if(!r)return;const a=e?Object.assign({},t):{customer_name:"",customer_role:"",customer_company:"",customer_city:"",customer_state:"",review_date:"",rating:5,comment:"",categories:[],is_verified:!1,is_visible:!0,business_response:"",business_response_date:""};r.innerHTML=`
            <div class="show-user-divider" style="margin:14px 0 10px;"></div>
            <p class="live-editor-col-title">${e?"Editar reseña":"Nueva reseña"}</p>
            ${c("Cliente",`<input type="text" class="users-manager-input" id="leRevName" value="${s(a.customer_name)}">`)}
            <div class="live-editor-field-row">
                ${c("Puesto (opcional)",`<input type="text" class="users-manager-input" id="leRevRole" value="${s(a.customer_role)}" placeholder="Jefe de mantenimiento">`)}
                ${c("Empresa (opcional)",`<input type="text" class="users-manager-input" id="leRevCompany" value="${s(a.customer_company)}">`)}
            </div>
            <div class="live-editor-field-row">
                ${c("Ciudad (opcional)",`<input type="text" class="users-manager-input" id="leRevCity" value="${s(a.customer_city)}">`)}
                ${c("Estado (opcional)",`<input type="text" class="users-manager-input" id="leRevState" value="${s(a.customer_state)}" placeholder="JAL">`)}
            </div>
            ${c("Fecha",`<input type="date" class="users-manager-input" id="leRevDate" value="${s(a.review_date)}">`)}
            ${c("Calificación",`<div class="service-review-stars-input" id="leRevStars"></div><input type="hidden" id="leRevRating" value="${a.rating||5}">`)}
            <div class="live-editor-field">
                <label>Comentario &middot; <span id="leRevCommentCount">${(a.comment||"").length}</span>/240</label>
                <textarea class="users-manager-input client-modal-textarea" id="leRevComment" rows="3" maxlength="240">${s(a.comment)}</textarea>
            </div>
            ${c("Categorías",`
                <div class="hs-product-chips" style="flex-wrap:wrap;">
                    ${Object.entries(g.reviewCategories||{}).map(([l,p])=>`
                        <label style="display:inline-flex;align-items:center;gap:4px;border:1px solid #d1d5db;border-radius:999px;padding:4px 10px;font-size:12.5px;cursor:pointer;font-weight:400;">
                            <input type="checkbox" class="le-rev-category" value="${s(l)}" ${(a.categories||[]).includes(l)?"checked":""}> ${s(p)}
                        </label>
                    `).join("")}
                </div>
            `)}
            <div class="live-editor-field-row">
                ${c("",`<label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;"><input type="checkbox" id="leRevVerified" ${a.is_verified?"checked":""}> Cliente verificado</label>`)}
                ${c("",`<label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;"><input type="checkbox" id="leRevVisible" ${a.is_visible?"checked":""}> Visible en público</label>`)}
            </div>
            ${c("Respuesta de Equiterm (opcional)",`<textarea class="users-manager-input client-modal-textarea" id="leRevResponse" rows="2">${s(a.business_response)}</textarea>`)}
            ${c("Fecha de respuesta (opcional)",`<input type="date" class="users-manager-input" id="leRevResponseDate" value="${s(a.business_response_date)}">`)}
            <div id="leRevErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
            <div style="display:flex;gap:8px;">
                <button type="button" id="leRevCancel" class="live-editor-btn live-editor-btn--outline" style="flex:1;">Cancelar</button>
                <button type="button" id="leRevSave" class="live-editor-btn live-editor-btn--solid" style="flex:1;">${e?"Guardar cambios":"Crear reseña"}</button>
            </div>
        `;const i=r.querySelector("#leRevStars");function n(l){r.querySelector("#leRevRating").value=l,i.innerHTML="";for(let p=1;p<=5;p++){const o=document.createElement("button");o.type="button",o.className="service-review-star"+(p<=l?" is-active":""),o.textContent="★",o.addEventListener("click",()=>n(p)),i.appendChild(o)}}n(a.rating||5),r.querySelector("#leRevComment").addEventListener("input",function(){r.querySelector("#leRevCommentCount").textContent=this.value.length}),r.querySelector("#leRevCancel").addEventListener("click",()=>{r.innerHTML=""}),r.querySelector("#leRevSave").addEventListener("click",()=>We(e?t.id:null,r)),r.scrollIntoView({behavior:"smooth",block:"nearest"})}async function We(t,e){const r=e.querySelector("#leRevErrors");r.style.display="none",r.innerHTML="";const a={customer_name:e.querySelector("#leRevName").value,customer_role:e.querySelector("#leRevRole").value||null,customer_company:e.querySelector("#leRevCompany").value||null,customer_city:e.querySelector("#leRevCity").value||null,customer_state:e.querySelector("#leRevState").value||null,review_date:e.querySelector("#leRevDate").value||null,rating:parseInt(e.querySelector("#leRevRating").value,10)||5,comment:e.querySelector("#leRevComment").value,categories:Array.from(e.querySelectorAll(".le-rev-category:checked")).map(l=>l.value),is_verified:e.querySelector("#leRevVerified").checked,is_visible:e.querySelector("#leRevVisible").checked,business_response:e.querySelector("#leRevResponse").value||null,business_response_date:e.querySelector("#leRevResponseDate").value||null},i=e.querySelector("#leRevSave");i.disabled=!0;const n=i.textContent;i.textContent="Guardando...";try{const l=t?g.reviewUpdateUrlTemplate.replace("__REVIEW_ID__",t):g.reviewsStoreUrl,p=await fetch(l,{method:t?"PUT":"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify(a)}),o=await p.json();if(p.ok){if(t){const h=w.findIndex(b=>String(b.id)===String(t));h!==-1&&(w[h]=Object.assign({},w[h],o.review))}else w.push(o.review);re(),window.showCenterToast&&showCenterToast(t?"Reseña actualizada.":"Reseña creada."),u()}else if(p.status===422){const h=o.errors||{};r.innerHTML=Object.values(h).flat().map(b=>`<p>${b}</p>`).join(""),r.style.display="block",i.disabled=!1,i.textContent=n}else throw new Error("save-review-failed")}catch{r.innerHTML="<p>No se pudo guardar. Intenta de nuevo.</p>",r.style.display="block",i.disabled=!1,i.textContent=n}}async function Ke(t){try{if((await fetch(g.reviewDestroyUrlTemplate.replace("__REVIEW_ID__",t),{method:"DELETE",headers:{"X-CSRF-TOKEN":E,Accept:"application/json"}})).ok){const r=w.findIndex(a=>String(a.id)===String(t));r!==-1&&w.splice(r,1),re(),window.showCenterToast&&showCenterToast("Reseña eliminada."),u()}else window.showCenterToast&&showCenterToast("No se pudo eliminar la reseña.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al eliminar la reseña.","error")}}async function Je(){const t=w.map(e=>e.id);try{(await fetch(g.reviewsReorderUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({order:t})})).ok?window.showCenterToast&&showCenterToast("Orden de reseñas actualizado."):window.showCenterToast&&showCenterToast("No se pudo guardar el nuevo orden.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al guardar el orden.","error")}}function I(){if(k==="general"){De();return}if(k==="gallery"){Oe();return}if(k==="reviews"){re();return}const t=he(C);if(!t){v.innerHTML='<div class="live-editor-panel-empty">Selecciona un bloque de la izquierda para editarlo aquí, o "Información general" para nombre, slug, precio y SEO.</div>';return}const e=A[t.type]||A.default;if(v.innerHTML=`
            <div class="live-editor-panel-header">
                <span class="live-editor-panel-header-info">
                    <span class="live-editor-block-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${e}</svg>
                    </span>
                    <span>
                        <strong>${s(t.title||B[t.type]||t.type)}</strong>
                        <small>Editando bloque</small>
                    </span>
                </span>
                <button type="button" class="live-editor-panel-close" id="lePanelClose" title="Cerrar">&times;</button>
            </div>
            ${c("Título (opcional, puedes usar {servicio})",`<input type="text" class="users-manager-input" id="leTitle" value="${s(t.title)}" placeholder="Ej: Beneficios del servicio">`)}
            ${we.includes(t.type)?'<div id="leTitleStyle"></div>':""}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div id="leTypeFields"></div>
        `,v.querySelector("#lePanelClose").addEventListener("click",()=>{C=null,$(),I()}),v.querySelector("#leTitle").addEventListener("input",r=>{t.title=r.target.value,d(),$(),u()}),we.includes(t.type)){const r=t.config=t.config||{};r.title_style=r.title_style||{},P(v.querySelector("#leTitleStyle"),r.title_style,{tagChoices:["h2","h3"]})}Xe(v.querySelector("#leTypeFields"),t)}const we=["benefits_grid","process_steps","content_tabs","gallery_carousel","faq"];function Xe(t,e){const r=e.config=e.config||{};switch(e.type){case"banner":U(t,r,"");break;case"dual_banner":t.innerHTML='<p class="hs-config-subtitle">Banner Izquierdo</p><div id="leDbLeft"></div><p class="hs-config-subtitle">Banner Derecho</p><div id="leDbRight"></div>',r.left=r.left||{image_url:"",link_url:"",alt:""},r.right=r.right||{image_url:"",link_url:"",alt:""},U(t.querySelector("#leDbLeft"),r.left,"Left"),U(t.querySelector("#leDbRight"),r.right,"Right");break;case"product_carousel":xe(t,r,"");break;case"product_carousel_banner":t.innerHTML='<p class="hs-config-subtitle">Banner</p><div id="lePcbBanner"></div><p class="hs-config-subtitle">Productos del carrusel</p><div id="lePcbCarousel"></div>',U(t.querySelector("#lePcbBanner"),{image_url:r.banner_image_url,link_url:r.banner_link_url,alt:r.banner_alt},"PcbBanner",(a,i)=>{a==="image_url"&&(r.banner_image_url=i),a==="link_url"&&(r.banner_link_url=i),a==="alt"&&(r.banner_alt=i)}),xe(t.querySelector("#lePcbCarousel"),r,"Pcb");break;case"category_grid":Ye(t,r);break;case"brand_carousel":t.innerHTML='<p class="hs-config-note">Este bloque muestra automáticamente todas las marcas activas. No requiere configuración adicional.</p>';break;case"html_block":Ze(t,r);break;case"faq":et(t,r);break;case"rich_header":tt(t,r);break;case"content_tabs":ae(t,r);break;case"benefits_grid":ie(t,r);break;case"process_steps":ne(t,r);break;case"gallery_carousel":rt(t,r);break;case"rating_reviews":at(t,r);break;case"cta_final":it(t,r);break;case"button":Ae(t,r);break;default:t.innerHTML='<p class="hs-config-note">Tipo de bloque desconocido.</p>'}}function U(t,e,r,a){const i="leBanner"+r+"Image",n="leBanner"+r+"Link",l="leBanner"+r+"Alt";t.innerHTML=`
            ${c("URL de Imagen",`
                <div class="img-picker-field">
                    <input type="text" class="users-manager-input" id="${i}" value="${s(e.image_url)}" placeholder="https://...">
                    <button type="button" class="img-picker-trigger-btn" data-target="${i}">Seleccionar</button>
                </div>
            `)}
            <div class="live-editor-field-row">
                ${c("URL de Enlace",`<input type="text" class="users-manager-input" id="${n}" value="${s(e.link_url)}" placeholder="/servicio/otro-servicio">`)}
                ${c("Texto Alternativo",`<input type="text" class="users-manager-input" id="${l}" value="${s(e.alt)}">`)}
            </div>
        `;const p=y=>a?a("image_url",y):e.image_url=y,o=y=>a?a("link_url",y):e.link_url=y,h=y=>a?a("alt",y):e.alt=y,b=t.querySelector("#"+i);b.addEventListener("input",()=>{p(b.value),d(),u()}),t.querySelector("#"+n).addEventListener("input",y=>{o(y.target.value),d(),u()}),t.querySelector("#"+l).addEventListener("input",y=>{h(y.target.value),d(),u()}),t.querySelector(".img-picker-trigger-btn").addEventListener("click",()=>{typeof window.openImagePicker=="function"&&window.openImagePicker(i)})}function xe(t,e,r){const a="leSource"+r,i="leLimit"+r,n="leCategory"+r,l="leBrand"+r,p="leCollection"+r,o="leManualWrap"+r,h=Object.keys(se).map(f=>`<option value="${f}" ${e.source===f?"selected":""}>${se[f]}</option>`).join("");t.innerHTML=`
            <div class="live-editor-field-row">
                ${c("Origen de Productos",`<select class="users-manager-select" id="${a}">${h}</select>`)}
                ${c("Límite de Productos",`<input type="number" class="users-manager-input" id="${i}" min="1" max="50" value="${e.limit??10}">`)}
            </div>
            <div id="leSourceFields${r}"></div>
        `,t.querySelector("#"+a).addEventListener("change",f=>{e.source=f.target.value,d(),b(),u()}),t.querySelector("#"+i).addEventListener("input",f=>{e.limit=parseInt(f.target.value,10)||10,d(),u()});function b(){const f=t.querySelector("#leSourceFields"+r);e.source==="category"?(f.innerHTML=c("Categoría",`<select class="users-manager-select" id="${n}">
                    <option value="">Selecciona una categoría</option>
                    ${(g.categories||[]).map(y=>`<option value="${y.id}" ${String(e.category_id)===String(y.id)?"selected":""}>${s(y.name)}</option>`).join("")}
                </select>`),f.querySelector("#"+n).addEventListener("change",y=>{e.category_id=y.target.value||null,d(),u()})):e.source==="brand"?(f.innerHTML=c("Marca",`<select class="users-manager-select" id="${l}">
                    <option value="">Selecciona una marca</option>
                    ${(g.brands||[]).map(y=>`<option value="${y.id}" ${String(e.brand_id)===String(y.id)?"selected":""}>${s(y.name)}</option>`).join("")}
                </select>`),f.querySelector("#"+l).addEventListener("change",y=>{e.brand_id=y.target.value||null,d(),u()})):e.source==="collection"?(f.innerHTML=c("Colección",`<select class="users-manager-select" id="${p}">
                    <option value="">Selecciona una colección</option>
                    ${(g.collections||[]).map(y=>`<option value="${y.id}" ${String(e.collection_id)===String(y.id)?"selected":""}>${s(y.name)}</option>`).join("")}
                </select>`),f.querySelector("#"+p).addEventListener("change",y=>{e.collection_id=y.target.value||null,d(),u()})):e.source==="manual"?(f.innerHTML=`<div class="live-editor-field"><label>Productos</label><div id="${o}"></div></div>`,Qe(f.querySelector("#"+o),e.product_ids||[],y=>{e.product_ids=y,d(),u()})):f.innerHTML=""}b()}function Qe(t,e,r){t.innerHTML=`
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
        `;const a=t.querySelector(".hs-product-search__input"),i=t.querySelector(".hs-product-search__dropdown"),n=t.querySelector(".hs-product-search__list"),l=t.querySelector(".hs-product-search__empty"),p=t.querySelector(".hs-product-chips");let o=[],h=null;function b(){p.innerHTML="",o.forEach(S=>{const _=document.createElement("span");_.className="hs-product-chip",_.innerHTML=`<span>${s(S.name)}</span><small>${s(S.sku)}</small><button type="button" aria-label="Quitar">&times;</button>`,_.querySelector("button").addEventListener("click",()=>{o=o.filter(L=>L.id!==S.id),b(),r(o.map(L=>L.id))}),p.appendChild(_)})}function f(){i.style.display="none",n.innerHTML=""}function y(S){n.innerHTML="";const _=S.filter(L=>!o.some(q=>q.id===L.id));if(!_.length){l.style.display="block",n.style.display="none";return}l.style.display="none",n.style.display="block",_.forEach(L=>{const q=document.createElement("li");q.className="hs-product-search__item",q.innerHTML=`<span>${s(L.name)}</span><small>SKU: ${s(L.sku)}</small>`,q.addEventListener("click",()=>{o.push({id:L.id,name:L.name,sku:L.sku}),b(),r(o.map(le=>le.id)),a.value="",f()}),n.appendChild(q)})}async function Ee(S){try{const _=new URL(g.productsSearchUrl,window.location.origin);Object.entries(S).forEach(([q,le])=>_.searchParams.set(q,le));const L=await fetch(_.toString(),{headers:{Accept:"application/json"}});return L.ok?await L.json():[]}catch{return[]}}a.addEventListener("input",function(){const S=this.value.trim();if(clearTimeout(h),S.length<2){f();return}h=setTimeout(async()=>{const _=await Ee({q:S});i.style.display="block",y(_)},300)}),document.addEventListener("click",S=>{t.contains(S.target)||f()}),e&&e.length&&Ee({ids:e.join(",")}).then(S=>{o=S.map(_=>({id:_.id,name:_.name,sku:_.sku})),b()})}function Ye(t,e){t.innerHTML=c("Categorías a mostrar (vacío = todas las principales activas)",`
            <select class="users-manager-select" id="leCategoryIds" multiple size="6">
                ${(g.categories||[]).map(r=>`<option value="${r.id}" ${(e.category_ids||[]).map(String).includes(String(r.id))?"selected":""}>${s(r.name)}</option>`).join("")}
            </select>
        `),t.querySelector("#leCategoryIds").addEventListener("change",r=>{e.category_ids=Array.from(r.target.selectedOptions).map(a=>parseInt(a.value,10)),d(),u()})}function Ze(t,e){t.innerHTML=c("Contenido HTML",`<textarea class="users-manager-input client-modal-textarea" id="leHtml" rows="8" placeholder="<div>...</div>">${s(e.html)}</textarea>`),t.querySelector("#leHtml").addEventListener("input",r=>{e.html=r.target.value,d(),u()})}function et(t,e){t.innerHTML=`
            ${c("Texto descriptivo (opcional)",`<textarea class="users-manager-input client-modal-textarea" id="leFaqDescription" rows="2">${s(e.description)}</textarea>`)}
            <p class="hs-config-note">Las preguntas y respuestas se capturan en <strong>Información general → Preguntas frecuentes</strong>. Esta sección solo define dónde aparece el acordeón en la página, su título y el texto descriptivo.</p>
        `,t.querySelector("#leFaqDescription").addEventListener("input",r=>{e.description=r.target.value,d(),u()})}function tt(t,e){t.innerHTML=`
            ${c("Badges cortos (separados por ·, máx. 3)",`<input type="text" class="users-manager-input" id="leRhBadges" value="${s((e.badges||[]).join(" · "))}" placeholder="Garantía 6 meses · Reporte técnico incluido">`)}
            ${c("Líneas de meta (una por línea)",`<textarea class="users-manager-input client-modal-textarea" id="leRhMetaLines" rows="2">${s((e.meta_lines||[]).join(`
`))}</textarea>`)}
            ${c("Texto del botón",`<input type="text" class="users-manager-input" id="leRhWhatsapp" value="${s(e.whatsapp_text||"Cotizar por WhatsApp")}">`)}
            <p class="hs-config-note">El CTA siempre abre WhatsApp; no existe botón de llamada.</p>
            ${c("Precio mostrado (opcional)",`<input type="text" class="users-manager-input" id="leRhPriceLabel" value="${s(e.price_label)}" placeholder="$8,500 MXN + IVA">`)}
            ${c("Imágenes de fondo (galería del servicio)",`<select class="users-manager-select" id="leRhBgImages" multiple size="4">${O(e.background_image_ids,g.images)}</select>`)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <p class="hs-config-note">El título y la descripción corta se editan en "Información general" — aquí solo se controla su estilo.</p>
            <p class="live-editor-col-title" style="margin:6px 0 0;">Estilo del título (H1, fijo)</p>
            <div id="leRhTitleStyle"></div>
            <p class="live-editor-col-title" style="margin:14px 0 0;">Estilo de la descripción corta</p>
            <div id="leRhSubtitleStyle"></div>
        `,t.querySelector("#leRhBadges").addEventListener("input",r=>{e.badges=r.target.value.split("·").map(a=>a.trim()).filter(Boolean).slice(0,3),d(),u()}),t.querySelector("#leRhMetaLines").addEventListener("input",r=>{e.meta_lines=r.target.value.split(`
`).map(a=>a.trim()).filter(Boolean),d(),u()}),t.querySelector("#leRhWhatsapp").addEventListener("input",r=>{e.whatsapp_text=r.target.value,d(),u()}),t.querySelector("#leRhPriceLabel").addEventListener("input",r=>{e.price_label=r.target.value,d(),u()}),t.querySelector("#leRhBgImages").addEventListener("change",r=>{e.background_image_ids=Array.from(r.target.selectedOptions).map(a=>parseInt(a.value,10)),d(),u()}),e.title_style=e.title_style||{},e.subtitle_style=e.subtitle_style||{},P(t.querySelector("#leRhTitleStyle"),e.title_style,{}),P(t.querySelector("#leRhSubtitleStyle"),e.subtitle_style,{})}function ae(t,e){e.tabs=e.tabs||[];let r='<p class="hs-config-note">Cada pestaña se muestra como pestaña horizontal en público.</p><div id="leCtRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="leCtAdd" style="margin-top:10px;">+ Agregar pestaña</button>';t.innerHTML=r;const a=t.querySelector("#leCtRows");e.tabs.forEach((i,n)=>{const l=document.createElement("div");l.className="hs-repeat-row",l.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${n+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-label" placeholder="Título de pestaña" value="${s(i.label)}">
                <input type="text" class="users-manager-input le-subtitle" placeholder="Subtítulo (opcional)" style="margin-top:6px;" value="${s(i.subtitle)}">
                <textarea class="users-manager-input client-modal-textarea le-body" rows="2" placeholder="Párrafo" style="margin-top:6px;">${s(i.body)}</textarea>
                <textarea class="users-manager-input client-modal-textarea le-bullets" rows="2" placeholder="Viñetas, una por línea" style="margin-top:6px;">${s((i.bullets||[]).join(`
`))}</textarea>
                <select class="users-manager-select le-image" style="margin-top:6px;">
                    <option value="">Sin imagen</option>
                    ${O(i.image_id?[i.image_id]:[],g.images)}
                </select>
                <div class="le-tab-style" style="margin-top:6px;"></div>
            `,l.querySelector(".le-label").addEventListener("input",p=>{i.label=p.target.value,d(),$(),u()}),l.querySelector(".le-subtitle").addEventListener("input",p=>{i.subtitle=p.target.value,d(),u()}),l.querySelector(".le-body").addEventListener("input",p=>{i.body=p.target.value,d(),u()}),l.querySelector(".le-bullets").addEventListener("input",p=>{i.bullets=p.target.value.split(`
`).map(o=>o.trim()).filter(Boolean),d(),u()}),l.querySelector(".le-image").addEventListener("change",p=>{i.image_id=p.target.value||null,d(),u()}),l.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.tabs.splice(n,1),d(),ae(t,e),u()}),i.style=i.style||{},P(l.querySelector(".le-tab-style"),i.style,{}),a.appendChild(l)}),t.querySelector("#leCtAdd").addEventListener("click",()=>{e.tabs.push({label:"",subtitle:"",body:"",bullets:[],image_id:null}),d(),ae(t,e),u()})}function ie(t,e){e.items=e.items||[],t.innerHTML='<p class="hs-config-note">Tarjetas de cifra + título + descripción.</p><div id="leBgRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="leBgAdd" style="margin-top:10px;">+ Agregar beneficio</button>';const r=t.querySelector("#leBgRows");e.items.forEach((a,i)=>{const n=document.createElement("div");n.className="hs-repeat-row",n.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${i+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-figure" placeholder="Cifra (ej. -12%)" value="${s(a.figure)}">
                <input type="text" class="users-manager-input le-title" placeholder="Título" style="margin-top:6px;" value="${s(a.title)}">
                <textarea class="users-manager-input client-modal-textarea le-description" rows="2" placeholder="Descripción corta" style="margin-top:6px;">${s(a.description)}</textarea>
            `,n.querySelector(".le-figure").addEventListener("input",l=>{a.figure=l.target.value,d(),u()}),n.querySelector(".le-title").addEventListener("input",l=>{a.title=l.target.value,d(),$(),u()}),n.querySelector(".le-description").addEventListener("input",l=>{a.description=l.target.value,d(),u()}),n.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.items.splice(i,1),d(),ie(t,e),u()}),r.appendChild(n)}),t.querySelector("#leBgAdd").addEventListener("click",()=>{e.items.push({figure:"",title:"",description:""}),d(),ie(t,e),u()})}function ne(t,e){e.steps=e.steps||[],t.innerHTML='<p class="hs-config-note">Pasos numerados del proceso.</p><div id="lePsRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="lePsAdd" style="margin-top:10px;">+ Agregar paso</button>';const r=t.querySelector("#lePsRows");e.steps.forEach((a,i)=>{const n=document.createElement("div");n.className="hs-repeat-row",n.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${i+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-title" placeholder="Título del paso" value="${s(a.title)}">
                <textarea class="users-manager-input client-modal-textarea le-description" rows="2" placeholder="Descripción" style="margin-top:6px;">${s(a.description)}</textarea>
                <input type="text" class="users-manager-input le-duration" placeholder="Duración (ej. 1 h)" style="margin-top:6px;" value="${s(a.duration)}">
            `,n.querySelector(".le-title").addEventListener("input",l=>{a.title=l.target.value,d(),$(),u()}),n.querySelector(".le-description").addEventListener("input",l=>{a.description=l.target.value,d(),u()}),n.querySelector(".le-duration").addEventListener("input",l=>{a.duration=l.target.value,d(),u()}),n.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.steps.splice(i,1),d(),ne(t,e),u()}),r.appendChild(n)}),t.querySelector("#lePsAdd").addEventListener("click",()=>{e.steps.push({title:"",description:"",duration:""}),d(),ne(t,e),u()})}function rt(t,e){t.innerHTML=c("Imágenes a mostrar (vacío = toda la galería)",`
            <select class="users-manager-select" id="leGcImages" multiple size="6">${O(e.image_ids,g.images)}</select>
            ${!g.images||!g.images.length?'<p class="hs-config-note" style="margin-top:6px;">Este servicio todavía no tiene imágenes en su galería.</p>':""}
        `),t.querySelector("#leGcImages").addEventListener("change",r=>{e.image_ids=Array.from(r.target.selectedOptions).map(a=>parseInt(a.value,10)),d(),u()})}function at(t,e){t.innerHTML=`
            ${c("Texto descriptivo (opcional)",`<textarea class="users-manager-input client-modal-textarea" id="leRrDescription" rows="2">${s(e.description)}</textarea>`)}
            ${c('Reseñas visibles antes de "Ver más"',`<input type="number" class="users-manager-input" id="leRrPerPage" min="1" max="20" value="${e.reviews_per_page??3}">`)}
            <p class="hs-config-note">Las reseñas se capturan en el panel <strong>Reseñas</strong> del sidebar, y las estadísticas de "Promedio mostrado" en <strong>Información general</strong>. Esta sección solo define dónde aparecen y su texto descriptivo.</p>
        `,t.querySelector("#leRrDescription").addEventListener("input",r=>{e.description=r.target.value,d(),u()}),t.querySelector("#leRrPerPage").addEventListener("input",r=>{e.reviews_per_page=parseInt(r.target.value,10)||3,d(),u()})}function it(t,e){t.innerHTML=`
            ${c("Título",`<input type="text" class="users-manager-input" id="leCtaHeadline" value="${s(e.headline)}" placeholder="¿Listo para cotizar tu servicio?">`)}
            <div id="leCtaHeadlineStyle"></div>
            ${c("Texto de apoyo",`<textarea class="users-manager-input client-modal-textarea" id="leCtaSubtext" rows="2">${s(e.subtext)}</textarea>`)}
            <div id="leCtaSubtextStyle"></div>
            <div class="show-user-divider" style="margin:10px 0;"></div>
            ${c("Texto del botón",`<input type="text" class="users-manager-input" id="leCtaWhatsapp" value="${s(e.whatsapp_text||"Cotizar por WhatsApp")}">`)}
            <p class="hs-config-note">Deja este campo vacío para ocultar el botón de WhatsApp.</p>
            ${c("Imagen de fondo (opcional)",`<select class="users-manager-select" id="leCtaBg"><option value="">Sin imagen</option>${O(e.background_image_id?[e.background_image_id]:[],g.images)}</select>`)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <p class="live-editor-col-title">Botón secundario (opcional)</p>
            <p class="hs-config-note">Se muestra junto al de WhatsApp (o solo, si dejaste ese campo vacío) — útil para un enlace que no sea WhatsApp.</p>
            <div id="leCtaSecondaryBtn"></div>
        `,t.querySelector("#leCtaHeadline").addEventListener("input",r=>{e.headline=r.target.value,d(),u()}),t.querySelector("#leCtaSubtext").addEventListener("input",r=>{e.subtext=r.target.value,d(),u()}),t.querySelector("#leCtaWhatsapp").addEventListener("input",r=>{e.whatsapp_text=r.target.value,d(),u()}),t.querySelector("#leCtaBg").addEventListener("change",r=>{e.background_image_id=r.target.value||null,d(),u()}),e.headline_style=e.headline_style||{},e.subtext_style=e.subtext_style||{},P(t.querySelector("#leCtaHeadlineStyle"),e.headline_style,{tagChoices:["h2","h3"]}),P(t.querySelector("#leCtaSubtextStyle"),e.subtext_style,{}),e.secondary_button=e.secondary_button||{text:"",url:"",style:"outline",color:"#ff6213"},ye(t.querySelector("#leCtaSecondaryBtn"),e.secondary_button,{alignField:!1})}let Se=null;function u(){clearTimeout(Se),Se=setTimeout(Le,400)}async function Le(){try{const e=await(await fetch(g.previewUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({sections:x.filter(r=>r.is_active).map((r,a)=>({...r,sort_order:a}))})})).json();H.srcdoc=e.html??""}catch(t){console.error("Error generando el preview:",t)}}H.addEventListener("load",()=>{try{const t=H.contentDocument;if(!t)return;const e=he(C);if(e&&e.id){const r=t.createElement("style");r.textContent=`[data-section-id="${e.id}"] { outline: 3px solid #ff6213; outline-offset: 2px; cursor: pointer; }`,t.head.appendChild(r)}t.body.addEventListener("click",r=>{const a=r.target.closest("[data-section-id]");if(!a)return;const i=a.getAttribute("data-section-id"),n=x.find(l=>String(l.id)===String(i));n&&(r.preventDefault(),ee(n._uid))},!0)}catch{}});const $e=document.getElementById("leViewportCaption");function nt(){$e&&($e.textContent=V==="mobile"?"Móvil · 375px":"Escritorio · 1440px")}de.addEventListener("click",t=>{const e=t.target.closest("button[data-viewport]");e&&(V=e.dataset.viewport,de.querySelectorAll("button").forEach(r=>r.classList.toggle("is-active",r===e)),H.classList.toggle("is-mobile",V==="mobile"),nt())}),M.addEventListener("click",async()=>{M.disabled=!0;const t=M.textContent;M.textContent="Guardando...",j.textContent="Guardando…",j.className="live-editor-status is-saving";try{if(!(await fetch(g.saveUrl,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({sections:x.map((r,a)=>({...r,sort_order:a}))})})).ok)throw new Error("save request failed");window.location.reload()}catch(e){console.error("Error guardando la página:",e),alert("No se pudieron guardar los cambios. Intenta de nuevo."),M.disabled=!1,M.textContent=t,d()}}),x.length?($(),I()):be(),Le()})();
