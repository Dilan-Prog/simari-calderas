const j={cc_rejected_insufficient_amount:"Fondos insuficientes.",cc_rejected_bad_filled_security_code:"El código de seguridad (CVV) es incorrecto.",cc_rejected_bad_filled_date:"La fecha de vencimiento de la tarjeta es incorrecta.",cc_rejected_bad_filled_card_number:"El número de tarjeta es incorrecto.",cc_rejected_bad_filled_other:"Revisa los datos de la tarjeta e intenta de nuevo.",cc_rejected_call_for_authorize:"Tu banco requiere que autorices el pago directamente con ellos.",cc_rejected_card_disabled:"La tarjeta está deshabilitada, contacta a tu banco.",cc_rejected_duplicated_payment:"Ya se registró un pago con estos mismos datos.",cc_rejected_high_risk:"El pago fue rechazado por seguridad.",cc_rejected_max_attempts:"Se alcanzó el límite de intentos permitidos."};function S(e){return j[e]||"No se pudo procesar el pago, intenta con otra tarjeta."}const P=["in_process","authorized","pending"];function T(){const e=document.querySelector('meta[name="csrf-token"]');return e?e.content:""}function y(){return new Promise((e,t)=>{if(window.MercadoPago){e(window.MercadoPago);return}let a=0;const r=setInterval(()=>{a+=1,window.MercadoPago?(clearInterval(r),e(window.MercadoPago)):a>100&&(clearInterval(r),t(new Error("No se pudo cargar el SDK de Mercado Pago.")))},50)})}function x(){return window.MercadoPago?Promise.resolve(window.MercadoPago):document.querySelector("script[data-mp-sdk]")?y():new Promise((e,t)=>{const a=document.createElement("script");a.src="https://sdk.mercadopago.com/js/v2",a.dataset.mpSdk="1",a.onload=()=>e(),a.onerror=()=>t(new Error("No se pudo cargar el SDK de Mercado Pago.")),document.head.appendChild(a)}).then(()=>y())}function _(e,t,a){const r=e.querySelector("[data-mp-message]");r&&(r.textContent=t||"",r.classList.remove("mp-message--error","mp-message--info"),a&&r.classList.add(`mp-message--${a}`))}function I(e){e.classList.add("mp-charge-disabled")}function L(e){e.classList.remove("mp-charge-disabled")}async function R(e,t){const a=await fetch(e,{method:"POST",headers:{"Content-Type":"application/json",Accept:"application/json","X-CSRF-TOKEN":T()},body:JSON.stringify(t)}),r=await a.json().catch(()=>({}));return{ok:a.ok,data:r}}let C=0;function N(e,t,a,r){return e.map(o=>{const l=o.chargeGroup,c=`${l}-${r}`,f=e.length>1?`Cobro ${l} de ${e.length}: ${o.includesMsi?"productos con meses sin interés":"resto de tu pedido"} — $${o.amount.toFixed(2)} MXN`:null;if(t&&o.id!==t){const b=o.status==="approved"?'<div class="checkout-alert" style="background:#e6f6ee;color:#0f7a4f;">Este cobro ya está aprobado.</div>':`<div class="checkout-alert" style="background:#f1f2f4;color:#6b7280;">Estatus de este cobro: ${o.status||"pendiente"}</div>`;return`
                <div class="checkout-payment mp-charge-block" data-mp-block="${l}">
                    ${f?`<div class="checkout-payment__head"><div class="checkout-payment__head-title">${f}</div></div>`:""}
                    ${b}
                </div>
            `}const p=!t&&l===2,g=o.includesMsi?`<select class="mp-cardform-field checkout-form__span2" id="form-checkout__installments-${c}"></select>`:`<select class="mp-cardform-field mp-cardform-field--hidden" id="form-checkout__installments-${c}"></select>
               <div class="mp-cardform-installments-note checkout-form__span2">Pago en una sola exhibición.</div>`;return`
            <div class="checkout-payment mp-charge-block mp-cardform-block" data-mp-block="${l}">
                ${f?`<div class="checkout-payment__head"><div class="checkout-payment__head-title">${f}</div></div>`:""}

                <div class="mp-cardform-preview" data-mp-preview="${l}">
                    <div class="mp-cardform-preview__top">
                        <span class="mp-cardform-preview__chip"></span>
                        <span class="mp-cardform-preview__brand" data-mp-preview-brand>&nbsp;</span>
                    </div>
                    <div class="mp-cardform-preview__number">•••• •••• •••• ••••</div>
                    <div class="mp-cardform-preview__bottom">
                        <span data-mp-preview-name>NOMBRE EN LA TARJETA</span>
                        <span>••/••</span>
                    </div>
                </div>

                <form id="cardForm-${c}" class="checkout-form__grid checkout-form__grid--tight">
                    <input type="hidden" id="form-checkout__cardholderEmail-${c}" value="${a||""}">

                    <div class="checkout-form__span2 mp-cardform-field" id="form-checkout__cardNumber-${c}"></div>
                    <input type="text" id="form-checkout__cardholderName-${c}" class="checkout-form__span2" placeholder="Nombre en la tarjeta" data-mp-name-input>
                    <div class="mp-cardform-field" id="form-checkout__expirationDate-${c}"></div>
                    <div class="mp-cardform-field" id="form-checkout__securityCode-${c}"></div>

                    ${g}

                    <select class="mp-cardform-field" id="form-checkout__identificationType-${c}"></select>
                    <input type="text" class="mp-cardform-field" id="form-checkout__identificationNumber-${c}" placeholder="RFC o CURP (opcional)">
                    <select id="form-checkout__issuer-${c}" style="display:none"></select>

                    <button type="submit" class="checkout-submit checkout-form__span2" id="cardFormSubmit-${c}">Confirmar y pagar $${o.amount.toFixed(2)} MXN</button>
                </form>

                <p data-mp-message class="mp-message"></p>

                <div class="mp-brand-logos-row">
                    <span class="mp-brand-logo-box"><img src="/images/payment-logos/visa.jpg" alt="Visa"></span>
                    <span class="mp-brand-logo-box"><img src="/images/payment-logos/mastercard.jpg" alt="Mastercard"></span>
                    <span class="mp-brand-logo-box"><img src="/images/payment-logos/amex.webp" alt="American Express"></span>
                    <span class="mp-trust-badge mp-trust-badge--pci">Certificado PCI-DSS</span>
                </div>

                ${p?'<div class="mp-charge-overlay">Se habilita al aprobarse el Cobro 1</div>':""}
            </div>
        `}).join("")}function F(e,t,a){const{payments:r,retryOnly:o,publicKey:l}=t,c=t.payerEmail||"",f=[];return r.forEach((p,g)=>{const b=p.chargeGroup,s=`${b}-${a}`,m=document.querySelector(`[data-mp-block="${b}"]`);if(!m||(!(g===0)&&!o&&I(m),o&&p.id!==o))return;const k=document.getElementById(`form-checkout__cardholderName-${s}`),$=m.querySelector("[data-mp-preview-name]");k&&$&&k.addEventListener("input",()=>{$.textContent=k.value.trim().toUpperCase()||"NOMBRE EN LA TARJETA"});const w=e.cardForm({amount:String(p.amount.toFixed(2)),iframe:!0,form:{id:`cardForm-${s}`,cardholderName:{id:`form-checkout__cardholderName-${s}`},cardholderEmail:{id:`form-checkout__cardholderEmail-${s}`},cardNumber:{id:`form-checkout__cardNumber-${s}`},expirationDate:{id:`form-checkout__expirationDate-${s}`},securityCode:{id:`form-checkout__securityCode-${s}`},installments:{id:`form-checkout__installments-${s}`},identificationType:{id:`form-checkout__identificationType-${s}`},identificationNumber:{id:`form-checkout__identificationNumber-${s}`},issuer:{id:`form-checkout__issuer-${s}`}},callbacks:{onFormMounted:u=>{u&&(console.error("[mercadopago-checkout] CardForm onFormMounted error",u),_(m,"No se pudo cargar el formulario de tarjeta. Recarga la página.","error"))},onInstallmentsReceived:(u,d)=>{var h,n;if(u||!Array.isArray(d)||!d.length)return;const i=m.querySelector("[data-mp-preview-brand]"),v=((h=d[0])==null?void 0:h.payment_method_id)||((n=d[0])==null?void 0:n.paymentMethodId);i&&v&&(i.textContent=String(v).toUpperCase())},onValidityChange:(u,d)=>{const i=document.getElementById(`form-checkout__${d}-${s}`);i&&i.classList.toggle("mp-cardform-field--invalid",!!u)},onSubmit:u=>{u.preventDefault(),_(m,"",null);const d=document.getElementById(`cardFormSubmit-${s}`);d&&(d.disabled=!0);const i=w.getCardFormData(),v=i.identificationNumber?{type:i.identificationType,number:i.identificationNumber}:void 0;R(p.chargeUrl,{token:i.token,payment_method_id:i.paymentMethodId,installments:p.includesMsi?i.installments:1,payer:{email:c,identification:v}}).then(({ok:h,data:n})=>{if(d&&(d.disabled=!1),h&&n.status==="approved"){if(_(m,"Pago aprobado.","info"),n.redirect){window.location.href=n.redirect;return}const E=r[g+1];if(E){const M=document.querySelector(`[data-mp-block="${E.chargeGroup}"]`);M&&L(M)}}else h&&P.includes(n.status)?(_(m,"Tu pago quedó en revisión por Mercado Pago -- te confirmaremos por correo en cuanto se resuelva.","info"),n.redirect&&(window.location.href=n.redirect)):h?_(m,S(n.status_detail),"error"):_(m,n.status_detail?S(n.status_detail):"No se pudo procesar el pago, intenta con otra tarjeta.","error")}).catch(()=>{d&&(d.disabled=!1),_(m,"No se pudo procesar el pago, intenta con otra tarjeta.","error")})},onError:u=>{console.error("[mercadopago-checkout] CardForm error",u)}}});f.push(w)}),f}async function A(){const e=document.getElementById("mp-checkout-data");if(!e)return;const t=JSON.parse(e.textContent);let a;try{a=await y()}catch{t.payments.forEach(f=>{const p=document.querySelector(`[data-mp-block="${f.chargeGroup}"]`);p&&_(p,"No se pudo cargar la pasarela de pago. Recarga la página.","error")});return}const r=document.getElementById("mpCardFormRoot");if(!r||r.dataset.mpRendered==="1")return;r.dataset.mpRendered="1";const o=++C;r.innerHTML=N(t.payments,t.retryOnly,t.payerEmail,o);const l=new a(t.publicKey,{locale:"es-MX"});F(l,t,o)}document.addEventListener("DOMContentLoaded",A);window.MercadoPagoCardForm={async render(e,t){if(e.dataset.mpRendered==="1")return;e.dataset.mpRendered="1";const a=++C;e.innerHTML=N(t.payments,t.retryOnly,t.payerEmail,a);let r;try{r=await x()}catch(l){throw e.innerHTML='<p class="mp-message mp-message--error">No se pudo cargar la pasarela de pago. Recarga la página.</p>',l}const o=new r(t.publicKey,{locale:"es-MX"});e._mpCardForms=F(o,t,a)},unmount(e){(e._mpCardForms||[]).forEach(t=>{try{t.unmount()}catch(a){console.error("[mercadopago-checkout] Error al desmontar CardForm",a)}}),e._mpCardForms=[],delete e.dataset.mpRendered}};
