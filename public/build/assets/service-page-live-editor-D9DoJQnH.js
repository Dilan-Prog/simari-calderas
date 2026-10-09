import{S as ce}from"./sortable.esm-D-EvzYhP.js";(function(){const b=window.__LIVE_EDITOR__;if(!b)return;const E=document.querySelector('meta[name="csrf-token"]').content,I={banner:"Banner",dual_banner:"Banner Doble",product_carousel:"Carrusel de Productos",product_carousel_banner:"Carrusel con Banner",category_grid:"Grid de Categorías",brand_carousel:"Carrusel de Marcas",brand_logos:"Bloque de Marcas",html_block:"Bloque HTML",faq:"Preguntas Frecuentes",rich_header:"Encabezado enriquecido",content_tabs:"Descripción por secciones",benefits_grid:"Beneficios / características",process_steps:"Proceso / cómo funciona",gallery_carousel:"Galería / carrusel",rating_reviews:"Rating y reseñas",cta_final:"CTA final",button:"Botón",table_block:"Tabla"},G={banner:'<rect width="18" height="12" x="3" y="6" rx="2"/><path d="M3 10h18"/>',dual_banner:'<rect width="8" height="14" x="3" y="5" rx="1.5"/><rect width="8" height="14" x="13" y="5" rx="1.5"/>',product_carousel:'<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/>',product_carousel_banner:'<rect width="18" height="12" x="3" y="6" rx="2"/><circle cx="9" cy="12" r="2"/>',category_grid:'<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/>',brand_carousel:'<path d="M12 2 2 7l10 5 10-5-10-5Z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/>',brand_logos:'<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/><path d="M14 17.5h7"/><path d="M17.5 14v7"/>',html_block:'<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',faq:'<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" x2="12.01" y1="17" y2="17"/>',rich_header:'<rect width="20" height="14" x="2" y="3" rx="2"/><line x1="2" x2="22" y1="9" y2="9"/>',content_tabs:'<path d="M21 15V6"/><path d="M18.5 18a2.5 2.5 0 1 0 0-5H8a2 2 0 1 0 0 4h10"/><path d="M3 3v18"/><path d="M14 6H3"/>',benefits_grid:'<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',process_steps:'<path d="M4 17V9a2 2 0 0 1 2-2h2"/><path d="m18 8 4 4-4 4"/><path d="M4 21v-2a2 2 0 0 1 2-2h2"/><path d="M14 3h6v6"/>',gallery_carousel:'<rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',rating_reviews:'<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',cta_final:'<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',button:'<rect width="18" height="7" x="3" y="8.5" rx="3.5"/>',table_block:'<path d="M3 3h18v18H3z"/><path d="M3 9h18"/><path d="M3 15h18"/><path d="M9 3v18"/>',default:'<rect width="18" height="18" x="3" y="3" rx="2"/>'},de={featured:"Destacados",new:"Nuevos",recommended:"Recomendados",category:"Por Categoría",brand:"Por Marca",collection:"Por Colección",manual:"Selección Manual"};function Be(t){switch(t){case"banner":return{image_url:"",link_url:"",alt:"",no_link:!1};case"dual_banner":return{left:{image_url:"",link_url:"",alt:"",no_link:!1},right:{image_url:"",link_url:"",alt:"",no_link:!1}};case"product_carousel":return{source:"featured",category_id:null,brand_id:null,collection_id:null,product_ids:[],limit:10};case"product_carousel_banner":return{banner_image_url:"",banner_link_url:"",banner_alt:"",banner_no_link:!1,source:"featured",category_id:null,brand_id:null,collection_id:null,product_ids:[],limit:10};case"category_grid":return{category_ids:[]};case"brand_carousel":return{};case"brand_logos":return{logos:[],grayscale:!1};case"html_block":return{html:""};case"faq":return{description:""};case"rich_header":return{badges:[],whatsapp_text:"Cotizar por WhatsApp",meta_lines:[],background_image_ids:[],price_label:""};case"content_tabs":return{tabs:[]};case"benefits_grid":return{items:[],layout:"horizontal"};case"process_steps":return{steps:[]};case"gallery_carousel":return{image_ids:[]};case"rating_reviews":return{description:"",reviews_per_page:3};case"cta_final":return{headline:"",subtext:"",whatsapp_text:"Cotizar por WhatsApp",background_image_id:null,secondary_button:{text:"",url:"",style:"outline",color:"#ff6213"}};case"button":return{buttons:[{text:"Cotizar ahora",url:"",style:"solid",color:"#ff6213"}],align:"center"};case"table_block":return{title:"",description:"",headers:[],rows:[],buttons:[],align:"left"};default:return{}}}let U=0;const ue=()=>"u"+ ++U,$=(b.sections||[]).map(t=>({_uid:ue(),id:t.id??null,type:t.type,title:t.title??"",config:t.config&&typeof t.config=="object"?t.config:{},is_active:t.is_active!==!1}));let C=null,q="empty",pe=!1,V="desktop";const v=Object.assign({name:"",slug:"",sort_order:0,short_description:"",price:"",currency:"MXN",show_price:!0,background_color:null,seo_title:"",seo_description:"",canonical_url:"",is_active:!1,faqs:[],rating_average_displayed:"",rating_total_rated:"",rating_recommend_percent:"",rating_punctuality_average:"",rating_recurring_clients:"",rating_since_year:"",rating_distribution:{}},b.general||{}),S=(b.reviews||[]).map(t=>Object.assign({},t)),P=document.getElementById("leBlocksList"),Me=document.getElementById("leAddBlockType"),Ae=document.getElementById("leAddBlockBtn"),_=document.getElementById("leEditPanel"),j=document.getElementById("leIframe"),H=document.getElementById("leDirtyIndicator"),M=document.getElementById("leSaveBtn"),ve=document.getElementById("leViewportToggle"),W=document.getElementById("leGeneralInfoBtn"),K=document.getElementById("leGalleryBtn"),J=document.getElementById("leReviewsBtn"),ge=document.querySelector(".live-editor-heading-row__left h1"),F=document.getElementById("leDeleteModal"),Ie=document.getElementById("leDeleteModalTitle"),Ge=document.getElementById("leDeleteModalAvatar"),Pe=document.getElementById("leDeleteModalCancel"),je=document.getElementById("leDeleteModalConfirm");let X=null;function Q(t,e){X=e,Ie.textContent=t,Ge.textContent=(t||"?").charAt(0).toUpperCase(),F.classList.add("active")}function Y(){X=null,F.classList.remove("active")}Pe.addEventListener("click",Y),F.addEventListener("click",t=>{t.target===F&&Y()}),je.addEventListener("click",()=>{const t=X;Y(),typeof t=="function"&&t()});function s(t){return String(t??"").replace(/&/g,"&amp;").replace(/"/g,"&quot;").replace(/</g,"&lt;").replace(/>/g,"&gt;")}function He(t){return String(t??"").toLowerCase().normalize("NFD").replace(/[^\x00-\x7F]/g,"").replace(/[^a-z0-9\s-]/g,"").trim().replace(/\s+/g,"-")}const Fe=["#000000","#141516","#374151","#4b5563","#6b7280","#9ca3af","#d1d5db","#f3f4f6","#ffffff","#ef4444","#f97316","#ff6213","#f59e0b","#eab308","#84cc16","#22c55e","#10b981","#14b8a6","#06b6d4","#0ea5e9","#3b82f6","#6366f1","#8b5cf6","#a855f7","#d946ef","#ec4899","#f43f5e","#7c2d12","#78350f","#365314","#134e4a","#1e3a8a","#4c1d95"],me="emb-custom-colors";function Ne(){try{const t=window.localStorage.getItem(me),e=t?JSON.parse(t):[];return Array.isArray(e)?e:[]}catch{return[]}}function ye(t){try{window.localStorage.setItem(me,JSON.stringify(t))}catch{}}function he(t){return/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(t)}function Z(t,e,a){let r=Ne();function l(n,p){return`<button type="button" class="le-color-swatch ${e&&e.toLowerCase()===n.toLowerCase()?"is-active":""}" data-hex="${n}" title="${n}" style="background:${n}">
                ${p?'<span class="le-color-swatch-remove" data-remove="'+n+'" title="Quitar de mis colores">&times;</span>':""}
            </button>`}function i(){t.innerHTML=`
                <div class="le-color-swatches">${Fe.map(c=>l(c,!1)).join("")}</div>
                ${r.length?`
                    <div class="le-color-custom-label">Mis colores</div>
                    <div class="le-color-swatches">${r.map(c=>l(c,!0)).join("")}</div>
                `:""}
                <div class="le-color-custom-row">
                    <input type="color" class="le-color-native" value="${he(e)?e:"#ff6213"}">
                    <input type="text" class="users-manager-input le-color-hex" placeholder="#ff6213" value="${s(e||"")}">
                    <button type="button" class="live-editor-btn live-editor-btn--outline le-color-save">Guardar</button>
                </div>
            `,t.querySelectorAll(".le-color-swatch").forEach(c=>{c.addEventListener("click",y=>{y.target.closest(".le-color-swatch-remove")||(a(c.dataset.hex),i())})}),t.querySelectorAll(".le-color-swatch-remove").forEach(c=>{c.addEventListener("click",y=>{y.stopPropagation();const g=c.dataset.remove;r=r.filter(m=>m!==g),ye(r),i()})});const n=t.querySelector(".le-color-native"),p=t.querySelector(".le-color-hex");n.addEventListener("input",()=>{p.value=n.value}),t.querySelector(".le-color-save").addEventListener("click",()=>{let c=p.value.trim();c&&(c.startsWith("#")||(c="#"+c),he(c)&&(r.includes(c)||(r=[...r,c],ye(r)),a(c),i()))})}i()}function A(t,e,a){a=a||{};const r=a.tagChoices||null,l=a.onChange||(()=>{}),i="leStyle"+ ++U;function n(){o(),l(),d()}const p=r?u("Etiqueta de encabezado",`
            <select class="users-manager-select" id="${i}Tag">
                ${r.map(c=>`<option value="${c}" ${(e.heading_tag||r[0])===c?"selected":""}>${c.toUpperCase()}</option>`).join("")}
            </select>
        `):"";t.innerHTML=`
            <p class="live-editor-col-title" style="margin:14px 0 8px;">Estilo del texto</p>
            ${p}
            <div class="live-editor-field-row">
                ${u("Alineación",`
                    <div class="le-align-toggle" id="${i}Align">
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
                ${u("Tamaño (px)",`<input type="number" class="users-manager-input" id="${i}Size" min="10" max="72" placeholder="Auto" value="${s(e.font_size||"")}">`)}
            </div>
            ${u("Familia tipográfica",`
                <select class="users-manager-select" id="${i}Family">
                    <option value="" ${e.font_family?"":"selected"}>Predeterminada (Inter)</option>
                    <option value="Inter Tight" ${e.font_family==="Inter Tight"?"selected":""}>Inter Tight</option>
                </select>
            `)}
            ${u("Color de texto",`<div id="${i}Color"></div>`)}
        `,r&&t.querySelector("#"+i+"Tag").addEventListener("change",c=>{e.heading_tag=c.target.value,n()}),t.querySelector("#"+i+"Align").addEventListener("click",c=>{const y=c.target.closest("button[data-align]");y&&(e.text_align=y.dataset.align,t.querySelectorAll("#"+i+"Align button").forEach(g=>g.classList.toggle("is-active",g===y)),n())}),t.querySelector("#"+i+"Size").addEventListener("input",c=>{const y=parseInt(c.target.value,10);e.font_size=Number.isFinite(y)?y:null,n()}),t.querySelector("#"+i+"Family").addEventListener("change",c=>{e.font_family=c.target.value||null,n()}),Z(t.querySelector("#"+i+"Color"),e.text_color,c=>{e.text_color=c,n()})}function ee(t,e,a){a=a||{};const r=a.alignField!==!1,l="leBtn"+ ++U;t.innerHTML=`
            ${u("Texto del botón",`<input type="text" class="users-manager-input" id="${l}Text" value="${s(e.text)}" placeholder="Cotizar ahora">`)}
            ${u("Enlace",`
                <div style="display:flex;gap:8px;">
                    <input type="text" class="users-manager-input" id="${l}Url" value="${s(e.url)}" placeholder="https:// o /servicios/..." style="flex:1;">
                    <button type="button" class="live-editor-btn live-editor-btn--outline" id="${l}LinkPick">Elegir enlace</button>
                </div>
            `)}
            <div class="live-editor-field-row">
                ${u("Estilo",`
                    <select class="users-manager-select" id="${l}Style">
                        <option value="solid" ${(e.style||"solid")==="solid"?"selected":""}>Sólido</option>
                        <option value="outline" ${e.style==="outline"?"selected":""}>Contorno</option>
                    </select>
                `)}
                ${r?u("Alineación",`
                    <select class="users-manager-select" id="${l}Align">
                        <option value="left" ${e.align==="left"?"selected":""}>Izquierda</option>
                        <option value="center" ${(e.align||"center")==="center"?"selected":""}>Centro</option>
                        <option value="right" ${e.align==="right"?"selected":""}>Derecha</option>
                    </select>
                `):""}
            </div>
            ${u("Color",`<div id="${l}Color"></div>`)}
        `,t.querySelector("#"+l+"Text").addEventListener("input",n=>{e.text=n.target.value,o(),d()});const i=t.querySelector("#"+l+"Url");i.addEventListener("input",n=>{e.url=n.target.value,o(),d()}),t.querySelector("#"+l+"LinkPick").addEventListener("click",n=>{typeof window.LinkPicker!="function"&&!(window.LinkPicker&&window.LinkPicker.open)||window.LinkPicker.open({anchorEl:n.target,onSelect:p=>{i.value=p,e.url=p,o(),d()}})}),t.querySelector("#"+l+"Style").addEventListener("change",n=>{e.style=n.target.value,o(),d()}),r&&t.querySelector("#"+l+"Align").addEventListener("change",n=>{e.align=n.target.value,o(),d()}),Z(t.querySelector("#"+l+"Color"),e.color||"#ff6213",n=>{e.color=n,o(),d()})}function te(t,e){Array.isArray(e.buttons)||(e.buttons=e.text||e.url?[{text:e.text||"",url:e.url||"",style:e.style||"solid",color:e.color||"#ff6213"}]:[{text:"Cotizar ahora",url:"",style:"solid",color:"#ff6213"}]),e.align=e.align||"center",t.innerHTML=`
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
        `,t.querySelector("#leBtnAlign").addEventListener("change",r=>{e.align=r.target.value,o(),d()});const a=t.querySelector("#leBtnRows");e.buttons.forEach((r,l)=>{const i=document.createElement("div");i.className="hs-repeat-row";const n=document.createElement("div");n.className="hs-repeat-row-head",n.innerHTML=`<span class="hs-repeat-row-num">${l+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button>`,i.appendChild(n);const p=document.createElement("div");i.appendChild(p),ee(p,r,{alignField:!1}),n.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.buttons.splice(l,1),o(),te(t,e),d()}),a.appendChild(i)}),t.querySelector("#leBtnAdd").addEventListener("click",()=>{e.buttons.push({text:"",url:"",style:"outline",color:"#ff6213"}),o(),te(t,e),d()})}function R(t,e){e.headers=Array.isArray(e.headers)?e.headers:[],e.rows=Array.isArray(e.rows)?e.rows:[],e.buttons=Array.isArray(e.buttons)?e.buttons:[],e.align=e.align||"left";const a=Math.max(e.headers.length,1);t.innerHTML=`
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
        `,t.querySelector("#leTbDesc").addEventListener("input",n=>{e.description=n.target.value,o(),d()});const r=t.querySelector("#leTbHeaders");e.headers.forEach((n,p)=>{const c=document.createElement("div");c.className="hs-repeat-row",c.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${p+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar columna">&times;</button></div>
                <input type="text" class="users-manager-input le-header" placeholder="Nombre de columna" value="${s(n)}">
            `,c.querySelector(".le-header").addEventListener("input",y=>{e.headers[p]=y.target.value,o(),d()}),c.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.headers.splice(p,1),e.rows.forEach(y=>y.splice(p,1)),o(),R(t,e),d()}),r.appendChild(c)}),t.querySelector("#leTbAddCol").addEventListener("click",()=>{e.headers.push(""),e.rows.forEach(n=>n.push("")),o(),R(t,e),d()});const l=t.querySelector("#leTbRows");e.rows.forEach((n,p)=>{for(;n.length<a;)n.push("");const c=document.createElement("div");c.className="hs-repeat-row";const y=n.map((g,m)=>`
                <input type="text" class="users-manager-input le-cell" data-ci="${m}" placeholder="${s(e.headers[m]||"Celda "+(m+1))}" value="${s(g)}" style="${m>0?"margin-top:6px;":""}">
            `).join("");c.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${p+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar fila">&times;</button></div>
                ${y}
            `,c.querySelectorAll(".le-cell").forEach(g=>{g.addEventListener("input",m=>{n[parseInt(m.target.dataset.ci,10)]=m.target.value,o(),d()})}),c.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.rows.splice(p,1),o(),R(t,e),d()}),l.appendChild(c)}),t.querySelector("#leTbAddRow").addEventListener("click",()=>{e.rows.push(new Array(a).fill("")),o(),R(t,e),d()});const i=t.querySelector("#leTbButtons");e.buttons.forEach((n,p)=>{const c=document.createElement("div");c.className="hs-repeat-row";const y=document.createElement("div");y.className="hs-repeat-row-head",y.innerHTML=`<span class="hs-repeat-row-num">${p+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button>`,c.appendChild(y);const g=document.createElement("div");c.appendChild(g),ee(g,n,{alignField:!1}),y.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.buttons.splice(p,1),o(),R(t,e),d()}),i.appendChild(c)}),t.querySelector("#leTbAddBtn").addEventListener("click",()=>{e.buttons.push({text:"",url:"",style:"outline",color:"#ff6213"}),o(),R(t,e),d()})}function o(){pe||(pe=!0,H.textContent="● Cambios sin guardar",H.className="live-editor-status is-dirty")}function be(t){return $.find(e=>e._uid===t)||null}let we=null;function k(){if(P.innerHTML="",!$.length){const t=document.createElement("div");t.className="live-editor-blocks-empty",t.textContent="Este servicio todavía no tiene bloques. Agrega uno abajo.",P.appendChild(t);return}$.forEach(t=>{const e=document.createElement("div");e.className="live-editor-block-row",q==="block"&&t._uid===C&&e.classList.add("is-selected"),t.is_active||e.classList.add("is-inactive"),e.dataset.uid=t._uid;const a=G[t.type]||G.default;e.innerHTML=`
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
            `,e.addEventListener("click",r=>{r.target.closest(".live-editor-toggle-active")||r.target.closest(".live-editor-block-delete")||ae(t._uid)}),e.querySelector(".live-editor-toggle-active").addEventListener("click",r=>{r.stopPropagation(),t.is_active=!t.is_active,o(),k(),d()}),e.querySelector(".live-editor-block-delete").addEventListener("click",r=>{r.stopPropagation();const l=t.title||I[t.type]||t.type;Q(l,()=>{const i=$.findIndex(n=>n._uid===t._uid);i!==-1&&$.splice(i,1),C===t._uid&&(C=null,B()),o(),k(),d()})}),P.appendChild(e)}),we||(we=new ce(P,{animation:150,handle:".live-editor-block-drag-handle",forceFallback:!0,ghostClass:"live-editor-block-row--ghost",dragClass:"live-editor-block-row--dragging",onEnd(t){if(t.oldIndex===t.newIndex)return;const[e]=$.splice(t.oldIndex,1);$.splice(t.newIndex,0,e),o(),k(),d()}}))}function N(){W.classList.remove("is-selected"),K.classList.remove("is-selected"),J.classList.remove("is-selected")}function ae(t){C=t,q="block",N(),k(),B()}function _e(){C=null,q="general",N(),W.classList.add("is-selected"),k(),B()}function De(){C=null,q="gallery",N(),K.classList.add("is-selected"),k(),B()}function ze(){C=null,q="reviews",N(),J.classList.add("is-selected"),k(),B()}W.addEventListener("click",_e),K.addEventListener("click",De),J.addEventListener("click",ze),Ae.addEventListener("click",()=>{const t=Me.value,e={_uid:ue(),id:null,type:t,title:"",config:Be(t),is_active:!0};$.push(e),o(),ae(e._uid),d()});function u(t,e,a){return`<div class="live-editor-field ${a||""}">
            <label>${s(t)}</label>
            ${e}
        </div>`}function xe(t,e){const a=(t||[]).map(String);return(e||[]).map(r=>`<option value="${r.id}" ${a.includes(String(r.id))?"selected":""}>${s(r.alt_text||"Imagen #"+r.id)}</option>`).join("")}function fe(t,e,a,r,l){const i=new Set((e||[]).map(String)),n=l&&l.clearAllLabel;function p(){if(!a||!a.length){t.innerHTML='<p class="hs-config-note">Esta página todavía no tiene imágenes en su Galería — sube alguna ahí primero.</p>';return}const c=a.map(g=>{const m=i.has(String(g.id));return`
                    <button type="button" class="img-library-item le-img-picker-item ${m?"is-selected":""}" data-id="${g.id}" title="${s(g.alt_text||"Imagen #"+g.id)}">
                        <img src="${s(g.url)}" alt="${s(g.alt_text||"")}">
                        ${m?'<span class="le-img-picker-check">&check;</span>':""}
                    </button>
                `}).join("");t.innerHTML=`
                <div class="img-library-grid">${c}</div>
                ${n?`<button type="button" class="button-secondary size-adjustment le-img-picker-clear" style="margin-top:8px;">${s(n)}</button>`:""}
            `,t.querySelectorAll(".le-img-picker-item").forEach(g=>{g.addEventListener("click",()=>{const m=g.dataset.id;i.has(m)?i.delete(m):i.add(m),p(),r(Array.from(i).map(h=>parseInt(h,10)))})});const y=t.querySelector(".le-img-picker-clear");y&&y.addEventListener("click",()=>{i.clear(),p(),r([])})}p()}function Se(){_.innerHTML=`
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
            ${u("Nombre",`<input type="text" class="users-manager-input" id="leGenName" value="${s(v.name)}">`)}
            ${u("Slug (URL)",`
                <div style="display:flex;gap:8px;">
                    <input type="text" class="users-manager-input" id="leGenSlug" value="${s(v.slug)}" style="flex:1;">
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
                        ${(b.eligibleParents||[]).map(n=>`<option value="${n.id}" ${String(v.parent_id)===String(n.id)?"selected":""}>${s(n.name)} (${n.page_type==="hub"?"Hub":"Categoría"})</option>`).join("")}
                    </select>
                `,"leGenParentField")}
            </div>
            <p class="hs-config-note">/servicios → hub · /servicios/{categoría} → nivel 2 · /servicios/{categoría}/{servicio} → nivel 3. Un servicio sin padre se sirve en /servicio/{slug} (legacy).</p>
            ${u("Orden",`<input type="number" min="0" step="1" class="users-manager-input" id="leGenSortOrder" value="${s(v.sort_order)}">`,"")}
            <p class="hs-config-note" style="margin-top:-8px;">Controla el orden entre páginas con el mismo padre (menor primero) -- se ve reflejado en la lista "Páginas de Servicio" del admin y en el menú/mega-menú del sitio público.</p>
            ${u("Descripción corta",`<textarea class="users-manager-input client-modal-textarea" id="leGenShortDesc" rows="2">${s(v.short_description)}</textarea>`)}
            <div class="live-editor-field-row">
                ${u("Precio (opcional)",`<input type="number" step="0.01" min="0" class="users-manager-input" id="leGenPrice" value="${s(v.price)}">`)}
                ${u("Moneda",`<input type="text" class="users-manager-input" id="leGenCurrency" value="${s(v.currency)}" maxlength="10">`)}
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
            ${u("Título SEO",`<input type="text" class="users-manager-input" id="leGenSeoTitle" value="${s(v.seo_title)}" maxlength="160">`)}
            ${u("Descripción SEO",`<textarea class="users-manager-input client-modal-textarea" id="leGenSeoDesc" rows="2" maxlength="500">${s(v.seo_description)}</textarea>`)}
            ${u("",`
                <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;">
                    <input type="checkbox" id="leGenIsCanonical" ${v.canonical_url?"":"checked"}> Es la URL Canónica de este servicio
                </label>
                <p class="hs-config-note" style="margin-top:4px;">Marcado (normal): Google usa la URL de este mismo servicio. Desmárcalo solo si este servicio es muy parecido a otro y quieres que Google indexe ese otro en su lugar.</p>
            `)}
            <div id="leGenCanonicalUrlWrap" style="${v.canonical_url?"":"display:none;"}">
                ${u("URL Canónica",`<input type="url" class="users-manager-input" id="leGenCanonicalUrl" value="${s(v.canonical_url)}" maxlength="255" placeholder="https://equitermindustries.com.mx/servicio/otro-servicio-similar">`)}
            </div>
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div class="live-editor-field">
                <label>Rating y reseñas — Promedio mostrado</label>
                <p class="hs-config-note" style="margin:0 0 8px;">Contenido curado por el equipo, igual que la FAQ. Estas cifras son de marketing (no tienen por qué coincidir con el número de reseñas capturadas en el panel "Reseñas") y nunca alimentan el marcado SEO — el marcado usa siempre el conteo real.</p>
            </div>
            <div class="live-editor-field-row">
                ${u("Promedio mostrado (0–5)",`<input type="number" step="0.1" min="0" max="5" class="users-manager-input" id="leGenRatingAvg" value="${s(v.rating_average_displayed)}">`)}
                ${u("Total de servicios calificados",`<input type="number" min="0" class="users-manager-input" id="leGenRatingTotal" value="${s(v.rating_total_rated)}">`)}
            </div>
            <div class="live-editor-field-row">
                ${u("% que recomendaría el servicio",`<input type="number" step="0.1" min="0" max="100" class="users-manager-input" id="leGenRatingRecommend" value="${s(v.rating_recommend_percent)}">`)}
                ${u("Puntualidad de cuadrilla (0–5)",`<input type="number" step="0.1" min="0" max="5" class="users-manager-input" id="leGenRatingPunctuality" value="${s(v.rating_punctuality_average)}">`)}
            </div>
            <div class="live-editor-field-row">
                ${u("Clientes recurrentes",`<input type="number" min="0" class="users-manager-input" id="leGenRatingRecurring" value="${s(v.rating_recurring_clients)}">`)}
                ${u("Calificando desde (año, opcional)",`<input type="number" min="2000" max="2100" class="users-manager-input" id="leGenRatingSince" value="${s(v.rating_since_year)}">`)}
            </div>
            ${u("Distribución por estrella",`
                <div class="live-editor-field-row" style="grid-template-columns:repeat(5,1fr);">
                    ${[5,4,3,2,1].map(n=>`
                        <div>
                            <label style="font-size:11px;color:#6b7280;display:block;margin-bottom:2px;">${n} ★</label>
                            <input type="number" min="0" class="users-manager-input le-gen-rating-dist" data-star="${n}" value="${s((v.rating_distribution||{})[n]??(v.rating_distribution||{})[String(n)]??"")}">
                        </div>
                    `).join("")}
                </div>
            `)}
            <div id="leGenErrors" class="user-manager-errors" style="display:none;margin-bottom:10px;"></div>
            <button type="button" id="leGenSaveBtn" class="live-editor-btn live-editor-btn--solid live-editor-btn--block">Guardar información general</button>
        `,_.querySelector("#leGenSaveBtn").addEventListener("click",()=>D()),_.querySelector("#leGenSlugGenerate").addEventListener("click",()=>{const n=_.querySelector("#leGenName");_.querySelector("#leGenSlug").value=He(n.value),o()});const t=_.querySelector("#leGenIsCanonical"),e=_.querySelector("#leGenCanonicalUrlWrap");t.addEventListener("change",()=>{e.style.display=t.checked?"none":""});const a=_.querySelector("#leGenPageType"),r=_.querySelector(".leGenParentField"),l=()=>{const n=a.value==="hub"||a.value==="category";r&&(r.style.display=n?"none":"")};a.addEventListener("change",l),l();const i=_.querySelector("#leGenBgColorClear");Z(_.querySelector("#leGenBgColorPicker"),v.background_color||"",n=>{v.background_color=n,i.disabled=!1,o()}),i.addEventListener("click",()=>{v.background_color=null,i.disabled=!0,Se(),o()})}function re(t){v.faqs=v.faqs||[];const e=t;e&&(e.innerHTML="",v.faqs.forEach((a,r)=>{const l=document.createElement("div");l.className="hs-faq-row",l.innerHTML=`
                <div class="hs-faq-row-head">
                    <span class="hs-faq-row-num">${r+1}</span>
                    <div class="hs-faq-row-actions">
                        <button type="button" class="hs-faq-btn hs-faq-remove" title="Eliminar">&times;</button>
                    </div>
                </div>
                <input type="text" class="users-manager-input hs-faq-question" placeholder="Pregunta" value="${s(a.question)}">
                <textarea class="users-manager-input client-modal-textarea hs-faq-answer" rows="2" placeholder="Respuesta">${s(a.answer)}</textarea>
            `,l.querySelector(".hs-faq-question").addEventListener("input",i=>{a.question=i.target.value,o()}),l.querySelector(".hs-faq-answer").addEventListener("input",i=>{a.answer=i.target.value,o()}),l.querySelector(".hs-faq-remove").addEventListener("click",()=>{v.faqs.splice(r,1),o(),re(t)}),e.appendChild(l)}))}async function D(t){t=t||{};const e=_.querySelector("#"+(t.btnId||"leGenSaveBtn")),a=_.querySelector("#"+(t.errorsBoxId||"leGenErrors"));a.style.display="none",a.innerHTML="",document.querySelectorAll("#leEditPanel .is-invalid").forEach(g=>g.classList.remove("is-invalid"));const r=g=>_.querySelector("#"+g),l=(g,m)=>{const h=r(g);return h?h.value:m},i=(g,m)=>{const h=r(g);return h?h.checked:m},n=l("leGenPageType",v.page_type),p=_.querySelectorAll(".le-gen-rating-dist"),c={name:l("leGenName",v.name),slug:l("leGenSlug",v.slug),page_type:n,parent_id:n==="hub"?"":l("leGenParentId",v.parent_id||""),sort_order:l("leGenSortOrder",v.sort_order),short_description:l("leGenShortDesc",v.short_description),price:l("leGenPrice",v.price),currency:l("leGenCurrency",v.currency),show_price:i("leGenShowPrice",v.show_price),background_color:v.background_color||"",is_active:i("leGenIsActive",v.is_active),seo_title:l("leGenSeoTitle",v.seo_title),seo_description:l("leGenSeoDesc",v.seo_description),is_canonical:i("leGenIsCanonical",!v.canonical_url),canonical_url:l("leGenCanonicalUrl",v.canonical_url),faq_items:v.faqs||[],rating_average_displayed:l("leGenRatingAvg",v.rating_average_displayed),rating_total_rated:l("leGenRatingTotal",v.rating_total_rated),rating_recommend_percent:l("leGenRatingRecommend",v.rating_recommend_percent),rating_punctuality_average:l("leGenRatingPunctuality",v.rating_punctuality_average),rating_recurring_clients:l("leGenRatingRecurring",v.rating_recurring_clients),rating_since_year:l("leGenRatingSince",v.rating_since_year),rating_distribution:p.length?Array.from(p).reduce((g,m)=>(g[m.dataset.star]=m.value,g),{}):v.rating_distribution||{}};e.disabled=!0;const y=e.textContent;e.textContent="Guardando...";try{const g=await fetch(b.generalUrl,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify(c)}),m=await g.json();if(g.ok){Object.assign(v,m.servicePage),ge&&(ge.textContent=v.name),document.title="Editor en vivo - "+v.name+" - Admin";const h=document.getElementById("leBrowserUrl");h&&(h.textContent="equitermindustries.com.mx"+v.public_path);const L=document.getElementById("leViewLiveLink");if(L){const x=window.location.origin;L.href=x+v.public_path}window.showCenterToast&&showCenterToast("Información general guardada."),d()}else if(g.status===422){const h=m.errors||{};a.innerHTML=Object.values(h).flat().map(x=>`<p>${x}</p>`).join(""),a.style.display="block";const L={name:"leGenName",slug:"leGenSlug",page_type:"leGenPageType",parent_id:"leGenParentId",short_description:"leGenShortDesc",price:"leGenPrice",currency:"leGenCurrency",seo_title:"leGenSeoTitle",seo_description:"leGenSeoDesc",canonical_url:"leGenCanonicalUrl",rating_average_displayed:"leGenRatingAvg",rating_total_rated:"leGenRatingTotal",rating_recommend_percent:"leGenRatingRecommend",rating_punctuality_average:"leGenRatingPunctuality",rating_recurring_clients:"leGenRatingRecurring",rating_since_year:"leGenRatingSince"};Object.keys(h).forEach(x=>{const f=_.querySelector("#"+(L[x]||""));f&&f.classList.add("is-invalid")})}else throw new Error("save-general-failed")}catch{a.innerHTML="<p>No se pudo guardar. Intenta de nuevo.</p>",a.style.display="block"}finally{e.disabled=!1,e.textContent=y}}function Oe(){_.innerHTML=`
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
        `,z()}function z(){const t=_.querySelector("#leGalleryGrid");if(!t)return;t.innerHTML="",(b.images||[]).forEach((a,r)=>{const l=document.createElement("div");l.className="service-gallery-item",l.dataset.id=a.id,l.innerHTML=`
                <div class="service-gallery-item__drag" title="Arrastrar para reordenar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                </div>
                ${r===0?'<span class="service-gallery-item__badge">PORTADA</span>':""}
                <button type="button" class="service-gallery-item__remove" title="Quitar">&times;</button>
                <img src="${a.url}" alt="${s(a.alt_text||"")}">
                <input type="text" class="users-manager-input service-gallery-item__alt" placeholder="Texto alternativo" value="${s(a.alt_text||"")}">
            `,l.querySelector(".service-gallery-item__remove").addEventListener("click",()=>{Q(a.alt_text||"Imagen #"+a.id,()=>Ve(a.id))});let i=null;l.querySelector(".service-gallery-item__alt").addEventListener("input",n=>{a.alt_text=n.target.value,clearTimeout(i),i=setTimeout(()=>We(a.id,a.alt_text),500)}),t.appendChild(l)});const e=document.createElement("div");e.className="service-gallery-item service-gallery-item--add",e.id="leGalleryAddTile",e.innerHTML=`
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            <span>Arrastra o selecciona</span>
        `,e.addEventListener("click",()=>{typeof window.openImagePicker=="function"&&window.openImagePicker(null,{onSelect:Ue})}),t.appendChild(e),new ce(t,{animation:150,handle:".service-gallery-item__drag",filter:".service-gallery-item--add",forceFallback:!0,ghostClass:"service-gallery-item--ghost",dragClass:"service-gallery-item--dragging",onEnd(a){if(a.oldIndex===a.newIndex)return;const[r]=b.images.splice(a.oldIndex,1);b.images.splice(a.newIndex,0,r),z(),Ke()}})}async function Ue(t){try{const e=await fetch(b.imagesStoreUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({image_url:t,alt_text:""})}),a=await e.json();e.ok?(b.images.push({id:a.image.id,url:a.image.url,alt_text:a.image.alt_text}),z(),window.showCenterToast&&showCenterToast("Imagen agregada."),d()):window.showCenterToast&&showCenterToast("No se pudo agregar la imagen.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al agregar la imagen.","error")}}async function Ve(t){try{if((await fetch(b.imageDestroyUrlTemplate.replace("__IMAGE_ID__",t),{method:"DELETE",headers:{"X-CSRF-TOKEN":E,Accept:"application/json"}})).ok){const a=b.images.findIndex(r=>String(r.id)===String(t));a!==-1&&b.images.splice(a,1),z(),window.showCenterToast&&showCenterToast("Imagen eliminada."),d()}else window.showCenterToast&&showCenterToast("No se pudo eliminar la imagen.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al eliminar la imagen.","error")}}async function We(t,e){try{await fetch(b.imageUpdateUrlTemplate.replace("__IMAGE_ID__",t),{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({alt_text:e})})}catch{}}async function Ke(){const t=b.images.map(e=>e.id);try{(await fetch(b.imagesReorderUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({order:t})})).ok?(window.showCenterToast&&showCenterToast("Orden de galería actualizado."),d()):window.showCenterToast&&showCenterToast("No se pudo guardar el nuevo orden de la galería.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al reordenar la galería.","error")}}function ne(){const t=S.filter(e=>e.is_visible).length;_.innerHTML=`
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
        `,ke(),_.querySelector("#leReviewAddBtn").addEventListener("click",()=>$e(null))}function ke(){const t=_.querySelector("#leReviewsList");if(t){if(t.innerHTML="",!S.length){t.innerHTML='<p class="hs-config-note">Todavía no hay reseñas capturadas para este servicio.</p>';return}S.forEach(e=>{const a=document.createElement("div");a.className="service-review-row",a.dataset.id=e.id;const r=e.comment||"";a.innerHTML=`
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
            `,a.querySelector(".edit").addEventListener("click",()=>$e(e)),a.querySelector(".delete").addEventListener("click",()=>{Q(e.customer_name||"Reseña",()=>Xe(e.id))}),t.appendChild(a)}),new ce(t,{animation:150,handle:".service-review-row__drag",forceFallback:!0,ghostClass:"service-review-row--ghost",dragClass:"service-review-row--dragging",onEnd(e){if(e.oldIndex===e.newIndex)return;const[a]=S.splice(e.oldIndex,1);S.splice(e.newIndex,0,a),ke(),Qe()}})}}function $e(t){const e=!!t,a=_.querySelector("#leReviewFormWrap");if(!a)return;const r=e?Object.assign({},t):{customer_name:"",customer_role:"",customer_company:"",customer_city:"",customer_state:"",review_date:"",rating:5,comment:"",categories:[],is_verified:!1,is_visible:!0,business_response:"",business_response_date:""};a.innerHTML=`
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
                    ${Object.entries(b.reviewCategories||{}).map(([n,p])=>`
                        <label style="display:inline-flex;align-items:center;gap:4px;border:1px solid #d1d5db;border-radius:999px;padding:4px 10px;font-size:12.5px;cursor:pointer;font-weight:400;">
                            <input type="checkbox" class="le-rev-category" value="${s(n)}" ${(r.categories||[]).includes(n)?"checked":""}> ${s(p)}
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
        `;const l=a.querySelector("#leRevStars");function i(n){a.querySelector("#leRevRating").value=n,l.innerHTML="";for(let p=1;p<=5;p++){const c=document.createElement("button");c.type="button",c.className="service-review-star"+(p<=n?" is-active":""),c.textContent="★",c.addEventListener("click",()=>i(p)),l.appendChild(c)}}i(r.rating||5),a.querySelector("#leRevComment").addEventListener("input",function(){a.querySelector("#leRevCommentCount").textContent=this.value.length}),a.querySelector("#leRevCancel").addEventListener("click",()=>{a.innerHTML=""}),a.querySelector("#leRevSave").addEventListener("click",()=>Je(e?t.id:null,a)),a.scrollIntoView({behavior:"smooth",block:"nearest"})}async function Je(t,e){const a=e.querySelector("#leRevErrors");a.style.display="none",a.innerHTML="";const r={customer_name:e.querySelector("#leRevName").value,customer_role:e.querySelector("#leRevRole").value||null,customer_company:e.querySelector("#leRevCompany").value||null,customer_city:e.querySelector("#leRevCity").value||null,customer_state:e.querySelector("#leRevState").value||null,review_date:e.querySelector("#leRevDate").value||null,rating:parseInt(e.querySelector("#leRevRating").value,10)||5,comment:e.querySelector("#leRevComment").value,categories:Array.from(e.querySelectorAll(".le-rev-category:checked")).map(n=>n.value),is_verified:e.querySelector("#leRevVerified").checked,is_visible:e.querySelector("#leRevVisible").checked,business_response:e.querySelector("#leRevResponse").value||null,business_response_date:e.querySelector("#leRevResponseDate").value||null},l=e.querySelector("#leRevSave");l.disabled=!0;const i=l.textContent;l.textContent="Guardando...";try{const n=t?b.reviewUpdateUrlTemplate.replace("__REVIEW_ID__",t):b.reviewsStoreUrl,p=await fetch(n,{method:t?"PUT":"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify(r)}),c=await p.json();if(p.ok){if(t){const y=S.findIndex(g=>String(g.id)===String(t));y!==-1&&(S[y]=Object.assign({},S[y],c.review))}else S.push(c.review);ne(),window.showCenterToast&&showCenterToast(t?"Reseña actualizada.":"Reseña creada."),d()}else if(p.status===422){const y=c.errors||{};a.innerHTML=Object.values(y).flat().map(g=>`<p>${g}</p>`).join(""),a.style.display="block",l.disabled=!1,l.textContent=i}else throw new Error("save-review-failed")}catch{a.innerHTML="<p>No se pudo guardar. Intenta de nuevo.</p>",a.style.display="block",l.disabled=!1,l.textContent=i}}async function Xe(t){try{if((await fetch(b.reviewDestroyUrlTemplate.replace("__REVIEW_ID__",t),{method:"DELETE",headers:{"X-CSRF-TOKEN":E,Accept:"application/json"}})).ok){const a=S.findIndex(r=>String(r.id)===String(t));a!==-1&&S.splice(a,1),ne(),window.showCenterToast&&showCenterToast("Reseña eliminada."),d()}else window.showCenterToast&&showCenterToast("No se pudo eliminar la reseña.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al eliminar la reseña.","error")}}async function Qe(){const t=S.map(e=>e.id);try{(await fetch(b.reviewsReorderUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({order:t})})).ok?window.showCenterToast&&showCenterToast("Orden de reseñas actualizado."):window.showCenterToast&&showCenterToast("No se pudo guardar el nuevo orden.","error")}catch{window.showCenterToast&&showCenterToast("Error de conexión al guardar el orden.","error")}}function B(){if(q==="general"){Se();return}if(q==="gallery"){Oe();return}if(q==="reviews"){ne();return}const t=be(C);if(!t){_.innerHTML='<div class="live-editor-panel-empty">Selecciona un bloque de la izquierda para editarlo aquí, o "Información general" para nombre, slug, precio y SEO.</div>';return}const e=G[t.type]||G.default;if(_.innerHTML=`
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
            ${Ee.includes(t.type)?'<div id="leTitleStyle"></div>':""}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <div id="leTypeFields"></div>
        `,_.querySelector("#lePanelClose").addEventListener("click",()=>{C=null,k(),B()}),_.querySelector("#leTitle").addEventListener("input",a=>{t.title=a.target.value,o(),k(),d()}),Ee.includes(t.type)){const a=t.config=t.config||{};a.title_style=a.title_style||{},A(_.querySelector("#leTitleStyle"),a.title_style,{tagChoices:["h2","h3"]})}Ye(_.querySelector("#leTypeFields"),t)}const Ee=["benefits_grid","process_steps","content_tabs","gallery_carousel","faq","brand_logos"];function Ye(t,e){const a=e.config=e.config||{};switch(e.type){case"banner":O(t,a,"",null,{recommendedSize:"1200×400px, horizontal (ancho completo de la página)"});break;case"dual_banner":t.innerHTML='<p class="hs-config-subtitle">Banner Izquierdo</p><div id="leDbLeft"></div><p class="hs-config-subtitle">Banner Derecho</p><div id="leDbRight"></div>',a.left=a.left||{image_url:"",link_url:"",alt:"",no_link:!1},a.right=a.right||{image_url:"",link_url:"",alt:"",no_link:!1},O(t.querySelector("#leDbLeft"),a.left,"Left",null,{recommendedSize:"800×500px cada uno (mismo tamaño en ambos para que se vean parejos)"}),O(t.querySelector("#leDbRight"),a.right,"Right",null,{recommendedSize:"800×500px cada uno (mismo tamaño en ambos para que se vean parejos)"});break;case"product_carousel":Ce(t,a,"");break;case"product_carousel_banner":t.innerHTML='<p class="hs-config-subtitle">Banner</p><div id="lePcbBanner"></div><p class="hs-config-subtitle">Productos del carrusel</p><div id="lePcbCarousel"></div>',O(t.querySelector("#lePcbBanner"),{image_url:a.banner_image_url,link_url:a.banner_link_url,alt:a.banner_alt,no_link:a.banner_no_link},"PcbBanner",(r,l)=>{r==="image_url"&&(a.banner_image_url=l),r==="link_url"&&(a.banner_link_url=l),r==="alt"&&(a.banner_alt=l),r==="no_link"&&(a.banner_no_link=l)},{recommendedSize:"440×640px, vertical (se recorta para llenar el espacio junto al carrusel)"}),Ce(t.querySelector("#lePcbCarousel"),a,"Pcb");break;case"category_grid":et(t,a);break;case"brand_carousel":t.innerHTML='<p class="hs-config-note">Este bloque muestra automáticamente todas las marcas activas. No requiere configuración adicional.</p>';break;case"brand_logos":Le(t,a);break;case"html_block":tt(t,a);break;case"faq":at(t,a);break;case"rich_header":rt(t,a);break;case"content_tabs":le(t,a);break;case"benefits_grid":ie(t,a);break;case"process_steps":se(t,a);break;case"gallery_carousel":nt(t,a);break;case"rating_reviews":lt(t,a);break;case"cta_final":it(t,a);break;case"button":te(t,a);break;case"table_block":R(t,a);break;default:t.innerHTML='<p class="hs-config-note">Tipo de bloque desconocido.</p>'}}function O(t,e,a,r,l){const i="leBanner"+a+"Image",n="leBanner"+a+"Link",p="leBanner"+a+"Alt",c=l&&l.recommendedSize;t.innerHTML=`
            ${u("URL de Imagen",`
                <div class="img-picker-field">
                    <input type="text" class="users-manager-input" id="${i}" value="${s(e.image_url)}" placeholder="https://...">
                    <button type="button" class="img-picker-trigger-btn" data-target="${i}">Seleccionar</button>
                </div>
                ${c?`<p class="hs-config-note">Medida recomendada: ${s(c)}.</p>`:""}
            `)}
            <div class="live-editor-field-row">
                ${u("URL de Enlace",`<input type="text" class="users-manager-input" id="${n}" value="${s(e.link_url)}" placeholder="/servicio/otro-servicio" ${e.no_link?"disabled":""}>`)}
                ${u("Texto Alternativo",`<input type="text" class="users-manager-input" id="${p}" value="${s(e.alt)}">`)}
            </div>
            <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;margin-top:-8px;">
                <input type="checkbox" id="${n}NoLink" ${e.no_link?"checked":""}> Sin enlace (la imagen no redirige a ningún lado)
            </label>
        `;const y=w=>r?r("image_url",w):e.image_url=w,g=w=>r?r("link_url",w):e.link_url=w,m=w=>r?r("alt",w):e.alt=w,h=w=>r?r("no_link",w):e.no_link=w,L=t.querySelector("#"+i);L.addEventListener("input",()=>{y(L.value),o(),d()});const x=t.querySelector("#"+n);x.addEventListener("input",w=>{g(w.target.value),o(),d()}),t.querySelector("#"+p).addEventListener("input",w=>{m(w.target.value),o(),d()}),t.querySelector("#"+n+"NoLink").addEventListener("change",w=>{h(w.target.checked),x.disabled=w.target.checked,o(),d()}),t.querySelector(".img-picker-trigger-btn").addEventListener("click",()=>{typeof window.openImagePicker=="function"&&window.openImagePicker(i)})}function Ce(t,e,a){const r="leSource"+a,l="leLimit"+a,i="leCategory"+a,n="leBrand"+a,p="leCollection"+a,c="leManualWrap"+a,y=Object.keys(de).map(m=>`<option value="${m}" ${e.source===m?"selected":""}>${de[m]}</option>`).join("");t.innerHTML=`
            <div class="live-editor-field-row">
                ${u("Origen de Productos",`<select class="users-manager-select" id="${r}">${y}</select>`)}
                ${u("Límite de Productos",`<input type="number" class="users-manager-input" id="${l}" min="1" max="50" value="${e.limit??10}">`)}
            </div>
            <div id="leSourceFields${a}"></div>
        `,t.querySelector("#"+r).addEventListener("change",m=>{e.source=m.target.value,o(),g(),d()}),t.querySelector("#"+l).addEventListener("input",m=>{e.limit=parseInt(m.target.value,10)||10,o(),d()});function g(){const m=t.querySelector("#leSourceFields"+a);e.source==="category"?(m.innerHTML=u("Categoría",`<select class="users-manager-select" id="${i}">
                    <option value="">Selecciona una categoría</option>
                    ${(b.categories||[]).map(h=>`<option value="${h.id}" ${String(e.category_id)===String(h.id)?"selected":""}>${s(h.name)}</option>`).join("")}
                </select>`),m.querySelector("#"+i).addEventListener("change",h=>{e.category_id=h.target.value||null,o(),d()})):e.source==="brand"?(m.innerHTML=u("Marca",`<select class="users-manager-select" id="${n}">
                    <option value="">Selecciona una marca</option>
                    ${(b.brands||[]).map(h=>`<option value="${h.id}" ${String(e.brand_id)===String(h.id)?"selected":""}>${s(h.name)}</option>`).join("")}
                </select>`),m.querySelector("#"+n).addEventListener("change",h=>{e.brand_id=h.target.value||null,o(),d()})):e.source==="collection"?(m.innerHTML=u("Colección",`<select class="users-manager-select" id="${p}">
                    <option value="">Selecciona una colección</option>
                    ${(b.collections||[]).map(h=>`<option value="${h.id}" ${String(e.collection_id)===String(h.id)?"selected":""}>${s(h.name)}</option>`).join("")}
                </select>`),m.querySelector("#"+p).addEventListener("change",h=>{e.collection_id=h.target.value||null,o(),d()})):e.source==="manual"?(m.innerHTML=`<div class="live-editor-field"><label>Productos</label><div id="${c}"></div></div>`,Ze(m.querySelector("#"+c),e.product_ids||[],h=>{e.product_ids=h,o(),d()})):m.innerHTML=""}g()}function Ze(t,e,a){t.innerHTML=`
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
        `;const r=t.querySelector(".hs-product-search__input"),l=t.querySelector(".hs-product-search__dropdown"),i=t.querySelector(".hs-product-search__list"),n=t.querySelector(".hs-product-search__empty"),p=t.querySelector(".hs-product-chips");let c=[],y=null;function g(){p.innerHTML="",c.forEach(x=>{const f=document.createElement("span");f.className="hs-product-chip",f.innerHTML=`<span>${s(x.name)}</span><small>${s(x.sku)}</small><button type="button" aria-label="Quitar">&times;</button>`,f.querySelector("button").addEventListener("click",()=>{c=c.filter(w=>w.id!==x.id),g(),a(c.map(w=>w.id))}),p.appendChild(f)})}function m(){l.style.display="none",i.innerHTML=""}function h(x){i.innerHTML="";const f=x.filter(w=>!c.some(T=>T.id===w.id));if(!f.length){n.style.display="block",i.style.display="none";return}n.style.display="none",i.style.display="block",f.forEach(w=>{const T=document.createElement("li");T.className="hs-product-search__item",T.innerHTML=`<span>${s(w.name)}</span><small>SKU: ${s(w.sku)}</small>`,T.addEventListener("click",()=>{c.push({id:w.id,name:w.name,sku:w.sku}),g(),a(c.map(oe=>oe.id)),r.value="",m()}),i.appendChild(T)})}async function L(x){try{const f=new URL(b.productsSearchUrl,window.location.origin);Object.entries(x).forEach(([T,oe])=>f.searchParams.set(T,oe));const w=await fetch(f.toString(),{headers:{Accept:"application/json"}});return w.ok?await w.json():[]}catch{return[]}}r.addEventListener("input",function(){const x=this.value.trim();if(clearTimeout(y),x.length<2){m();return}y=setTimeout(async()=>{const f=await L({q:x});l.style.display="block",h(f)},300)}),document.addEventListener("click",x=>{t.contains(x.target)||m()}),e&&e.length&&L({ids:e.join(",")}).then(x=>{c=x.map(f=>({id:f.id,name:f.name,sku:f.sku})),g()})}function et(t,e){t.innerHTML=u("Categorías a mostrar (vacío = todas las principales activas)",`
            <select class="users-manager-select" id="leCategoryIds" multiple size="6">
                ${(b.categories||[]).map(a=>`<option value="${a.id}" ${(e.category_ids||[]).map(String).includes(String(a.id))?"selected":""}>${s(a.name)}</option>`).join("")}
            </select>
        `),t.querySelector("#leCategoryIds").addEventListener("change",a=>{e.category_ids=Array.from(a.target.selectedOptions).map(r=>parseInt(r.value,10)),o(),d()})}function tt(t,e){t.innerHTML=u("Contenido HTML",`<textarea class="users-manager-input client-modal-textarea" id="leHtml" rows="8" placeholder="<div>...</div>">${s(e.html)}</textarea>`),t.querySelector("#leHtml").addEventListener("input",a=>{e.html=a.target.value,o(),d()})}function at(t,e){t.innerHTML=`
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
        `,t.querySelector("#leFaqDescription").addEventListener("input",a=>{e.description=a.target.value,o(),d()}),t.querySelector("#leFaqAdd").addEventListener("click",()=>{v.faqs=v.faqs||[],v.faqs.push({question:"",answer:""}),o(),re(t.querySelector("#leFaqRows"))}),re(t.querySelector("#leFaqRows")),t.querySelector("#leFaqSaveBtn").addEventListener("click",()=>D({btnId:"leFaqSaveBtn",errorsBoxId:"leFaqErrors"}))}function rt(t,e){t.innerHTML=`
            ${u("Badges cortos (separados por ·, máx. 3)",`<input type="text" class="users-manager-input" id="leRhBadges" value="${s((e.badges||[]).join(" · "))}" placeholder="Garantía 6 meses · Reporte técnico incluido">`)}
            ${u("Líneas de meta (una por línea)",`<textarea class="users-manager-input client-modal-textarea" id="leRhMetaLines" rows="2">${s((e.meta_lines||[]).join(`
`))}</textarea>`)}
            ${u("Texto del botón",`<input type="text" class="users-manager-input" id="leRhWhatsapp" value="${s(e.whatsapp_text||"Cotizar por WhatsApp")}">`)}
            <p class="hs-config-note">El CTA siempre abre WhatsApp; no existe botón de llamada.</p>
            ${u("Precio mostrado (opcional)",`<input type="text" class="users-manager-input" id="leRhPriceLabel" value="${s(e.price_label)}" placeholder="$8,500 MXN + IVA">`)}
            ${u("Imagen de fondo (galería del servicio)",'<div id="leRhBgImages"></div><p class="hs-config-note" style="margin-top:6px;">Si marcas varias, solo se usa la primera.</p>')}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <p class="hs-config-note">El título y la descripción corta se editan en "Información general" — aquí solo se controla su estilo.</p>
            <p class="live-editor-col-title" style="margin:6px 0 0;">Estilo del título (H1, fijo)</p>
            <div id="leRhTitleStyle"></div>
            <p class="live-editor-col-title" style="margin:14px 0 0;">Estilo de la descripción corta</p>
            <div id="leRhSubtitleStyle"></div>
        `,t.querySelector("#leRhBadges").addEventListener("input",a=>{e.badges=a.target.value.split("·").map(r=>r.trim()).filter(Boolean).slice(0,3),o(),d()}),t.querySelector("#leRhMetaLines").addEventListener("input",a=>{e.meta_lines=a.target.value.split(`
`).map(r=>r.trim()).filter(Boolean),o(),d()}),t.querySelector("#leRhWhatsapp").addEventListener("input",a=>{e.whatsapp_text=a.target.value,o(),d()}),t.querySelector("#leRhPriceLabel").addEventListener("input",a=>{e.price_label=a.target.value,o(),d()}),fe(t.querySelector("#leRhBgImages"),e.background_image_ids,b.images,a=>{e.background_image_ids=a,o(),d()}),e.title_style=e.title_style||{},e.subtitle_style=e.subtitle_style||{},A(t.querySelector("#leRhTitleStyle"),e.title_style,{}),A(t.querySelector("#leRhSubtitleStyle"),e.subtitle_style,{})}function le(t,e){e.tabs=e.tabs||[];let a='<p class="hs-config-note">Cada pestaña se muestra como pestaña horizontal en público.</p><div id="leCtRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="leCtAdd" style="margin-top:10px;">+ Agregar pestaña</button>';t.innerHTML=a;const r=t.querySelector("#leCtRows");e.tabs.forEach((l,i)=>{const n=document.createElement("div");n.className="hs-repeat-row",n.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${i+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-label" placeholder="Título de pestaña" value="${s(l.label)}">
                <input type="text" class="users-manager-input le-subtitle" placeholder="Subtítulo (opcional)" style="margin-top:6px;" value="${s(l.subtitle)}">
                <textarea class="users-manager-input client-modal-textarea le-body" rows="2" placeholder="Párrafo" style="margin-top:6px;">${s(l.body)}</textarea>
                <textarea class="users-manager-input client-modal-textarea le-bullets" rows="2" placeholder="Viñetas, una por línea" style="margin-top:6px;">${s((l.bullets||[]).join(`
`))}</textarea>
                <select class="users-manager-select le-image" style="margin-top:6px;">
                    <option value="">Sin imagen</option>
                    ${xe(l.image_id?[l.image_id]:[],b.images)}
                </select>
                <div class="le-tab-style" style="margin-top:6px;"></div>
            `,n.querySelector(".le-label").addEventListener("input",p=>{l.label=p.target.value,o(),k(),d()}),n.querySelector(".le-subtitle").addEventListener("input",p=>{l.subtitle=p.target.value,o(),d()}),n.querySelector(".le-body").addEventListener("input",p=>{l.body=p.target.value,o(),d()}),n.querySelector(".le-bullets").addEventListener("input",p=>{l.bullets=p.target.value.split(`
`).map(c=>c.trim()).filter(Boolean),o(),d()}),n.querySelector(".le-image").addEventListener("change",p=>{l.image_id=p.target.value||null,o(),d()}),n.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.tabs.splice(i,1),o(),le(t,e),d()}),l.style=l.style||{},A(n.querySelector(".le-tab-style"),l.style,{}),r.appendChild(n)}),t.querySelector("#leCtAdd").addEventListener("click",()=>{e.tabs.push({label:"",subtitle:"",body:"",bullets:[],image_id:null}),o(),le(t,e),d()})}function ie(t,e){e.items=e.items||[],e.layout=e.layout==="vertical"?"vertical":"horizontal",t.innerHTML=`
            ${u("Diseño de las tarjetas",`
                <select class="users-manager-select" id="leBgLayout">
                    <option value="horizontal" ${e.layout==="horizontal"?"selected":""}>Horizontal (en fila)</option>
                    <option value="vertical" ${e.layout==="vertical"?"selected":""}>Vertical (apiladas)</option>
                </select>
            `)}
            <p class="hs-config-note">Tarjetas de cifra + título + descripción.</p><div id="leBgRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="leBgAdd" style="margin-top:10px;">+ Agregar beneficio</button>
        `,t.querySelector("#leBgLayout").addEventListener("change",r=>{e.layout=r.target.value,o(),d()});const a=t.querySelector("#leBgRows");e.items.forEach((r,l)=>{const i=document.createElement("div");i.className="hs-repeat-row",i.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${l+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-figure" placeholder="Cifra (ej. -12%)" value="${s(r.figure)}">
                <input type="text" class="users-manager-input le-title" placeholder="Título" style="margin-top:6px;" value="${s(r.title)}">
                <textarea class="users-manager-input client-modal-textarea le-description" rows="2" placeholder="Descripción corta" style="margin-top:6px;">${s(r.description)}</textarea>
            `,i.querySelector(".le-figure").addEventListener("input",n=>{r.figure=n.target.value,o(),d()}),i.querySelector(".le-title").addEventListener("input",n=>{r.title=n.target.value,o(),k(),d()}),i.querySelector(".le-description").addEventListener("input",n=>{r.description=n.target.value,o(),d()}),i.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.items.splice(l,1),o(),ie(t,e),d()}),a.appendChild(i)}),t.querySelector("#leBgAdd").addEventListener("click",()=>{e.items.push({figure:"",title:"",description:""}),o(),ie(t,e),d()})}function se(t,e){e.steps=e.steps||[],t.innerHTML='<p class="hs-config-note">Pasos numerados del proceso.</p><div id="lePsRows" class="hs-repeat-rows"></div><button type="button" class="button-secondary size-adjustment" id="lePsAdd" style="margin-top:10px;">+ Agregar paso</button>';const a=t.querySelector("#lePsRows");e.steps.forEach((r,l)=>{const i=document.createElement("div");i.className="hs-repeat-row",i.innerHTML=`
                <div class="hs-repeat-row-head"><span class="hs-repeat-row-num">${l+1}</span><button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button></div>
                <input type="text" class="users-manager-input le-title" placeholder="Título del paso" value="${s(r.title)}">
                <textarea class="users-manager-input client-modal-textarea le-description" rows="2" placeholder="Descripción" style="margin-top:6px;">${s(r.description)}</textarea>
                <input type="text" class="users-manager-input le-duration" placeholder="Duración (ej. 1 h)" style="margin-top:6px;" value="${s(r.duration)}">
            `,i.querySelector(".le-title").addEventListener("input",n=>{r.title=n.target.value,o(),k(),d()}),i.querySelector(".le-description").addEventListener("input",n=>{r.description=n.target.value,o(),d()}),i.querySelector(".le-duration").addEventListener("input",n=>{r.duration=n.target.value,o(),d()}),i.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.steps.splice(l,1),o(),se(t,e),d()}),a.appendChild(i)}),t.querySelector("#lePsAdd").addEventListener("click",()=>{e.steps.push({title:"",description:"",duration:""}),o(),se(t,e),d()})}function Le(t,e){e.logos=Array.isArray(e.logos)?e.logos:[],t.innerHTML=`
            <p class="hs-config-note">Logotipos de las marcas con las que trabajas en este servicio. Usa imágenes con fondo transparente (PNG/SVG/WebP) para que se vean parejas.</p>
            <div id="leBlRows" class="hs-repeat-rows"></div>
            <button type="button" class="button-secondary size-adjustment" id="leBlAdd" style="margin-top:10px;">+ Agregar logotipo</button>
            <label style="display:flex;align-items:center;gap:8px;font-weight:400;font-size:13px;color:#374151;margin-top:14px;">
                <input type="checkbox" id="leBlGray" ${e.grayscale?"checked":""}> Mostrar en escala de grises (a color al pasar el cursor)
            </label>
        `;const a=t.querySelector("#leBlRows"),r=()=>{Le(t,e),d()};e.logos.forEach((l,i)=>{const n=document.createElement("div");n.className="hs-repeat-row",n.innerHTML=`
                <div class="hs-repeat-row-head">
                    <span class="hs-repeat-row-num">${i+1}</span>
                    <span style="margin-left:auto;display:flex;gap:4px;">
                        <button type="button" class="hs-faq-btn le-bl-up" title="Subir" ${i===0?"disabled":""}>&uarr;</button>
                        <button type="button" class="hs-faq-btn le-bl-down" title="Bajar" ${i===e.logos.length-1?"disabled":""}>&darr;</button>
                        <button type="button" class="hs-faq-btn hs-repeat-remove" title="Eliminar">&times;</button>
                    </span>
                </div>
                <div style="display:flex;gap:10px;align-items:center;">
                    <div style="width:84px;height:56px;flex-shrink:0;border:1px solid #e5e7eb;border-radius:8px;background:#f9fafb;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                        ${l.url?`<img src="${s(l.url)}" alt="" style="max-width:100%;max-height:100%;object-fit:contain;">`:'<span style="font-size:11px;color:#9ca3af;">Sin imagen</span>'}
                    </div>
                    <button type="button" class="button-secondary size-adjustment le-bl-pick">${l.url?"Cambiar logotipo":"Seleccionar logotipo"}</button>
                </div>
                <input type="text" class="users-manager-input le-bl-alt" placeholder="Nombre de la marca (texto alternativo)" style="margin-top:6px;" value="${s(l.alt)}">
                <input type="text" class="users-manager-input le-bl-link" placeholder="Enlace (opcional): https://… o /servicios/…" style="margin-top:6px;" value="${s(l.link_url)}">
            `,n.querySelector(".le-bl-alt").addEventListener("input",p=>{l.alt=p.target.value,o(),d()}),n.querySelector(".le-bl-link").addEventListener("input",p=>{l.link_url=p.target.value,o(),d()}),n.querySelector(".le-bl-pick").addEventListener("click",()=>{typeof window.openImagePicker=="function"&&window.openImagePicker(null,{onSelect:p=>{l.url=p,o(),r()}})}),n.querySelector(".le-bl-up").addEventListener("click",()=>{i!==0&&([e.logos[i-1],e.logos[i]]=[e.logos[i],e.logos[i-1]],o(),r())}),n.querySelector(".le-bl-down").addEventListener("click",()=>{i!==e.logos.length-1&&([e.logos[i+1],e.logos[i]]=[e.logos[i],e.logos[i+1]],o(),r())}),n.querySelector(".hs-repeat-remove").addEventListener("click",()=>{e.logos.splice(i,1),o(),r()}),a.appendChild(n)}),t.querySelector("#leBlAdd").addEventListener("click",()=>{typeof window.openImagePicker=="function"&&window.openImagePicker(null,{onSelect:l=>{e.logos.push({url:l,alt:"",link_url:""}),o(),r()}})}),t.querySelector("#leBlGray").addEventListener("change",l=>{e.grayscale=l.target.checked,o(),d()})}function nt(t,e){t.innerHTML=u("Imágenes a mostrar (vacío = toda la galería)",'<div id="leGcImages"></div>'),fe(t.querySelector("#leGcImages"),e.image_ids,b.images,a=>{e.image_ids=a,o(),d()},{clearAllLabel:"Vaciar selección"})}function lt(t,e){t.innerHTML=`
            ${u("Texto descriptivo (opcional)",`<textarea class="users-manager-input client-modal-textarea" id="leRrDescription" rows="2">${s(e.description)}</textarea>`)}
            ${u('Reseñas visibles antes de "Ver más"',`<input type="number" class="users-manager-input" id="leRrPerPage" min="1" max="20" value="${e.reviews_per_page??3}">`)}
            <p class="hs-config-note">Las reseñas se capturan en el panel <strong>Reseñas</strong> del sidebar, y las estadísticas de "Promedio mostrado" en <strong>Información general</strong>. Esta sección solo define dónde aparecen y su texto descriptivo.</p>
        `,t.querySelector("#leRrDescription").addEventListener("input",a=>{e.description=a.target.value,o(),d()}),t.querySelector("#leRrPerPage").addEventListener("input",a=>{e.reviews_per_page=parseInt(a.target.value,10)||3,o(),d()})}function it(t,e){t.innerHTML=`
            ${u("Título",`<input type="text" class="users-manager-input" id="leCtaHeadline" value="${s(e.headline)}" placeholder="¿Listo para cotizar tu servicio?">`)}
            <div id="leCtaHeadlineStyle"></div>
            ${u("Texto de apoyo",`<textarea class="users-manager-input client-modal-textarea" id="leCtaSubtext" rows="2">${s(e.subtext)}</textarea>`)}
            <div id="leCtaSubtextStyle"></div>
            <div class="show-user-divider" style="margin:10px 0;"></div>
            ${u("Texto del botón",`<input type="text" class="users-manager-input" id="leCtaWhatsapp" value="${s(e.whatsapp_text||"Cotizar por WhatsApp")}">`)}
            <p class="hs-config-note">Deja este campo vacío para ocultar el botón de WhatsApp.</p>
            ${u("Imagen de fondo (opcional)",`<select class="users-manager-select" id="leCtaBg"><option value="">Sin imagen</option>${xe(e.background_image_id?[e.background_image_id]:[],b.images)}</select>`)}
            <div class="show-user-divider" style="margin:10px 0;"></div>
            <p class="live-editor-col-title">Botón secundario (opcional)</p>
            <p class="hs-config-note">Se muestra junto al de WhatsApp (o solo, si dejaste ese campo vacío) — útil para un enlace que no sea WhatsApp.</p>
            <div id="leCtaSecondaryBtn"></div>
        `,t.querySelector("#leCtaHeadline").addEventListener("input",a=>{e.headline=a.target.value,o(),d()}),t.querySelector("#leCtaSubtext").addEventListener("input",a=>{e.subtext=a.target.value,o(),d()}),t.querySelector("#leCtaWhatsapp").addEventListener("input",a=>{e.whatsapp_text=a.target.value,o(),d()}),t.querySelector("#leCtaBg").addEventListener("change",a=>{e.background_image_id=a.target.value||null,o(),d()}),e.headline_style=e.headline_style||{},e.subtext_style=e.subtext_style||{},A(t.querySelector("#leCtaHeadlineStyle"),e.headline_style,{tagChoices:["h2","h3"]}),A(t.querySelector("#leCtaSubtextStyle"),e.subtext_style,{}),e.secondary_button=e.secondary_button||{text:"",url:"",style:"outline",color:"#ff6213"},ee(t.querySelector("#leCtaSecondaryBtn"),e.secondary_button,{alignField:!1})}let qe=null;function d(){clearTimeout(qe),qe=setTimeout(Te,400)}async function Te(){try{const e=await(await fetch(b.previewUrl,{method:"POST",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({sections:$.filter(a=>a.is_active).map((a,r)=>({...a,sort_order:r}))})})).json();j.srcdoc=e.html??""}catch(t){console.error("Error generando el preview:",t)}}j.addEventListener("load",()=>{try{const t=j.contentDocument;if(!t)return;const e=be(C);if(e&&e.id){const a=t.createElement("style");a.textContent=`[data-section-id="${e.id}"] { outline: 3px solid #ff6213; outline-offset: 2px; cursor: pointer; }`,t.head.appendChild(a)}t.body.addEventListener("click",a=>{const r=a.target.closest("[data-section-id]");if(!r)return;const l=r.getAttribute("data-section-id"),i=$.find(n=>String(n.id)===String(l));i&&(a.preventDefault(),ae(i._uid))},!0)}catch{}});const Re=document.getElementById("leViewportCaption");function st(){Re&&(Re.textContent=V==="mobile"?"Móvil · 375px":"Escritorio · 1440px")}ve.addEventListener("click",t=>{const e=t.target.closest("button[data-viewport]");e&&(V=e.dataset.viewport,ve.querySelectorAll("button").forEach(a=>a.classList.toggle("is-active",a===e)),j.classList.toggle("is-mobile",V==="mobile"),st())}),M.addEventListener("click",async()=>{M.disabled=!0;const t=M.textContent;M.textContent="Guardando...",H.textContent="Guardando…",H.className="live-editor-status is-saving",_.querySelector("#leGenSaveBtn")?await D():_.querySelector("#leFaqSaveBtn")&&await D({btnId:"leFaqSaveBtn",errorsBoxId:"leFaqErrors"});try{if(!(await fetch(b.saveUrl,{method:"PUT",headers:{"Content-Type":"application/json","X-CSRF-TOKEN":E,Accept:"application/json"},body:JSON.stringify({sections:$.map((a,r)=>({...a,sort_order:r}))})})).ok)throw new Error("save request failed");window.location.reload()}catch(e){console.error("Error guardando la página:",e),alert("No se pudieron guardar los cambios. Intenta de nuevo."),M.disabled=!1,M.textContent=t,o()}}),$.length?(k(),B()):_e(),Te()})();
