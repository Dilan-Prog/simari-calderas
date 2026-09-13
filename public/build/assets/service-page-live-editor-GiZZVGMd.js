(function(){const b=window.__LIVE_EDITOR__;if(!b)return;const j=document.querySelector('meta[name="csrf-token"]').content,P={banner:"Banner",dual_banner:"Banner Doble",product_carousel:"Carrusel de Productos",product_carousel_banner:"Carrusel con Banner",category_grid:"Grid de Categorías",brand_carousel:"Carrusel de Marcas",html_block:"Bloque HTML",faq:"Preguntas Frecuentes",rich_header:"Encabezado enriquecido",content_tabs:"Descripción por secciones",benefits_grid:"Beneficios / características",process_steps:"Proceso / cómo funciona",gallery_carousel:"Galería / carrusel",rating_reviews:"Rating y reseñas",cta_final:"CTA final",button:"Botón"},M={banner:'<rect width="18" height="12" x="3" y="6" rx="2"/><path d="M3 10h18"/>',dual_banner:'<rect width="8" height="14" x="3" y="5" rx="1.5"/><rect width="8" height="14" x="13" y="5" rx="1.5"/>',product_carousel:'<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',product_carousel_banner:'<rect width="18" height="12" x="3" y="6" rx="2"/><circle cx="9" cy="12" r="2"/>',category_grid:'<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/>',brand_carousel:'<path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/>',html_block:'<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',faq:'<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" x2="12.01" y1="17" y2="17"/>',rich_header:'<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="2" x2="22" y1="9" y2="9"/>',content_tabs:'<path d="M21 15V6"/><path d="M18.5 18a2.5 2.5 0 1 0 0-5H8a2 2 0 1 0 0 4h10"/><path d="M3 3v18"/><path d="M14 6H3"/>',benefits_grid:'<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',process_steps:'<path d="M4 17V9a2 2 0 0 1 2-2h2"/><path d="m18 8 4 4-4 4"/><path d="M4 21v-2a2 2 0 0 1 2-2h2"/><path d="M14 3h6v6"/>',gallery_carousel:'<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',rating_reviews:'<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',cta_final:'<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',button:'<rect width="18" height="7" x="3" y="8.5" rx="3.5"/>',default:'<rect width="18" height="18" x="3" y="3" rx="2"/>'},Q={featured:"Destacados",new:"Nuevos",recommended:"Recomendados",category:"Por Categoría",brand:"Por Marca",collection:"Por Colección",manual:"Selección Manual"};function me(e){switch(e){case"banner":return{image_url:"",link_url:"",alt:""};case"dual_banner":return{left:{image_url:"",link_url:"",alt:""},right:{image_url:"",link_url:"",alt:""}};case"product_carousel":return{source:"featured",category_id:null,brand_id:null,collection_id:null,product_ids:[],limit:10};case"product_carousel_banner":return{banner_image_url:"",banner_link_url:"",banner_alt:"",source:"featured",category_id:null,brand_id:null,collection_id:null,product_ids:[],limit:10};case"category_grid":return{category_ids:[]};case"brand_carousel":return{};case"html_block":return{html:""};case"faq":return{description:""};case"rich_header":return{badges:[],whatsapp_text:"Cotizar por WhatsApp",meta_lines:[],background_image_ids:[],price_label:""};case"content_tabs":return{tabs:[]};case"benefits_grid":return{items:[]};case"process_steps":return{steps:[]};case"gallery_carousel":return{image_ids:[]};case"rating_reviews":return{description:"",reviews_per_page:3};case"cta_final":return{headline:"",subtext:"",whatsapp_text:"Cotizar por WhatsApp",background_image_id:null,secondary_button:{text:"",url:"",style:"outline",color:"#ff6213"}};case"button":return{text:"Cotizar ahora",url:"",style:"solid",color:"#ff6213",align:"center"};default:return{}}}let D=0;const Z=()=>"u"+ ++D,x=(b.sections||[]).map(e=>({_uid:Z(),id:e.id??null,type:e.type,title:e.title??"",config:e.config&&typeof e.config=="object"?e.config:{},is_active:e.is_active!==!1}));let $=null,B="empty",ee=!1,O="desktop";const y=Object.assign({name:"",slug:"",short_description:"",price:"",currency:"MXN",show_price:!0,seo_title:"",seo_description:"",is_active:!1,faqs:[]},b.general||{}),N=document.getElementById("leBlocksList"),he=document.getElementById("leAddBlockType"),be=document.getElementById("leAddBlockBtn"),g=document.getElementById("leEditPanel"),I=document.getElementById("leIframe"),A=document.getElementById("leDirtyIndicator"),k=document.getElementById("leSaveBtn"),te=document.getElementById("leViewportToggle"),z=document.getElementById("leGeneralInfoBtn"),le=document.querySelector(".live-editor-heading-row__left h1"),G=document.getElementById("leDeleteModal"),fe=document.getElementById("leDeleteModalTitle"),_e=document.getElementById("leDeleteModalAvatar"),xe=document.getElementById("leDeleteModalCancel"),we=document.getElementById("leDeleteModalConfirm");let H=null;function Se(e){H=e._uid;const t=e.title||P[e.type]||e.type;fe.textContent=t,_e.textContent=t.charAt(0).toUpperCase(),G.classList.add("active")}function U(){H=null,G.classList.remove("active")}xe.addEventListener("click",U),G.addEventListener("click",e=>{e.target===G&&U()}),we.addEventListener("click",()=>{if(!H)return;const e=H,t=x.findIndex(l=>l._uid===e);t!==-1&&x.splice(t,1),$===e&&($=null,T()),U(),s(),L(),o()});function d(e){return String(e??"").replace(/&/g,"&amp;").replace(/"/g,"&quot;").replace(/</g,"&lt;").replace(/>/g,"&gt;")}function Le(e){return String(e??"").toLowerCase().normalize("NFD").replace(/[^\x00-\x7F]/g,"").replace(/[^a-z0-9\s-]/g,"").trim().replace(/\s+/g,"-")}const $e=["#000000","#141516","#374151","#4b5563","#6b7280","#9ca3af","#d1d5db","#f3f4f6","#ffffff","#ef4444","#f97316","#ff6213","#f59e0b","#eab308","#84cc16","#22c55e","#10b981","#14b8a6","#06b6d4","#0ea5e9","#3b82f6","#6366f1","#8b5cf6","#a855f7","#d946ef","#ec4899","#f43f5e","#7c2d12","#78350f","#365314","#134e4a","#1e3a8a","#4c1d95"],ie="emb-custom-colors";function Ee(){try{const e=window.localStorage.getItem(ie),t=e?JSON.parse(e):[];return Array.isArray(t)?t:[]}catch{return[]}}function ae(e){try{window.localStorage.setItem(ie,JSON.stringify(e))}catch{}}function re(e){return/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(e)}function ne(e,t,l){let i=Ee();function r(a,p){return`<button type="button" class="le-color-swatch ${t&&t.toLowerCase()===a.toLowerCase()?"is-active":""}" data-hex="${a}" title="${a}" style="background:${a}">
                ${p?'<span class="le-color-swatch-remove" data-remove="'+a+'" title="Quitar de mis colores">&times;</span>':""}
            </button>`}function n(){e.innerHTML=`
                <div class="le-color-swatches">${$e.map(c=>r(c,!1)).join("")}</div>
                ${i.length?`
                    <div class="le-color-custom-label">Mis colores</div>
                    <div class="le-color-swatches">${i.map(c=>r(c,!0)).join("")}</div>
                `:""}
                <div class="le-color-custom-row">
                    <input type="color" class="le-color-native" value="${re(t)?t:"#ff6213"}">
                    <input type="text" class="users-manager-input le-color-hex" placeholder="#ff6213" value="${d(t||"")}">
                    <button type="button" class="live-editor-btn live-editor-btn--outline le-color-save">Guardar</button>
                </div>
            `,e.querySelectorAll(".le-color-swatch").forEach(c=>{c.addEventListener("click",h=>{h.target.closest(".le-color-swatch-remove")||(l(c.dataset.hex),n())})}),e.querySelectorAll(".le-color-swatch-remove").forEach(c=>{c.addEventListener("click",h=>{h.stopPropagation();const f=c.dataset.remove;i=i.filter(m=>m!==f),ae(i),n()})});const a=e.querySelector(".le-color-native"),p=e.querySelector(".le-color-hex");a.addEventListener("input",()=>{p.value=a.value}),e.querySelector(".le-color-save").addEventListener("click",()=>{let c=p.value.trim();c&&(c.startsWith("#")||(c="#"+c),re(c)&&(i.includes(c)||(i=[...i,c],ae(i)),l(c),n()))})}n()}function C(e,t,l){l=l||{};const i=l.tagChoices||null,r=l.onChange||(()=>{}),n="leStyle"+ ++D;function a(){s(),r(),o()}const p=i?u("Etiqueta de encabezado",`
            <select class="users-manager-select" id="${n}Tag">
                ${i.map(c=>`<option value="${c}" ${(t.heading_tag||i[0])===c?"selected":""}>${c.toUpperCase()}</option>`).join("")}
            </select>
        `):"";e.innerHTML=`
            <p class="live-editor-col-title" style="margin:14px 0 8px;">Estilo del texto</p>
            ${p}
            <div class="live-editor-field-row">
                ${u("Alineación",`
                    <div class="le-align-toggle" id="${n}Align">
                        <button type="button" data-align="left" class="${(t.text_align||"left")==="left"?"is-active":""}" title="Izquierda">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="15" x2="3" y1="12" y2="12"/><line x1="17" x2="3" y1="18" y2="18"/></svg>
                        </button>
                        <button type="button" data-align="center" class="${t.text_align==="center"?"is-active":""}" title="Centro">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="17" x2="7" y1="12" y2="12"/><line x1="19" x2="5" y1="18" y2="18"/></svg>
                        </button>
                        <button type="button" data-align="right" class="${t.text_align==="right"?"is-active":""}" title="Derecha">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="21" x2="9" y1="12" y2="12"/><line x1="21" x2="7" y1="18" y2="18"/></svg>
                        </button>
                    </div>
                `)}
                ${u("Tamaño (px)",`<input type="number" class="users-manager-input" id="${n}Size" min="10" max="72" placeholder="Auto" value="${d(t.font_size||"")}">`)}
            </div>
            ${u("Familia tipográfica",`
                <select class="users-manager-select" id="${n}Family">
                    <option value="" ${t.font_family?"":"selected"}>Predeterminada (Inter)</option>
                    <option value="Inter Tight" ${t.font_family==="Inter Tight"?"selected":""}>Inter Tight</option>
                </select>
            `)}
            ${u("Color de texto",`<div id="${n}Color"></div>`)}
        `,i&&e.querySelector("#"+n+"Tag").addEventListener("change",c=>{t.heading_tag=c.target.value,a()}),e.querySelector("#"+n+"Align").addEventListener("click",c=>{const h=c.target.closest("button[data-align]");h&&(t.text_align=h.dataset.align,e.querySelectorAll("#"+n+"Align button").forEach(f=>f.classList.toggle("is-active",f===h)),a())}),e.querySelector("#"+n+"Size").addEventListener("input",c=>{const h=parseInt(c.target.value,10);t.font_size=Number.isFinite(h)?h:null,a()}),e.querySelector("#"+n+"Family").addEventListener("change",c=>{t.font_family=c.target.value||null,a()}),ne(e.querySelector("#"+n+"Color"),t.text_color,c=>{t.text_color=c,a()})}function se(e,t,l){l=l||{};const i=l.alignField!==!1,r="leBtn"+ ++D;e.innerHTML=`
            ${u("Texto del botón",`<input type="text" class="users-manager-input" id="${r}Text" value="${d(t.text)}" placeholder="Cotizar ahora">`)}
            ${u("Enlace",`
                <div style="display:flex;gap:8px;">
                    <input type="text" class="users-manager-input" id="${r}Url" value="${d(t.url)}" placeholder="https:// o /servicios/..." style="flex:1;">
                    <button type="button" class="live-editor-btn live-editor-btn--outline" id="${r}LinkPick">Elegir enlace</button>
                </div>
            `)}
            <div class="live-editor-field-row">
                ${u("Estilo",`
                    <select class="users-manager-select" id="${r}Style">
                        <option value="solid" ${(t.style||"solid")==="solid"?"selected":""}>Sólido</option>
                        <option value="outline" ${t.style==="outline"?"selected":""}>Contorno</option>
                    </select>
                `)}
                ${i?u("Alineación",`
                    <select class="users-manager-select" id="${r}Align">
                        <option value="left" ${t.align==="left"?"selected":""}>Izquierda</option>
                        <option value="center" ${(t.align||"center")==="center"?"selected":""}>Centro</option>
                        <option value="right" ${t.align==="right"?"selected":""}>Derecha</option>
                    </select>
                `):""}
            </div>
            ${u("Color",`<div id="${r}Color"></div>`)}
        `,e.querySelector("#"+r+"Text").addEventListener("input",a=>{t.text=a.target.value,s(),o()});const n=e.querySelector("#"+r+"Url");n.addEventListener("input",a=>{t.url=a.target.value,s(),o()}),e.querySelector("#"+r+"LinkPick").addEventListener("click",a=>{typeof window.LinkPicker!="function"&&!(window.LinkPicker&&window.LinkPicker.open)||window.LinkPicker.open({anchorEl:a.target,onSelect:p=>{n.value=p,t.url=p,s(),o()}})}),e.querySelector("#"+r+"Style").addEventListener("change",a=>{t.style=a.target.value,s(),o()}),i&&e.querySelector("#"+r+"Align").addEventListener("change",a=>{t.align=a.target.value,s(),o()}),ne(e.querySelector("#"+r+"Color"),t.color||"#ff6213",a=>{t.color=a,s(),o()})}function qe(e,t){se(e,t,{alignField:!0})}function s(){ee||(ee=!0,A.textContent="● Cambios sin guardar",A.className="live-editor-status is-dirty")}function oe(e){return x.find(t=>t._uid===e)||null}let q=null;function L(){if(N.innerHTML="",!x.length){const e=document.createElement("div");e.className="live-editor-blocks-empty",e.textContent="Este servicio todavía no tiene bloques. Agrega uno abajo.",N.appendChild(e);return}x.forEach(e=>{const t=document.createElement("div");t.className="live-editor-block-row",B==="block"&&e._uid===$&&t.classList.add("is-selected"),e.is_active||t.classList.add("is-inactive"),t.draggable=!0,t.dataset.uid=e._uid;const l=M[e.type]||M.default;t.innerHTML=`
                <span class="live-editor-block-drag-handle" title="Arrastrar para reordenar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                </span>
                <span class="live-editor-block-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${l}</svg>
                </span>
                <span class="live-editor-block-info">
                    <span class="live-editor-block-name">${d(e.title||P[e.type]||e.type)}</span>
                    <span class="live-editor-block-type">${d(P[e.type]||e.type)}</span>
                </span>
                <span class="live-editor-block-actions">
                    <button type="button" class="live-editor-toggle-active ${e.is_active?"is-on":""}" title="Activa/Inactiva"></button>
                    <button type="button" class="live-editor-block-delete" title="Eliminar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                    </button>
                </span>
            `,t.addEventListener("click",i=>{i.target.closest(".live-editor-toggle-active")||i.target.closest(".live-editor-block-delete")||W(e._uid)}),t.querySelector(".live-editor-toggle-active").addEventListener("click",i=>{i.stopPropagation(),e.is_active=!e.is_active,s(),L(),o()}),t.querySelector(".live-editor-block-delete").addEventListener("click",i=>{i.stopPropagation(),Se(e)}),t.addEventListener("dragstart",()=>{q=e._uid,t.classList.add("is-dragging")}),t.addEventListener("dragend",()=>{t.classList.remove("is-dragging")}),t.addEventListener("dragover",i=>{i.preventDefault(),!(q===null||q===e._uid)&&t.classList.add("drag-over")}),t.addEventListener("dragleave",()=>{t.classList.remove("drag-over")}),t.addEventListener("drop",i=>{if(i.preventDefault(),t.classList.remove("drag-over"),q===null||q===e._uid)return;const r=x.findIndex(p=>p._uid===q),n=x.findIndex(p=>p._uid===e._uid);if(q=null,r===-1||n===-1)return;const[a]=x.splice(r,1);x.splice(n,0,a),s(),L(),o()}),N.appendChild(t)})}function W(e){$=e,B="block",z.classList.remove("is-selected"),L(),T()}function ce(){$=null,B="general",z.classList.add("is-selected"),L(),T()}z.addEventListener("click",ce),be.addEventListener("click",()=>{const e=he.value,t={_uid:Z(),id:null,type:e,title:"",config:me(e),is_active:!0};x.push(t),s(),W(t._uid),o()});function u(e,t,l){return`<div class="live-editor-field ${l||""}">
            <label>${d(e)}</label>
            ${t}
        </div>`}function F(e,t){const l=(e||[]).map(String);return(t||[]).map(i=>`<option value="${i.id}" ${l.includes(String(i.id))?"selected":""}>${d(i.alt_text||"Imagen #"+i.id)}</option>`).join("")}function ke(){g.innerHTML=`
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
            ${u("Nombre",`<input type="text" class="users-manager-input" id="leGenName" value="${d(y.name)}">`)}
            ${u("Slug (URL)",`
                <div style="display:flex;gap:8px;">
                    <input type="text" class="users-manager-input" id="leGenSlug" value="${d(y.slug)}" style="flex:1;">
                    <button type="button" id="leGenSlugGenerate" class="live-editor-btn live-editor-btn--outline" title="Generar a partir del nombre">Generar</button>
                </div>
            `,"")}
            <div class="live-editor-field-row">
                ${u("Tipo de página",`
                    <select class="users-manager-select" id="leGenPageType">
                        <option value="service" ${y.page_type==="service"?"selected":""}>Servicio (nivel 3)</option>
                        <option value="category" ${y.page_type==="category"?"selected":""}>Categoría (nivel 2)</option>
                        <option value="hub" ${y.page_type==="hub"?"selected":""}>Hub — /servicios (nivel 1)</option>
                    </select>
                `)}
                ${u("Página padre",`
                    <select class="users-manager-select" id="leGenParentId">
                        <option value="">Sin padre (nivel raíz)</option>
                        ${(b.eligibleParents||[]).map(i=>`<option value="${i.id}" ${String(y.parent_id)===String(i.id)?"selected":""}>${d(i.name)} (${i.page_type==="hub"?"Hub":"Categoría"})</option>`).join("")}
                    </select>
                `,"leGenParentField")}
            </div>
            <p class="hs-config-note">/servicios → hub · /servicios/{categoría} → nivel 2 · /servicios/{categoría}/{servicio} → nivel 3. Un servicio sin padre se sirve en /servicio/{slug} (legacy).</p>
            ${u("Descripción corta",`<textarea class="users-manager-input client-modal-textarea" id="leGenShortDesc" rows="2">${d(y.short_description)}</textarea>`)}
            <div class="live-editor-field-row">
                ${u("Precio (opcional)",`<input type="number" step="0.01" min="0" class="users-manager-input" id="leGenPrice" value="${d(y.price)}">`)}
                ${u("Moneda",`<input type="text" class="users-manager-input" id="leGenCurrency" value="${d(y.currency)}" maxlength="10">`)}
            </div>
            ${u("Visibilidad del precio",`
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenShowPrice" ${y.show_price?"checked":""}> Mostrar el precio en el sitio público
                </label>
                <p class="hs-config-note" style="margin-top:4px;">Si lo desmarcas, el servicio se sigue publicando pero sin precio visible — útil para cotizar en privado.</p>
            `)}
            ${u("Estado",`
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenIsActive" ${y.is_active?"checked":""}> Publicado (visible en el sitio público)
                </label>
            `)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            ${u("Título SEO",`<input type="text" class="users-manager-input" id="leGenSeoTitle" value="${d(y.seo_title)}" maxlength="160">`)}
            ${u("Descripción SEO",`<textarea class="users-manager-input client-modal-textarea" id="leGenSeoDesc" rows="2" maxlength="500">${d(y.seo_description)}</textarea>`)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div class="live-editor-field">
                <label>Preguntas frecuentes</label>
                <p class="hs-config-note" style="margin:0 0 8px;">Alimentan el <code>FAQPage</code> de Google y el acordeón visible cuando hay un bloque "Preguntas Frecuentes" en esta página.</p>
                <div id="leGenFaqRows" class="hs-faq-items"></div>
                <button type="button" class="button-secondary size-adjustment" id="leGenFaqAdd" style="margin-top:10px;">+ Agregar pregunta</button>
            </div>
            <div id="leGenErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
            <button type="button" id="leGenSaveBtn" class="live-editor-btn live-editor-btn--solid live-editor-btn--block">Guardar información general</button>
        `,g.querySelector("#leGenSaveBtn").addEventListener("click",Ce),g.querySelector("#leGenFaqAdd").addEventListener("click",()=>{y.faqs=y.faqs||[],y.faqs.push({question:"",answer:""}),s(),V()}),V(),g.querySelector("#leGenSlugGenerate").addEventListener("click",()=>{const i=g.querySelector("#leGenName");g.querySelector("#leGenSlug").value=Le(i.value),s()});const e=g.querySelector("#leGenPageType"),t=g.querySelector(".leGenParentField"),l=()=>{const i=e.value==="hub"||e.value==="category";t&&(t.style.display=i?"none":"")};e.addEventListener("change",l),l()}function V(){y.faqs=y.faqs||[];const e=g.querySelector("#leGenFaqRows");e&&(e.innerHTML="",y.faqs.forEach((t,l)=>{const i=document.createElement("div");i.className="hs-faq-row",i.innerHTML=`
                <div class="hs-faq-row-head">
                    <span class="hs-faq-row-num">${l+1}</span>
                    <div class="hs-faq-row-actions">
                        <button type="button" class="hs-faq-btn hs-faq-remove" title="Eliminar">&times;</button>
                    </div>
                </div>
                <input type="text" class="users-manager-input hs-faq-question" placeholder="Pregunta" value="${d(t.question)}">
                <textarea class="users-manager-input client-modal-textarea hs-faq-answer" rows="2" placeholder="Respuesta">${d(t.answer)}</textarea>
            `,i.querySelector(".hs-faq-question").addEventListener("input",r=>{t.question=r.target.value,s()}),i.querySelector(".hs-faq-answer").addEventListener("input",r=>{t.answer=r.target.value,s()}),i.querySelector(".hs-faq-remove").addEventListener("click",()=>{y.faqs.splice(l,1),s(),V()}),e.appendChild(i)}))}async function Ce(){const e=g.querySelector("#leGenSaveBtn"),t=g.querySelector("#leGenErrors");t.style.display="none",t.innerHTML="",document.querySelectorAll("#leEditPanel .is-invalid").forEach(n=>n.classList.remove("is-invalid"));const l=g.querySelector("#leGenPageType").value,i={name:g.querySelector("#leGenName").value,slug:g.querySelector("#leGenSlug").value,page_type:l,parent_id:l==="hub"?"":g.querySelector("#leGenParentId").value,short_description:g.querySelector("#leGenShortDesc").value,price:g.querySelector("#leGenPrice").value,currency:g.querySelector("#leGenCurrency").value,show_price:g.querySelector("#leGenShowPrice").checked,is_active:g.querySelector("#leGenIsActive").checked,seo_title:g.querySelector("#leGenSeoTitle").value,seo_description:g.querySelector("#leGenSeoDesc").value,faq_items:y.faqs||[]};e.disabled=!0;const r=e.textContent;e.textContent="Guardando...";try{const n=await fetch(b.generalUrl,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":j,Accept:"application/json"},body:JSON.stringify(i)}),a=await n.json();if(n.ok){Object.assign(y,a.servicePage),le&&(le.textContent=y.name),document.title="Editor en vivo - "+y.name+" - Admin";const p=document.getElementById("leBrowserUrl");p&&(p.textContent="equitermindustries.com.mx"+y.public_path);const c=document.getElementById("leViewLiveLink");if(c){const h=window.location.origin;c.href=h+y.public_path}window.showCenterToast&&showCenterToast("Información general guardada."),o()}else if(n.status===422){const p=a.errors||{};t.innerHTML=Object.values(p).flat().map(h=>`<p>${h}</p>`).join(""),t.style.display="block";const c={name:"leGenName",slug:"leGenSlug",page_type:"leGenPageType",parent_id:"leGenParentId",short_description:"leGenShortDesc",price:"leGenPrice",currency:"leGenCurrency",seo_title:"leGenSeoTitle",seo_description:"leGenSeoDesc"};Object.keys(p).forEach(h=>{const f=g.querySelector("#"+(c[h]||""));f&&f.classList.add("is-invalid")})}else throw new Error("save-general-failed")}catch{t.innerHTML="<p>No se pudo guardar. Intenta de nuevo.</p>",t.style.display="block"}finally{e.disabled=!1,e.textContent=r}}function T(){if(B==="general"){ke();return}const e=oe($);if(!e){g.innerHTML='<div class="live-editor-panel-empty">Selecciona un bloque de la izquierda para editarlo aquí, o "Información general" para nombre, slug, precio y SEO.</div>';return}const t=M[e.type]||M.default;if(g.innerHTML=`
            <div class="live-editor-panel-header">
                <span class="live-editor-panel-header-info">
                    <span class="live-editor-block-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${t}</svg>
                    </span>
                    <span>
                        <strong>${d(e.title||P[e.type]||e.type)}</strong>
                        <small>Editando bloque</small>
                    </span>
                </span>
                <button type="button" class="live-editor-panel-close" id="lePanelClose" title="Cerrar">&times;</button>
            </div>
            ${u("Título (opcional, puedes usar {servicio})",`<input type="text" class="users-manager-input" id="leTitle" value="${d(e.title)}" placeholder="Ej: Beneficios del servicio">`)}
            ${de.includes(e.type)?'<div id="leTitleStyle"></div>':""}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div id="leTypeFields"></div>
        `,g.querySelector("#lePanelClose").addEventListener("click",()=>{$=null,L(),T()}),g.querySelector("#leTitle").addEventListener("input",l=>{e.title=l.target.value,s(),L(),o()}),de.includes(e.type)){const l=e.config=e.config||{};l.title_style=l.title_style||{},C(g.querySelector("#leTitleStyle"),l.title_style,{tagChoices:["h2","h3"]})}Te(g.querySelector("#leTypeFields"),e)}const de=["benefits_grid","process_steps","content_tabs","gallery_carousel","faq"];function Te(e,t){const l=t.config=t.config||{};switch(t.type){case"banner":R(e,l,"");break;case"dual_banner":e.innerHTML='<p class="hs-config-subtitle">Banner Izquierdo</p><div id="leDbLeft"></div><p class="hs-config-subtitle">Banner Derecho</p><div id="leDbRight"></div>',l.left=l.left||{image_url:"",link_url:"",alt:""},l.right=l.right||{image_url:"",link_url:"",alt:""},R(e.querySelector("#leDbLeft"),l.left,"Left"),R(e.querySelector("#leDbRight"),l.right,"Right");break;case"product_carousel":ue(e,l,"");break;case"product_carousel_banner":e.innerHTML='<p class="hs-config-subtitle">Banner</p><div id="lePcbBanner"></div><p class="hs-config-subtitle">Productos del carrusel</p><div id="lePcbCarousel"></div>',R(e.querySelector("#lePcbBanner"),{image_url:l.banner_image_url,link_url:l.banner_link_url,alt:l.banner_alt},"PcbBanner",(i,r)=>{i==="image_url"&&(l.banner_image_url=r),i==="link_url"&&(l.banner_link_url=r),i==="alt"&&(l.banner_alt=r)}),ue(e.querySelector("#lePcbCarousel"),l,"Pcb");break;case"category_grid":Me(e,l);break;case"brand_carousel":e.innerHTML='<p class="hs-config-note">Este bloque muestra automáticamente todas las marcas activas. No requiere configuración adicional.</p>';break;case"html_block":Be(e,l);break;case"faq":Ie(e,l);break;case"rich_header":Ae(e,l);break;case"content_tabs":K(e,l);break;case"benefits_grid":J(e,l);break;case"process_steps":X(e,l);break;case"gallery_carousel":Ge(e,l);break;case"rating_reviews":He(e,l);break;case"cta_final":Fe(e,l);break;case"button":qe(e,l);break;default:e.innerHTML='<p class="hs-config-note">Tipo de bloque desconocido.</p>'}}function R(e,t,l,i){const r="leBanner"+l+"Image",n="leBanner"+l+"Link",a="leBanner"+l+"Alt";e.innerHTML=`
            ${u("URL de Imagen",`
                <div class="img-picker-field">
                    <input type="text" class="users-manager-input" id="${r}" value="${d(t.image_url)}" placeholder="https://...">
                    <button type="button" class="img-picker-trigger-btn" data-target="${r}">Seleccionar</button>
                </div>
            `)}
            <div class="live-editor-field-row">
                ${u("URL de Enlace",`<input type="text" class="users-manager-input" id="${n}" value="${d(t.link_url)}" placeholder="/servicio/otro-servicio">`)}
                ${u("Texto Alternativo",`<input type="text" class="users-manager-input" id="${a}" value="${d(t.alt)}">`)}
            </div>
        `;const p=v=>i?i("image_url",v):t.image_url=v,c=v=>i?i("link_url",v):t.link_url=v,h=v=>i?i("alt",v):t.alt=v,f=e.querySelector("#"+r);f.addEventListener("input",()=>{p(f.value),s(),o()}),e.querySelector("#"+n).addEventListener("input",v=>{c(v.target.value),s(),o()}),e.querySelector("#"+a).addEventListener("input",v=>{h(v.target.value),s(),o()}),e.querySelector(".img-picker-trigger-btn").addEventListener("click",()=>{typeof window.openImagePicker=="function"&&window.openImagePicker(r)})}function ue(e,t,l){const i="leSource"+l,r="leLimit"+l,n="leCategory"+l,a="leBrand"+l,p="leCollection"+l,c="leManualWrap"+l,h=Object.keys(Q).map(m=>`<option value="${m}" ${t.source===m?"selected":""}>${Q[m]}</option>`).join("");e.innerHTML=`
            <div class="live-editor-field-row">
                ${u("Origen de Productos",`<select class="users-manager-select" id="${i}">${h}</select>`)}
                ${u("Límite de Productos",`<input type="number" class="users-manager-input" id="${r}" min="1" max="50" value="${t.limit??10}">`)}
            </div>
            <div id="leSourceFields${l}"></div>
        `,e.querySelector("#"+i).addEventListener("change",m=>{t.source=m.target.value,s(),f(),o()}),e.querySelector("#"+r).addEventListener("input",m=>{t.limit=parseInt(m.target.value,10)||10,s(),o()});function f(){const m=e.querySelector("#leSourceFields"+l);t.source==="category"?(m.innerHTML=u("Categoría",`<select class="users-manager-select" id="${n}">
                    <option value="">Selecciona una categoría</option>
                    ${(b.categories||[]).map(v=>`<option value="${v.id}" ${String(t.category_id)===String(v.id)?"selected":""}>${d(v.name)}</option>`).join("")}
                </select>`),m.querySelector("#"+n).addEventListener("change",v=>{t.category_id=v.target.value||null,s(),o()})):t.source==="brand"?(m.innerHTML=u("Marca",`<select class="users-manager-select" id="${a}">
                    <option value="">Selecciona una marca</option>
                    ${(b.brands||[]).map(v=>`<option value="${v.id}" ${String(t.brand_id)===String(v.id)?"selected":""}>${d(v.name)}</option>`).join("")}
                </select>`),m.querySelector("#"+a).addEventListener("change",v=>{t.brand_id=v.target.value||null,s(),o()})):t.source==="collection"?(m.innerHTML=u("Colección",`<select class="users-manager-select" id="${p}">
                    <option value="">Selecciona una colección</option>
                    ${(b.collections||[]).map(v=>`<option value="${v.id}" ${String(t.collection_id)===String(v.id)?"selected":""}>${d(v.name)}</option>`).join("")}
                </select>`),m.querySelector("#"+p).addEventListener("change",v=>{t.collection_id=v.target.value||null,s(),o()})):t.source==="manual"?(m.innerHTML=`<div class="live-editor-field"><label>Productos</label><div id="${c}"></div></div>`,Pe(m.querySelector("#"+c),t.product_ids||[],v=>{t.product_ids=v,s(),o()})):m.innerHTML=""}f()}function Pe(e,t,l){e.innerHTML=`
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
        `;const i=e.querySelector(".hs-product-search__input"),r=e.querySelector(".hs-product-search__dropdown"),n=e.querySelector(".hs-product-search__list"),a=e.querySelector(".hs-product-search__empty"),p=e.querySelector(".hs-product-chips");let c=[],h=null;function f(){p.innerHTML="",c.forEach(w=>{const _=document.createElement("span");_.className="hs-product-chip",_.innerHTML=`<span>${d(w.name)}</span><small>${d(w.sku)}</small><button type="button" aria-label="Quitar">&times;</button>`,_.querySelector("button").addEventListener("click",()=>{c=c.filter(S=>S.id!==w.id),f(),l(c.map(S=>S.id))}),p.appendChild(_)})}function m(){r.style.display="none",n.innerHTML=""}function v(w){n.innerHTML="";const _=w.filter(S=>!c.some(E=>E.id===S.id));if(!_.length){a.style.display="block",n.style.display="none";return}a.style.display="none",n.style.display="block",_.forEach(S=>{const E=document.createElement("li");E.className="hs-product-search__item",E.innerHTML=`<span>${d(S.name)}</span><small>SKU: ${d(S.sku)}</small>`,E.addEventListener("click",()=>{c.push({id:S.id,name:S.name,sku:S.sku}),f(),l(c.map(Y=>Y.id)),i.value="",m()}),n.appendChild(E)})}async function ye(w){try{const _=new URL(b.productsSearchUrl,window.location.origin);Object.entries(w).forEach(([E,Y])=>_.searchParams.set(E,Y));const S=await fetch(_.toString(),{headers:{Accept:"application/json"}});return S.ok?await S.json():[]}catch{return[]}}i.addEventListener("input",function(){const w=this.value.trim();if(clearTimeout(h),w.length<2){m();return}h=setTimeout(async()=>{const _=await ye({q:w});r.style.display="block",v(_)},300)}),document.addEventListener("click",w=>{e.contains(w.target)||m()}),t&&t.length&&ye({ids:t.join(",")}).then(w=>{c=w.map(_=>({id:_.id,name:_.name,sku:_.sku})),f()})}function Me(e,t){e.innerHTML=u("Categorías a mostrar (vacío = todas las principales activas)",`
            <select class="users-manager-select" id="leCategoryIds" multiple size="6">
                ${(b.categories||[]).map(l=>`<option value="${l.id}" ${(t.category_ids||[]).map(String).includes(String(l.id))?"selected":""}>${d(l.name)}</option>`).join("")}
            </select>
        `),e.querySelector("#leCategoryIds").addEventListener("change",l=>{t.category_ids=Array.from(l.target.selectedOptions).map(i=>parseInt(i.value,10)),s(),o()})}function Be(e,t){e.innerHTML=u("Contenido HTML",`<textarea class="users-manager-input client-modal-textarea" id="leHtml" rows="8" placeholder="<div>...</div>">${d(t.html)}</textarea>`),e.querySelector("#leHtml").addEventListener("input",l=>{t.html=l.target.value,s(),o()})}function Ie(e,t){e.innerHTML=`
            ${u("Texto descriptivo (opcional)",`<textarea class="users-manager-input client-modal-textarea" id="leFaqDescription" rows="2">${d(t.description)}</textarea>`)}
            <p class="hs-config-note">Las preguntas y respuestas se capturan en este Servicio (pestaña SEO y Preguntas Frecuentes del formulario clásico). Esta sección solo define el título y el texto descriptivo.</p>
        `,e.querySelector("#leFaqDescription").addEventListener("input",l=>{t.description=l.target.value,s(),o()})}function Ae(e,t){e.innerHTML=`
            ${u("Badges cortos (separados por ·, máx. 3)",`<input type="text" class="users-manager-input" id="leRhBadges" value="${d((t.badges||[]).join(" · "))}" placeholder="Garantía 6 meses · Reporte técnico incluido">`)}
            ${u("Líneas de meta (una por línea)",`<textarea class="users-manager-input client-modal-textarea" id="leRhMetaLines" rows="2">${d((t.meta_lines||[]).join(`
`))}</textarea>`)}
            ${u("Texto del botón",`<input type="text" class="users-manager-input" id="leRhWhatsapp" value="${d(t.whatsapp_text||"Cotizar por WhatsApp")}">`)}
            <p class="hs-config-note">El CTA siempre abre WhatsApp; no existe botón de llamada.</p>
            ${u("Precio mostrado (opcional)",`<input type="text" class="users-manager-input" id="leRhPriceLabel" value="${d(t.price_label)}" placeholder="$8,500 MXN + IVA">`)}
            ${u("Imágenes de fondo (galería del servicio)",`<select class="users-manager-select" id="leRhBgImages" multiple size="4">${F(t.background_image_ids,b.images)}</select>`)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <p class="hs-config-note">El título y la descripción corta se editan en "Información general" — aquí solo se controla su estilo.</p>
            <p class="live-editor-col-title" style="margin:6px 0 0;">Estilo del título (H1, fijo)</p>
            <div id="leRhTitleStyle"></div>
            <p class="live-editor-col-title" style="margin:14px 0 0;">Estilo de la descripción corta</p>
            <div id="leRhSubtitleStyle"></div>
        `,e.querySelector("#leRhBadges").addEventListener("input",l=>{t.badges=l.target.value.split("·").map(i=>i.trim()).filter(Boolean).slice(0,3),s(),o()}),e.querySelector("#leRhMetaLines").addEventListener("input",l=>{t.meta_lines=l.target.value.split(`
`).map(i=>i.trim()).filter(Boolean),s(),o()}),e.querySelector("#leRhWhatsapp").addEventListener("input",l=>{t.whatsapp_text=l.target.value,s(),o()}),e.querySelector("#leRhPriceLabel").addEventListener("input",l=>{t.price_label=l.target.value,s(),o()}),e.querySelector("#leRhBgImages").addEventListener("change",l=>{t.background_image_ids=Array.from(l.target.selectedOptions).map(i=>parseInt(i.value,10)),s(),o()}),t.title_style=t.title_style||{},t.subtitle_style=t.subtitle_style||{},C(e.querySelector("#leRhTitleStyle"),t.title_style,{}),C(e.querySelector("#leRhSubtitleStyle"),t.subtitle_style,{})}function K(e,t){t.tabs=t.tabs||[];let l='<p class="hs-config-note">Cada pestaña se muestra como pestaña horizontal en público.</p><div id="leCtRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="leCtAdd" style="margin-top:10px;">+ Agregar pestaña</button>';e.innerHTML=l;const i=e.querySelector("#leCtRows");t.tabs.forEach((r,n)=>{const a=document.createElement("div");a.className="hs-repeat-row",a.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${n+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-label" placeholder="Título de pestaña" value="${d(r.label)}">
                <input type="text" class="users-manager-input le-subtitle" placeholder="Subtítulo (opcional)" style="margin-top:6px;" value="${d(r.subtitle)}">
                <textarea class="users-manager-input client-modal-textarea le-body" rows="2" placeholder="Párrafo" style="margin-top:6px;">${d(r.body)}</textarea>
                <textarea class="users-manager-input client-modal-textarea le-bullets" rows="2" placeholder="Viñetas, una por línea" style="margin-top:6px;">${d((r.bullets||[]).join(`
`))}</textarea>
                <select class="users-manager-select le-image" style="margin-top:6px;">
                    <option value="">Sin imagen</option>
                    ${F(r.image_id?[r.image_id]:[],b.images)}
                </select>
                <div class="le-tab-style" style="margin-top:6px;"></div>
            `,a.querySelector(".le-label").addEventListener("input",p=>{r.label=p.target.value,s(),L(),o()}),a.querySelector(".le-subtitle").addEventListener("input",p=>{r.subtitle=p.target.value,s(),o()}),a.querySelector(".le-body").addEventListener("input",p=>{r.body=p.target.value,s(),o()}),a.querySelector(".le-bullets").addEventListener("input",p=>{r.bullets=p.target.value.split(`
`).map(c=>c.trim()).filter(Boolean),s(),o()}),a.querySelector(".le-image").addEventListener("change",p=>{r.image_id=p.target.value||null,s(),o()}),a.querySelector(".hs-repeat-remove").addEventListener("click",()=>{t.tabs.splice(n,1),s(),K(e,t),o()}),r.style=r.style||{},C(a.querySelector(".le-tab-style"),r.style,{}),i.appendChild(a)}),e.querySelector("#leCtAdd").addEventListener("click",()=>{t.tabs.push({label:"",subtitle:"",body:"",bullets:[],image_id:null}),s(),K(e,t),o()})}function J(e,t){t.items=t.items||[],e.innerHTML='<p class="hs-config-note">Tarjetas de cifra + título + descripción.</p><div id="leBgRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="leBgAdd" style="margin-top:10px;">+ Agregar beneficio</button>';const l=e.querySelector("#leBgRows");t.items.forEach((i,r)=>{const n=document.createElement("div");n.className="hs-repeat-row",n.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${r+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-figure" placeholder="Cifra (ej. -12%)" value="${d(i.figure)}">
                <input type="text" class="users-manager-input le-title" placeholder="Título" style="margin-top:6px;" value="${d(i.title)}">
                <textarea class="users-manager-input client-modal-textarea le-description" rows="2" placeholder="Descripción corta" style="margin-top:6px;">${d(i.description)}</textarea>
            `,n.querySelector(".le-figure").addEventListener("input",a=>{i.figure=a.target.value,s(),o()}),n.querySelector(".le-title").addEventListener("input",a=>{i.title=a.target.value,s(),L(),o()}),n.querySelector(".le-description").addEventListener("input",a=>{i.description=a.target.value,s(),o()}),n.querySelector(".hs-repeat-remove").addEventListener("click",()=>{t.items.splice(r,1),s(),J(e,t),o()}),l.appendChild(n)}),e.querySelector("#leBgAdd").addEventListener("click",()=>{t.items.push({figure:"",title:"",description:""}),s(),J(e,t),o()})}function X(e,t){t.steps=t.steps||[],e.innerHTML='<p class="hs-config-note">Pasos numerados del proceso.</p><div id="lePsRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="lePsAdd" style="margin-top:10px;">+ Agregar paso</button>';const l=e.querySelector("#lePsRows");t.steps.forEach((i,r)=>{const n=document.createElement("div");n.className="hs-repeat-row",n.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${r+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-title" placeholder="Título del paso" value="${d(i.title)}">
                <textarea class="users-manager-input client-modal-textarea le-description" rows="2" placeholder="Descripción" style="margin-top:6px;">${d(i.description)}</textarea>
                <input type="text" class="users-manager-input le-duration" placeholder="Duración (ej. 1 h)" style="margin-top:6px;" value="${d(i.duration)}">
            `,n.querySelector(".le-title").addEventListener("input",a=>{i.title=a.target.value,s(),L(),o()}),n.querySelector(".le-description").addEventListener("input",a=>{i.description=a.target.value,s(),o()}),n.querySelector(".le-duration").addEventListener("input",a=>{i.duration=a.target.value,s(),o()}),n.querySelector(".hs-repeat-remove").addEventListener("click",()=>{t.steps.splice(r,1),s(),X(e,t),o()}),l.appendChild(n)}),e.querySelector("#lePsAdd").addEventListener("click",()=>{t.steps.push({title:"",description:"",duration:""}),s(),X(e,t),o()})}function Ge(e,t){e.innerHTML=u("Imágenes a mostrar (vacío = toda la galería)",`
            <select class="users-manager-select" id="leGcImages" multiple size="6">${F(t.image_ids,b.images)}</select>
            ${!b.images||!b.images.length?'<p class="hs-config-note" style="margin-top:6px;">Este servicio todavía no tiene imágenes en su galería.</p>':""}
        `),e.querySelector("#leGcImages").addEventListener("change",l=>{t.image_ids=Array.from(l.target.selectedOptions).map(i=>parseInt(i.value,10)),s(),o()})}function He(e,t){e.innerHTML=`
            ${u("Texto descriptivo (opcional)",`<textarea class="users-manager-input client-modal-textarea" id="leRrDescription" rows="2">${d(t.description)}</textarea>`)}
            ${u('Reseñas visibles antes de "Ver más"',`<input type="number" class="users-manager-input" id="leRrPerPage" min="1" max="20" value="${t.reviews_per_page??3}">`)}
            <p class="hs-config-note">Las reseñas se capturan en la pestaña Rating y reseñas del formulario clásico.</p>
        `,e.querySelector("#leRrDescription").addEventListener("input",l=>{t.description=l.target.value,s(),o()}),e.querySelector("#leRrPerPage").addEventListener("input",l=>{t.reviews_per_page=parseInt(l.target.value,10)||3,s(),o()})}function Fe(e,t){e.innerHTML=`
            ${u("Título",`<input type="text" class="users-manager-input" id="leCtaHeadline" value="${d(t.headline)}" placeholder="¿Listo para cotizar tu servicio?">`)}
            <div id="leCtaHeadlineStyle"></div>
            ${u("Texto de apoyo",`<textarea class="users-manager-input client-modal-textarea" id="leCtaSubtext" rows="2">${d(t.subtext)}</textarea>`)}
            <div id="leCtaSubtextStyle"></div>
            <div class="show-user-divider" style="margin:10px 0;"></div>
            ${u("Texto del botón",`<input type="text" class="users-manager-input" id="leCtaWhatsapp" value="${d(t.whatsapp_text||"Cotizar por WhatsApp")}">`)}
            <p class="hs-config-note">Deja este campo vacío para ocultar el botón de WhatsApp.</p>
            ${u("Imagen de fondo (opcional)",`<select class="users-manager-select" id="leCtaBg"><option value="">Sin imagen</option>${F(t.background_image_id?[t.background_image_id]:[],b.images)}</select>`)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <p class="live-editor-col-title">Botón secundario (opcional)</p>
            <p class="hs-config-note">Se muestra junto al de WhatsApp (o solo, si dejaste ese campo vacío) — útil para un enlace que no sea WhatsApp.</p>
            <div id="leCtaSecondaryBtn"></div>
        `,e.querySelector("#leCtaHeadline").addEventListener("input",l=>{t.headline=l.target.value,s(),o()}),e.querySelector("#leCtaSubtext").addEventListener("input",l=>{t.subtext=l.target.value,s(),o()}),e.querySelector("#leCtaWhatsapp").addEventListener("input",l=>{t.whatsapp_text=l.target.value,s(),o()}),e.querySelector("#leCtaBg").addEventListener("change",l=>{t.background_image_id=l.target.value||null,s(),o()}),t.headline_style=t.headline_style||{},t.subtext_style=t.subtext_style||{},C(e.querySelector("#leCtaHeadlineStyle"),t.headline_style,{tagChoices:["h2","h3"]}),C(e.querySelector("#leCtaSubtextStyle"),t.subtext_style,{}),t.secondary_button=t.secondary_button||{text:"",url:"",style:"outline",color:"#ff6213"},se(e.querySelector("#leCtaSecondaryBtn"),t.secondary_button,{alignField:!1})}let pe=null;function o(){clearTimeout(pe),pe=setTimeout(ve,400)}async function ve(){try{const t=await(await fetch(b.previewUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":j,Accept:"application/json"},body:JSON.stringify({sections:x.filter(l=>l.is_active).map((l,i)=>({...l,sort_order:i}))})})).json();I.srcdoc=t.html??""}catch(e){console.error("Error generando el preview:",e)}}I.addEventListener("load",()=>{try{const e=I.contentDocument;if(!e)return;const t=oe($);if(t&&t.id){const l=e.createElement("style");l.textContent=`[data-section-id="${t.id}"] { outline: 3px solid #ff6213; outline-offset: 2px; cursor: pointer; }`,e.head.appendChild(l)}e.body.addEventListener("click",l=>{const i=l.target.closest("[data-section-id]");if(!i)return;const r=i.getAttribute("data-section-id"),n=x.find(a=>String(a.id)===String(r));n&&(l.preventDefault(),W(n._uid))},!0)}catch{}});const ge=document.getElementById("leViewportCaption");function Re(){ge&&(ge.textContent=O==="mobile"?"Móvil · 375px":"Escritorio · 1440px")}te.addEventListener("click",e=>{const t=e.target.closest("button[data-viewport]");t&&(O=t.dataset.viewport,te.querySelectorAll("button").forEach(l=>l.classList.toggle("is-active",l===t)),I.classList.toggle("is-mobile",O==="mobile"),Re())}),k.addEventListener("click",async()=>{k.disabled=!0;const e=k.textContent;k.textContent="Guardando...",A.textContent="Guardando…",A.className="live-editor-status is-saving";try{if(!(await fetch(b.saveUrl,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":j,Accept:"application/json"},body:JSON.stringify({sections:x.map((l,i)=>({...l,sort_order:i}))})})).ok)throw new Error("save request failed");window.location.reload()}catch(t){console.error("Error guardando la página:",t),alert("No se pudieron guardar los cambios. Intenta de nuevo."),k.disabled=!1,k.textContent=e,s()}}),x.length?(L(),T()):ce(),ve()})();
