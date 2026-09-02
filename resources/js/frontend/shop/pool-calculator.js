import Alpine from './alpine-init.js';

// IMPORTANTE: este entry NO llama Alpine.start() -- la página donde vive
// este componente (Colección, /coleccion/{slug}) ya lo arranca en
// collection.js. Llamar Alpine.start() una segunda vez rompe Alpine
// (confirmado leyendo collection.js antes de escribir este archivo).
Alpine.data('poolCalculator', (config) => ({
    config,

    // --- Datos del formulario (con sus defaults) ---
    forma: 'rectangular',
    largo: null,
    ancho: null,
    diametro: null,
    prof: 1.5,
    ciudad: '',
    // cubierta/expuesta son booleanos reales (no 'si'/'no' string) porque
    // la fórmula de negocio de abajo hace `this.cubierta ? 0.55 : 1.0` --
    // ver comentario en pool-calculator.blade.php sobre por qué los radios
    // usan :checked/@change en vez de x-model directo.
    cubierta: false,
    expuesta: false,
    tempActual: 22,
    tempObjetivo: 28,
    horasCalentado: 72,

    resultado: null,

    /**
     * Fórmula de dimensionamiento -- modelo de negocio ya definido por el
     * usuario, NO se reinventa aquí. `modelos` (config.modelos) ya llega
     * ordenado ascendente por BTU desde el Blade (ver pool-calculator.blade.php),
     * requisito de `.find(m => m.btu >= btuEq)` para recomendar el modelo
     * MÁS PEQUEÑO que alcance.
     */
    dimensionar() {
        const area = this.forma === 'redonda'
            ? Math.PI * Math.pow((this.diametro || 0) / 2, 2)
            : (this.largo || 0) * (this.ancho || 0);
        const m3 = area * this.prof;
        const K = 5580 / (this.horasCalentado || 72);
        const dT = Math.max(0, this.tempObjetivo - this.tempActual);
        const fC = this.cubierta ? 0.55 : 1.0;
        const fV = this.expuesta ? 1.15 : 1.0;
        const btuReq = area * dT * K * fC * fV;
        const tAmb = config.ciudades[this.ciudad] ?? 12;
        const fDer = Math.max(0.45, 1 - 0.025 * (27 - tAmb));
        const btuEq = btuReq / fDer;
        const modelo = config.modelos.find(m => m.btu >= btuEq) || { nombre: 'Proyecto a medida', btu: btuEq, precio: null, slug: null, url: null };
        const cop = 2.0 + (config.copNominal - 2.0) * fDer;
        const kW = btuEq / 3412 / cop;
        const kWhMes = kW * config.horasDia * 30;
        const costo = kWhMes * config.tarifaKwh;

        this.resultado = {
            m2: area,
            m3,
            litros: m3 * 1000,
            btuReq,
            btuEq,
            modelo,
            kWhMes,
            costoMin: costo * 0.75,
            costoMax: costo * 1.25,
        };

        // NOTA: a diferencia de lo que sugiere el roadmap original, el `ref`
        // (folio EQ-XXXX) todavía NO existe en este punto -- se genera
        // recién en enviarWhatsApp() al hacer POST al endpoint de leads. Por
        // eso este evento no lo incluye; el evento con `ref` es
        // 'whatsapp_abierto', más abajo.
        window.dataLayer = window.dataLayer || [];
        dataLayer.push({
            event: 'calc_completado',
            alberca_m3: Math.round(m3),
            modelo: modelo.nombre,
            btu: Math.round(btuEq),
            ciudad: this.ciudad,
        });
    },

    async enviarWhatsApp() {
        const datos = {
            forma: this.forma,
            largo: this.largo,
            ancho: this.ancho,
            diametro: this.diametro,
            prof: this.prof,
            ciudad: this.ciudad,
            cubierta: this.cubierta,
            expuesta: this.expuesta,
            tempActual: this.tempActual,
            tempObjetivo: this.tempObjetivo,
            horasCalentado: this.horasCalentado,
        };

        // El ref (folio EQ-XXXX) se pide al backend justo antes de abrir
        // WhatsApp. Envuelto en try/catch: si el fetch falla (red caída,
        // endpoint caído, etc.) el mensaje de WhatsApp debe salir IGUAL,
        // solo que sin línea de "Ref:" -- nunca bloquear el contacto del
        // cliente por un fallo de tracking (mismo criterio "silencioso" ya
        // usado en ad-tracking.js).
        let ref = null;
        try {
            const response = await fetch('/api/v1/pool-calculator/leads', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    visitor_uuid: window.__adVisitorUuid || null,
                    home_section_id: config.homeSectionId,
                    payload: { datos, resultado: this.resultado },
                }),
            });
            const data = await response.json();
            ref = data?.ref ?? null;
        } catch (e) {
            ref = null;
        }

        const m3 = Math.round(this.resultado.m3);
        const btu = Math.round(this.resultado.btuEq);
        const costoMin = Math.round(this.resultado.costoMin);
        const costoMax = Math.round(this.resultado.costoMax);
        const nombreModelo = this.resultado.modelo.nombre;

        const lineas = [
            'Hola, quiero cotizar una bomba de calor para mi alberca.',
            `Volumen aproximado: ${m3} m3`,
            `Ciudad: ${this.ciudad}`,
            `BTU/h requerido: ${btu}`,
            `Modelo recomendado: ${nombreModelo}`,
            `Costo mensual estimado: $${costoMin} - $${costoMax} MXN`,
        ];
        if (ref) {
            lineas.push(`Ref: ${ref}`);
        }

        window.dataLayer = window.dataLayer || [];
        dataLayer.push({
            event: 'whatsapp_abierto',
            ref,
            modelo: nombreModelo,
            alberca_m3: m3,
            ciudad: this.ciudad,
        });

        window.location.href = `https://wa.me/${config.whatsappNumero}?text=${encodeURIComponent(lineas.join('\n'))}`;
    },
}));
