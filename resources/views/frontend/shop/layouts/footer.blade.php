<footer class="eq-footer">
  <div class="eq-footer__inner">
    <div class="eq-footer__grid">
      <div>
        <img src="{{ asset('images/logo/equiterm-logo-blanco-color-3x.png') }}" alt="Equiterm Industries" class="eq-footer__logo">
        <p>{{ \App\Models\Setting::get('footer.description', 'Ingeniería térmica industrial: calderas, calentamiento y tratamiento de agua, con proyectos llave en mano en toda la República Mexicana.') }}</p>
      </div>
      <div>
        <div class="eq-footer__col-title">Servicios</div>
        @foreach ($footerServiceItems as $item)
          <a href="{{ $item->url ?? '#' }}" target="{{ $item->target }}">{{ $item->title }}</a>
        @endforeach
      </div>
      <div>
        <div class="eq-footer__col-title">Productos</div>
        @foreach ($footerProductItems as $item)
          <a href="{{ $item->url ?? '#' }}" target="{{ $item->target }}">{{ $item->title }}</a>
        @endforeach
      </div>
      <div>
        <div class="eq-footer__col-title">Contacto</div>
        <div class="eq-footer__contact">
          {{ \App\Models\Setting::get('footer.address', 'Ciudad de México, CDMX') }}<br>
          <a href="tel:{{ \App\Models\Setting::get('footer.phone_link', '+524494348018') }}">{{ \App\Models\Setting::get('footer.phone', '+52 449 434 8018') }}</a><br>
          <a href="mailto:{{ \App\Models\Setting::get('footer.email', 'administracion@equitermindustries.com.mx') }}">{{ \App\Models\Setting::get('footer.email', 'administracion@equitermindustries.com.mx') }}</a>
        </div>
      </div>
    </div>
    <div class="eq-footer__legal">
      <span>© {{ date('Y') }} Equiterm Industries. Todos los derechos reservados.</span>
      <nav class="footer-legal" aria-label="Enlaces legales">
                <ul class="footer-legal-links">
                    <li>@auth
                            @php $rol = auth()->user()->role?->name_role @endphp

                            @if ($rol === 'admin')
                                <a  href="{{ route('admin.dashboard') }}">Panel Admin</a>

                            @elseif ($rol === 'employe')
                                <a href="{{ route('employee.dashboard') }}">Mi Dashboard</a>

                            @else
                                <a href="{{ route('profile.edit') }}">Mi Cuenta</a>
                            @endif
                        @else
                            <a href="{{ route('login') }}">Iniciar Sesión</a>
                        @endauth
                    </li>
                    <li><a href="{{ route('privacy-notice') }}">Aviso de privacidad</a></li>
                    <li><a href="{{ route('terms-of-service') }}">Términos y condiciones</a></li>
                </ul>
            </nav>
      <span>Distribuidor autorizado Masstercal Rinnai · Aquaplus</span>
    </div>
  </div>
</footer>
