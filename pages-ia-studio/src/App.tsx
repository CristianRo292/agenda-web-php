import { useEffect, useMemo, useRef, useState } from 'react';
import {
  Calendar,
  CalendarCheck,
  Eye,
  EyeOff,
  FileCode2,
  Plus,
  Search,
  SlidersHorizontal,
  X,
} from 'lucide-react';
import { CodeViewerModal } from './components/CodeViewerModal';
import type { Categoria, EventoItem, UsuarioPreview, VistaActiva } from './types';

const CATEGORIAS_INICIALES: Categoria[] = [
  { id: 1, nombre: 'Trabajo' },
  { id: 2, nombre: 'Personal' },
  { id: 3, nombre: 'Importante' },
  { id: 4, nombre: 'Salud' },
  { id: 5, nombre: 'Estudio' },
];

const EVENTOS_SEMILLA = (usuarioId: number): EventoItem[] => [
  {
    id: 101,
    usuarioId,
    titulo: 'Revisión de arquitectura del proyecto',
    fecha: '2026-11-25',
    hora: '10:00',
    categoriaId: 1,
    descripcion: 'Validar esquemas de base de datos, índices y flujo de autenticación.',
  },
  {
    id: 102,
    usuarioId,
    titulo: 'Caminata matutina en el bosque',
    fecha: '2026-11-26',
    hora: '07:30',
    categoriaId: 4,
    descripcion: 'Ruta tranquila de 5 km para despejar la mente antes de iniciar el día.',
  },
  {
    id: 103,
    usuarioId,
    titulo: 'Entrega de documentación final',
    fecha: '2026-11-27',
    hora: '16:00',
    categoriaId: 3,
    descripcion: 'Confirmar respaldo SQL y guía de despliegue en servidor local.',
  },
];

const STORAGE_USERS = 'agendaweb_preview_users_v1';
const STORAGE_EVENTS = 'agendaweb_preview_events_v1';
const STORAGE_SESSION = 'agendaweb_preview_session_v1';

function claseBadge(nombre: string): string {
  switch (nombre.toLowerCase()) {
    case 'trabajo':
      return 'bg-[#2d5a1b]/15 text-[#234914]';
    case 'personal':
      return 'bg-[#185e7a]/12 text-[#154e66]';
    case 'importante':
      return 'bg-[#b44a18]/14 text-[#93380d]';
    case 'salud':
      return 'bg-[#157347]/14 text-[#0e5a36]';
    case 'estudio':
      return 'bg-[#664c92]/14 text-[#4f3778]';
    default:
      return 'bg-[#5c6b57]/15 text-[#3a4536]';
  }
}

async function hashTexto(valor: string): Promise<string> {
  const bytes = new TextEncoder().encode(valor);
  const digest = await crypto.subtle.digest('SHA-256', bytes);
  return Array.from(new Uint8Array(digest))
    .map((b) => b.toString(16).padStart(2, '0'))
    .join('');
}

export default function App() {
  const [usuarios, setUsuarios] = useState<UsuarioPreview[]>(() => {
    try {
      const raw = localStorage.getItem(STORAGE_USERS);
      return raw ? JSON.parse(raw) : [];
    } catch {
      return [];
    }
  });

  const [eventos, setEventos] = useState<EventoItem[]>(() => {
    try {
      const raw = localStorage.getItem(STORAGE_EVENTS);
      return raw ? JSON.parse(raw) : [];
    } catch {
      return [];
    }
  });

  const [usuarioActivoId, setUsuarioActivoId] = useState<number | null>(() => {
    try {
      const raw = localStorage.getItem(STORAGE_SESSION);
      return raw ? Number(raw) : null;
    } catch {
      return null;
    }
  });

  const usuarioActivo = useMemo(
    () => usuarios.find((u) => u.id === usuarioActivoId) ?? null,
    [usuarios, usuarioActivoId]
  );

  const [vista, setVista] = useState<VistaActiva>(() =>
    usuarioActivoId ? 'dashboard' : 'bienvenida'
  );

  const [visorCodigoAbierto, setVisorCodigoAbierto] = useState(false);
  const [toast, setToast] = useState<string>('');
  const toastTimer = useRef<number | null>(null);

  // Estados de autenticación
  const [loginCorreo, setLoginCorreo] = useState('');
  const [loginClave, setLoginClave] = useState('');
  const [loginVerClave, setLoginVerClave] = useState(false);
  const [loginError, setLoginError] = useState('');

  const [regNombre, setRegNombre] = useState('');
  const [regCorreo, setRegCorreo] = useState('');
  const [regClave, setRegClave] = useState('');
  const [regConfirmar, setRegConfirmar] = useState('');
  const [regVerClave, setRegVerClave] = useState(false);
  const [regVerConfirmar, setRegVerConfirmar] = useState(false);
  const [regError, setRegError] = useState('');

  // Menú de usuario y confirmación
  const [menuUsuarioAbierto, setMenuUsuarioAbierto] = useState(false);
  const menuRef = useRef<HTMLDivElement | null>(null);
  const [confirmacion, setConfirmacion] = useState<{
    titulo: string;
    mensaje: string;
    onConfirmar: () => void;
  } | null>(null);

  // Búsqueda y filtros del Dashboard
  const [busqueda, setBusqueda] = useState('');
  const [categoriaFiltro, setCategoriaFiltro] = useState<number>(0);
  const [filtrosAbiertos, setFiltrosAbiertos] = useState(false);
  const [enTitulo, setEnTitulo] = useState(true);
  const [enCategoria, setEnCategoria] = useState(true);
  const [enDescripcion, setEnDescripcion] = useState(true);

  // Explorar con debounce real
  const [exploreInput, setExploreInput] = useState('');
  const [exploreDebounced, setExploreDebounced] = useState('');
  const [exploreCat, setExploreCat] = useState<string>('');
  const [exploreCargando, setExploreCargando] = useState(false);

  // Nuevo evento
  const [nuevoTitulo, setNuevoTitulo] = useState('');
  const [nuevoFecha, setNuevoFecha] = useState('2026-11-25');
  const [nuevoHora, setNuevoHora] = useState('09:00');
  const [nuevoCategoriaId, setNuevoCategoriaId] = useState(1);
  const [nuevoDescripcion, setNuevoDescripcion] = useState('');
  const [nuevoError, setNuevoError] = useState('');

  // Modal editar evento
  const [eventoEditando, setEventoEditando] = useState<EventoItem | null>(null);
  const [editError, setEditError] = useState('');

  useEffect(() => {
    try {
      localStorage.setItem(STORAGE_USERS, JSON.stringify(usuarios));
    } catch {
      // Ignorar si almacenamiento no está disponible
    }
  }, [usuarios]);

  useEffect(() => {
    try {
      localStorage.setItem(STORAGE_EVENTS, JSON.stringify(eventos));
    } catch {
      // Ignorar
    }
  }, [eventos]);

  useEffect(() => {
    try {
      if (usuarioActivoId) {
        localStorage.setItem(STORAGE_SESSION, String(usuarioActivoId));
      } else {
        localStorage.removeItem(STORAGE_SESSION);
      }
    } catch {
      // Ignorar
    }
  }, [usuarioActivoId]);

  useEffect(() => {
    const handleClickOutside = (e: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        setMenuUsuarioAbierto(false);
      }
    };
    const handleEsc = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        setMenuUsuarioAbierto(false);
        setEventoEditando(null);
        setConfirmacion(null);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleEsc);
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleEsc);
    };
  }, []);

  // Debounce de 300ms en Explorar
  useEffect(() => {
    setExploreCargando(true);
    const timer = window.setTimeout(() => {
      setExploreDebounced(exploreInput.trim());
      setExploreCargando(false);
    }, 300);
    return () => window.clearTimeout(timer);
  }, [exploreInput, exploreCat]);

  const mostrarToast = (mensaje: string) => {
    setToast(mensaje);
    if (toastTimer.current) window.clearTimeout(toastTimer.current);
    toastTimer.current = window.setTimeout(() => setToast(''), 3200);
  };

  const nombreCategoria = (id: number): string =>
    CATEGORIAS_INICIALES.find((c) => c.id === id)?.nombre ?? 'General';

  const eventosDelUsuario = useMemo(() => {
    if (!usuarioActivo) return [];
    return eventos
      .filter((e) => e.usuarioId === usuarioActivo.id)
      .sort((a, b) => `${a.fecha} ${a.hora}`.localeCompare(`${b.fecha} ${b.hora}`));
  }, [eventos, usuarioActivo]);

  const eventosFiltrados = useMemo(() => {
    const q = busqueda.trim().toLowerCase();
    return eventosDelUsuario.filter((ev) => {
      if (categoriaFiltro > 0 && ev.categoriaId !== categoriaFiltro) return false;
      if (!q) return true;
      const cat = nombreCategoria(ev.categoriaId).toLowerCase();
      const tituloMatch = enTitulo && ev.titulo.toLowerCase().includes(q);
      const catMatch = enCategoria && cat.includes(q);
      const descMatch = enDescripcion && ev.descripcion.toLowerCase().includes(q);
      if (!enTitulo && !enCategoria && !enDescripcion) {
        return ev.titulo.toLowerCase().includes(q);
      }
      return tituloMatch || catMatch || descMatch;
    });
  }, [eventosDelUsuario, busqueda, categoriaFiltro, enTitulo, enCategoria, enDescripcion]);

  const eventosExplorar = useMemo(() => {
    const q = exploreDebounced.toLowerCase();
    return eventosDelUsuario.filter((ev) => {
      if (exploreCat !== '' && String(ev.categoriaId) !== exploreCat) return false;
      if (!q) return true;
      const cat = nombreCategoria(ev.categoriaId).toLowerCase();
      return (
        ev.titulo.toLowerCase().includes(q) ||
        cat.includes(q) ||
        ev.descripcion.toLowerCase().includes(q)
      );
    });
  }, [eventosDelUsuario, exploreDebounced, exploreCat]);

  const iniciarComoVisitante = () => {
    const existente = usuarios.find((u) => u.correo === 'visitante@agendaweb.local');
    if (existente) {
      setUsuarioActivoId(existente.id);
      setVista('dashboard');
      return;
    }
    const nuevoId = Date.now();
    const visitante: UsuarioPreview = {
      id: nuevoId,
      nombre: 'Elena Bosque',
      correo: 'visitante@agendaweb.local',
    };
    setUsuarios((prev) => [...prev, visitante]);
    setEventos((prev) => [...prev, ...EVENTOS_SEMILLA(nuevoId)]);
    setUsuarioActivoId(nuevoId);
    setVista('dashboard');
  };

  const manejarLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoginError('');
    const correo = loginCorreo.trim().toLowerCase();
    if (!correo || !loginClave) {
      setLoginError('Correo o contraseña incorrectos');
      return;
    }
    const usuario = usuarios.find((u) => u.correo.toLowerCase() === correo);
    if (!usuario || !usuario.claveHashSimulada) {
      setLoginError('Correo o contraseña incorrectos');
      return;
    }
    const hashIngresado = await hashTexto(loginClave);
    if (hashIngresado !== usuario.claveHashSimulada) {
      setLoginError('Correo o contraseña incorrectos');
      return;
    }
    setUsuarioActivoId(usuario.id);
    setLoginClave('');
    setVista('dashboard');
  };

  const manejarRegistro = async (e: React.FormEvent) => {
    e.preventDefault();
    setRegError('');
    const nombre = regNombre.trim();
    const correo = regCorreo.trim().toLowerCase();
    if (nombre.length < 2) {
      setRegError('Ingresa tu nombre (al menos 2 caracteres).');
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
      setRegError('Ingresa un correo electrónico válido.');
      return;
    }
    if (regClave.length < 8) {
      setRegError('La contraseña debe tener al menos 8 caracteres.');
      return;
    }
    if (regClave !== regConfirmar) {
      setRegError('Las contraseñas no coinciden.');
      return;
    }
    if (usuarios.some((u) => u.correo.toLowerCase() === correo)) {
      setRegError('Este correo electrónico ya se encuentra registrado.');
      return;
    }

    const claveHashSimulada = await hashTexto(regClave);
    const nuevoId = Date.now();
    const nuevoUsuario: UsuarioPreview = {
      id: nuevoId,
      nombre,
      correo,
      claveHashSimulada,
    };
    setUsuarios((prev) => [...prev, nuevoUsuario]);
    setEventos((prev) => [...prev, ...EVENTOS_SEMILLA(nuevoId)]);
    setUsuarioActivoId(nuevoId);
    setRegClave('');
    setRegConfirmar('');
    setVista('dashboard');
    mostrarToast('Cuenta creada y sesión iniciada');
  };

  const cerrarSesion = () => {
    setMenuUsuarioAbierto(false);
    setUsuarioActivoId(null);
    setVista('bienvenida');
  };

  const eliminarCuenta = () => {
    if (!usuarioActivo) return;
    setMenuUsuarioAbierto(false);
    setConfirmacion({
      titulo: 'Eliminar cuenta',
      mensaje:
        'Esta acción eliminará tu cuenta y todos tus eventos de forma permanente. ¿Deseas continuar?',
      onConfirmar: () => {
        const uid = usuarioActivo.id;
        setEventos((prev) => prev.filter((ev) => ev.usuarioId !== uid));
        setUsuarios((prev) => prev.filter((u) => u.id !== uid));
        setUsuarioActivoId(null);
        setConfirmacion(null);
        setVista('bienvenida');
        mostrarToast('Tu cuenta fue eliminada');
      },
    });
  };

  const crearEvento = (e: React.FormEvent) => {
    e.preventDefault();
    if (!usuarioActivo) return;
    setNuevoError('');
    if (!nuevoTitulo.trim() || !nuevoFecha || !nuevoHora) {
      setNuevoError('Completa título, fecha y hora.');
      return;
    }
    const nuevo: EventoItem = {
      id: Date.now(),
      usuarioId: usuarioActivo.id,
      titulo: nuevoTitulo.trim(),
      fecha: nuevoFecha,
      hora: nuevoHora,
      categoriaId: nuevoCategoriaId,
      descripcion: nuevoDescripcion.trim(),
    };
    setEventos((prev) => [...prev, nuevo]);
    setNuevoTitulo('');
    setNuevoDescripcion('');
    setVista('dashboard');
    mostrarToast('Evento registrado correctamente');
  };

  const guardarEdicion = (e: React.FormEvent) => {
    e.preventDefault();
    if (!eventoEditando || !usuarioActivo) return;
    setEditError('');
    if (!eventoEditando.titulo.trim() || !eventoEditando.fecha || !eventoEditando.hora) {
      setEditError('Completa todos los campos obligatorios.');
      return;
    }
    setEventos((prev) =>
      prev.map((ev) =>
        ev.id === eventoEditando.id && ev.usuarioId === usuarioActivo.id
          ? {
              ...eventoEditando,
              titulo: eventoEditando.titulo.trim(),
              descripcion: eventoEditando.descripcion.trim(),
            }
          : ev
      )
    );
    setEventoEditando(null);
    mostrarToast('Evento actualizado correctamente');
  };

  const eliminarEvento = (id: number) => {
    if (!usuarioActivo) return;
    setConfirmacion({
      titulo: 'Eliminar evento',
      mensaje: '¿Estás seguro de que deseas eliminar este evento de tu agenda?',
      onConfirmar: () => {
        setEventos((prev) =>
          prev.filter((ev) => !(ev.id === id && ev.usuarioId === usuarioActivo.id))
        );
        setConfirmacion(null);
        mostrarToast('Evento eliminado de tu agenda');
      },
    });
  };

  const inicialAvatarLogin = loginCorreo.trim()
    ? loginCorreo.trim().charAt(0).toUpperCase()
    : 'A';
  const inicialAvatarReg = regNombre.trim()
    ? regNombre.trim().charAt(0).toUpperCase()
    : 'N';

  return (
    <div className="flex min-h-screen flex-col">
      {/* Vistas sin sesión: Bienvenida, Login, Registro */}
      {!usuarioActivo && vista === 'bienvenida' && (
        <main className="flex flex-1 items-center justify-center px-5 py-10">
          <section className="glass-surface w-full max-w-[540px] rounded-[24px] px-8 py-12 text-center">
            <div className="mx-auto mb-6 flex h-[74px] w-[74px] items-center justify-center rounded-[22px] bg-gradient-to-br from-[#3b7324] to-[#224614] text-white shadow-[0_18px_36px_-10px_rgba(45,90,27,0.42)]">
              <CalendarCheck className="h-9 w-9" />
            </div>
            <h1 className="font-display text-4xl font-bold tracking-tight text-[#1a1f18]">
              AgendaWeb
            </h1>
            <p className="mt-3 text-base leading-relaxed text-[#4a5546]">
              Organiza tus compromisos diarios con claridad y serenidad. Un refugio personal para planificar tu tiempo sin distracciones.
            </p>
            <div className="mt-8 flex flex-col items-center gap-3">
              <button
                type="button"
                onClick={() => setVista('login')}
                className="min-w-[220px] rounded-full bg-[#2d5a1b] px-7 py-3 text-sm font-medium text-white shadow-[0_6px_18px_-4px_rgba(45,90,27,0.35)] transition hover:-translate-y-0.5 hover:bg-[#224614]"
              >
                Comenzar
              </button>
              <button
                type="button"
                onClick={iniciarComoVisitante}
                className="rounded-full border border-[#5c6b57]/30 bg-white/75 px-6 py-2.5 text-sm font-medium text-[#1a1f18] transition hover:bg-white"
              >
                Explorar vista previa interactiva
              </button>
              <button
                type="button"
                onClick={() => setVista('registro')}
                className="mt-1 text-sm font-medium text-[#2d5a1b] hover:underline"
              >
                Crear una cuenta nueva
              </button>
            </div>
          </section>
        </main>
      )}

      {!usuarioActivo && vista === 'login' && (
        <main className="flex flex-1 items-center justify-center px-5 py-10">
          <section className="glass-surface w-full max-w-[440px] rounded-[24px] p-8">
            <div className="mb-6 text-center">
              <div className="font-display mx-auto mb-4 flex h-[68px] w-[68px] items-center justify-center rounded-full bg-[#2d5a1b] text-2xl font-semibold text-white shadow-[0_10px_24px_rgba(45,90,27,0.22)]">
                {inicialAvatarLogin}
              </div>
              <h1 className="font-display text-2xl font-bold text-[#1a1f18]">
                Bienvenido de nuevo
              </h1>
              <p className="mt-1 text-sm text-[#4a5546]">
                Accede a tu espacio personal en AgendaWeb
              </p>
            </div>

            {loginError && (
              <div
                role="alert"
                className="mb-4 rounded-2xl border border-[#b42318]/25 bg-[#b42318]/10 px-4 py-3 text-sm font-medium text-[#b42318]"
              >
                {loginError}
              </div>
            )}

            <form onSubmit={manejarLogin} className="space-y-4">
              <div>
                <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-login-email">
                  Correo electrónico
                </label>
                <input
                  id="p-login-email"
                  type="email"
                  value={loginCorreo}
                  onChange={(e) => setLoginCorreo(e.target.value)}
                  placeholder="tu@correo.com"
                  className="bosque-input w-full rounded-[14px] px-3.5 py-2.5 text-sm text-[#1a1f18]"
                  required
                />
              </div>

              <div>
                <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-login-pass">
                  Contraseña
                </label>
                <div className="relative flex items-center">
                  <input
                    id="p-login-pass"
                    type={loginVerClave ? 'text' : 'password'}
                    value={loginClave}
                    onChange={(e) => setLoginClave(e.target.value)}
                    placeholder="Ingresa tu contraseña"
                    className="bosque-input w-full rounded-[14px] py-2.5 pr-11 pl-3.5 text-sm text-[#1a1f18]"
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setLoginVerClave((v) => !v)}
                    aria-label={loginVerClave ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                    className="absolute right-2 flex h-8 w-8 items-center justify-center rounded-full text-[#697564] transition hover:bg-[#2d5a1b]/10 hover:text-[#2d5a1b]"
                  >
                    {loginVerClave ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
              </div>

              <button
                type="submit"
                className="w-full rounded-full bg-[#2d5a1b] py-2.5 text-sm font-medium text-white shadow-[0_6px_18px_-4px_rgba(45,90,27,0.35)] transition hover:bg-[#224614]"
              >
                Iniciar sesión
              </button>
            </form>

            <div className="mt-5 space-y-2 text-center text-sm text-[#4a5546]">
              <p>
                ¿Aún no tienes cuenta?{' '}
                <button
                  type="button"
                  onClick={() => {
                    setLoginError('');
                    setVista('registro');
                  }}
                  className="font-medium text-[#2d5a1b] hover:underline"
                >
                  Crear una cuenta
                </button>
              </p>
              <p>
                <button
                  type="button"
                  onClick={() => setVista('bienvenida')}
                  className="text-[#4a5546] hover:text-[#1a1f18]"
                >
                  Volver al inicio
                </button>
              </p>
            </div>
          </section>
        </main>
      )}

      {!usuarioActivo && vista === 'registro' && (
        <main className="flex flex-1 items-center justify-center px-5 py-10">
          <section className="glass-surface w-full max-w-[440px] rounded-[24px] p-8">
            <div className="mb-6 text-center">
              <div className="font-display mx-auto mb-4 flex h-[68px] w-[68px] items-center justify-center rounded-full bg-[#2d5a1b] text-2xl font-semibold text-white shadow-[0_10px_24px_rgba(45,90,27,0.22)]">
                {inicialAvatarReg}
              </div>
              <h1 className="font-display text-2xl font-bold text-[#1a1f18]">Crear cuenta</h1>
              <p className="mt-1 text-sm text-[#4a5546]">
                Organiza tus eventos personales con serenidad
              </p>
            </div>

            {regError && (
              <div
                role="alert"
                className="mb-4 rounded-2xl border border-[#b42318]/25 bg-[#b42318]/10 px-4 py-3 text-sm font-medium text-[#b42318]"
              >
                {regError}
              </div>
            )}

            <form onSubmit={manejarRegistro} className="space-y-4">
              <div>
                <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-reg-name">
                  Nombre completo
                </label>
                <input
                  id="p-reg-name"
                  type="text"
                  value={regNombre}
                  onChange={(e) => setRegNombre(e.target.value)}
                  placeholder="Ej. Sofía Mendoza"
                  className="bosque-input w-full rounded-[14px] px-3.5 py-2.5 text-sm text-[#1a1f18]"
                  required
                />
              </div>

              <div>
                <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-reg-email">
                  Correo electrónico
                </label>
                <input
                  id="p-reg-email"
                  type="email"
                  value={regCorreo}
                  onChange={(e) => setRegCorreo(e.target.value)}
                  placeholder="tu@correo.com"
                  className="bosque-input w-full rounded-[14px] px-3.5 py-2.5 text-sm text-[#1a1f18]"
                  required
                />
              </div>

              <div>
                <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-reg-pass">
                  Contraseña (mínimo 8 caracteres)
                </label>
                <div className="relative flex items-center">
                  <input
                    id="p-reg-pass"
                    type={regVerClave ? 'text' : 'password'}
                    value={regClave}
                    onChange={(e) => setRegClave(e.target.value)}
                    placeholder="Mínimo 8 caracteres"
                    className="bosque-input w-full rounded-[14px] py-2.5 pr-11 pl-3.5 text-sm text-[#1a1f18]"
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setRegVerClave((v) => !v)}
                    aria-label={regVerClave ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                    className="absolute right-2 flex h-8 w-8 items-center justify-center rounded-full text-[#697564] transition hover:bg-[#2d5a1b]/10 hover:text-[#2d5a1b]"
                  >
                    {regVerClave ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
              </div>

              <div>
                <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-reg-confirm">
                  Confirmar contraseña
                </label>
                <div className="relative flex items-center">
                  <input
                    id="p-reg-confirm"
                    type={regVerConfirmar ? 'text' : 'password'}
                    value={regConfirmar}
                    onChange={(e) => setRegConfirmar(e.target.value)}
                    placeholder="Repite tu contraseña"
                    className="bosque-input w-full rounded-[14px] py-2.5 pr-11 pl-3.5 text-sm text-[#1a1f18]"
                    required
                  />
                  <button
                    type="button"
                    onClick={() => setRegVerConfirmar((v) => !v)}
                    aria-label={regVerConfirmar ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                    className="absolute right-2 flex h-8 w-8 items-center justify-center rounded-full text-[#697564] transition hover:bg-[#2d5a1b]/10 hover:text-[#2d5a1b]"
                  >
                    {regVerConfirmar ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                  </button>
                </div>
              </div>

              <button
                type="submit"
                className="w-full rounded-full bg-[#2d5a1b] py-2.5 text-sm font-medium text-white shadow-[0_6px_18px_-4px_rgba(45,90,27,0.35)] transition hover:bg-[#224614]"
              >
                Registrarme
              </button>
            </form>

            <div className="mt-5 text-center text-sm text-[#4a5546]">
              ¿Ya tienes cuenta?{' '}
              <button
                type="button"
                onClick={() => {
                  setRegError('');
                  setVista('login');
                }}
                className="font-medium text-[#2d5a1b] hover:underline"
              >
                Iniciar sesión
              </button>
            </div>
          </section>
        </main>
      )}

      {/* Vistas autenticadas */}
      {usuarioActivo && (
        <div className="mx-auto w-full max-w-[1120px] flex-1 px-6 pt-5 pb-12">
          {/* Header flotante */}
          <header className="glass-surface-strong sticky top-4 z-40 mb-8 flex flex-wrap items-center justify-between gap-4 rounded-full px-5 py-3">
            <button
              type="button"
              onClick={() => setVista('dashboard')}
              className="font-display flex shrink-0 items-center gap-2.5 text-lg font-bold text-[#1a1f18]"
            >
              <span className="flex h-8 w-8 items-center justify-center rounded-[11px] bg-gradient-to-br from-[#3a7024] to-[#244815] text-white shadow-sm">
                <Calendar className="h-4 w-4" />
              </span>
              <span className="whitespace-nowrap">AgendaWeb</span>
            </button>

            <nav className="flex items-center gap-1.5" aria-label="Navegación principal">
              <button
                type="button"
                onClick={() => setVista('dashboard')}
                className={`whitespace-nowrap rounded-full px-4 py-1.5 text-sm font-medium transition ${
                  vista === 'dashboard'
                    ? 'bg-[#2d5a1b] text-white'
                    : 'text-[#4a5546] hover:bg-[#2d5a1b]/10 hover:text-[#1a1f18]'
                }`}
              >
                Mis eventos
              </button>
              <button
                type="button"
                onClick={() => setVista('explorar')}
                className={`whitespace-nowrap rounded-full px-4 py-1.5 text-sm font-medium transition ${
                  vista === 'explorar'
                    ? 'bg-[#2d5a1b] text-white'
                    : 'text-[#4a5546] hover:bg-[#2d5a1b]/10 hover:text-[#1a1f18]'
                }`}
              >
                Explorar
              </button>
              <button
                type="button"
                onClick={() => setVista('registrar')}
                className={`whitespace-nowrap rounded-full px-4 py-1.5 text-sm font-medium transition ${
                  vista === 'registrar'
                    ? 'bg-[#2d5a1b] text-white'
                    : 'text-[#4a5546] hover:bg-[#2d5a1b]/10 hover:text-[#1a1f18]'
                }`}
              >
                Nuevo evento
              </button>
            </nav>

            <div className="relative shrink-0" ref={menuRef}>
              <button
                type="button"
                onClick={() => setMenuUsuarioAbierto((o) => !o)}
                aria-expanded={menuUsuarioAbierto}
                className="flex items-center gap-2 rounded-full border border-white/80 bg-white/75 py-1 pr-3 pl-1 text-sm font-medium text-[#1a1f18] transition hover:-translate-y-0.5"
              >
                <span className="font-display flex h-8 w-8 items-center justify-center rounded-full bg-[#2d5a1b] text-sm font-semibold text-white uppercase">
                  {usuarioActivo.nombre.charAt(0)}
                </span>
                <span className="max-w-[140px] truncate">{usuarioActivo.nombre}</span>
              </button>

              {menuUsuarioAbierto && (
                <div className="glass-surface-strong absolute right-0 z-50 mt-2.5 w-64 rounded-[20px] p-4 shadow-xl">
                  <div className="mb-3 border-b border-[#1a1f18]/10 pb-3">
                    <p className="text-sm font-semibold text-[#1a1f18]">{usuarioActivo.nombre}</p>
                    <p className="truncate text-xs text-[#697564]">{usuarioActivo.correo}</p>
                  </div>
                  <div className="flex flex-col gap-2">
                    <button
                      type="button"
                      onClick={cerrarSesion}
                      className="w-full rounded-full border border-[#5c6b57]/25 bg-white/80 px-4 py-2 text-xs font-medium text-[#1a1f18] transition hover:bg-white"
                    >
                      Cerrar sesión
                    </button>
                    <button
                      type="button"
                      onClick={eliminarCuenta}
                      className="w-full rounded-full border border-[#b42318]/25 bg-[#b42318]/10 px-4 py-2 text-xs font-medium text-[#b42318] transition hover:bg-[#b42318] hover:text-white"
                    >
                      Eliminar cuenta
                    </button>
                  </div>
                </div>
              )}
            </div>
          </header>

          {/* Vista Dashboard */}
          {vista === 'dashboard' && (
            <main>
              <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                  <h1 className="font-display text-3xl font-bold text-[#1a1f18]">Tus eventos</h1>
                  <p className="mt-1 text-sm text-[#4a5546]">
                    Gestiona tu agenda personal con tranquilidad y precisión.
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => setVista('registrar')}
                  className="inline-flex items-center gap-1.5 rounded-full bg-[#2d5a1b] px-5 py-2.5 text-sm font-medium text-white shadow-[0_6px_18px_-4px_rgba(45,90,27,0.35)] transition hover:-translate-y-0.5 hover:bg-[#224614]"
                >
                  <Plus className="h-4 w-4" />
                  Nuevo evento
                </button>
              </div>

              {/* Barra de búsqueda avanzada */}
              <section className="glass-surface mb-7 rounded-[24px] p-4">
                <div className="flex flex-wrap items-center gap-3">
                  <div className="relative min-w-[240px] flex-1">
                    <Search className="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#697564]" />
                    <input
                      type="search"
                      value={busqueda}
                      onChange={(e) => setBusqueda(e.target.value)}
                      placeholder="Buscar eventos por título, categoría o descripción..."
                      className="bosque-input w-full rounded-full py-2.5 pr-4 pl-10 text-sm text-[#1a1f18]"
                    />
                  </div>

                  <select
                    value={categoriaFiltro}
                    onChange={(e) => setCategoriaFiltro(Number(e.target.value))}
                    aria-label="Filtrar por categoría"
                    className="bosque-input rounded-full px-4 py-2.5 text-sm text-[#1a1f18]"
                  >
                    <option value={0}>Todas las categorías</option>
                    {CATEGORIAS_INICIALES.map((cat) => (
                      <option key={cat.id} value={cat.id}>
                        {cat.nombre}
                      </option>
                    ))}
                  </select>

                  <button
                    type="button"
                    onClick={() => setFiltrosAbiertos((o) => !o)}
                    aria-expanded={filtrosAbiertos}
                    className="inline-flex items-center gap-2 rounded-full border border-[#5c6b57]/25 bg-white/80 px-4 py-2.5 text-sm font-medium text-[#1a1f18] transition hover:bg-white"
                  >
                    <SlidersHorizontal className="h-4 w-4" />
                    Filtros
                  </button>
                </div>

                {filtrosAbiertos && (
                  <div className="mt-4 grid grid-cols-1 gap-4 border-t border-[#1a1f18]/10 pt-4 sm:grid-cols-3">
                    {[
                      { label: 'Buscar en Título', checked: enTitulo, onChange: setEnTitulo },
                      {
                        label: 'Buscar en Categoría',
                        checked: enCategoria,
                        onChange: setEnCategoria,
                      },
                      {
                        label: 'Buscar en Descripción',
                        checked: enDescripcion,
                        onChange: setEnDescripcion,
                      },
                    ].map((sw) => (
                      <label
                        key={sw.label}
                        className="flex cursor-pointer items-center justify-between gap-3 text-sm text-[#1a1f18]"
                      >
                        <span>{sw.label}</span>
                        <button
                          type="button"
                          role="switch"
                          aria-checked={sw.checked}
                          onClick={() => sw.onChange(!sw.checked)}
                          className={`relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors ${
                            sw.checked ? 'bg-[#2d5a1b]' : 'bg-[#5c6b57]/30'
                          }`}
                        >
                          <span
                            className={`inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform ${
                              sw.checked ? 'translate-x-6' : 'translate-x-1'
                            }`}
                          />
                        </button>
                      </label>
                    ))}
                  </div>
                )}
              </section>

              {eventosFiltrados.length === 0 ? (
                <section className="glass-surface rounded-[24px] px-6 py-14 text-center">
                  <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#2d5a1b]/10 text-[#2d5a1b]">
                    <Calendar className="h-6 w-6" />
                  </div>
                  <h2 className="font-display text-xl font-semibold text-[#1a1f18]">
                    No hay eventos para mostrar
                  </h2>
                  <p className="mx-auto mt-2 max-w-md text-sm text-[#4a5546]">
                    {busqueda || categoriaFiltro > 0
                      ? 'Prueba ajustando tu búsqueda o limpiando los filtros activos.'
                      : 'Tu agenda está despejada. Crea tu primer evento para comenzar.'}
                  </p>
                  <button
                    type="button"
                    onClick={() => setVista('registrar')}
                    className="mt-5 rounded-full bg-[#2d5a1b] px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#224614]"
                  >
                    Registrar primer evento
                  </button>
                </section>
              ) : (
                <section className="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                  {eventosFiltrados.map((ev) => {
                    const cat = nombreCategoria(ev.categoriaId);
                    return (
                      <article
                        key={ev.id}
                        className="glass-surface event-card-glow flex flex-col justify-between gap-4 rounded-[24px] p-5"
                      >
                        <div>
                          <div className="flex items-start justify-between gap-3">
                            <div>
                              <h2 className="font-display text-lg font-semibold text-[#1a1f18]">
                                {ev.titulo}
                              </h2>
                              <p className="mt-1 text-xs text-[#697564] tabular-nums">
                                {ev.fecha} · {ev.hora}
                              </p>
                            </div>
                            <span
                              className={`shrink-0 rounded-full px-3 py-1 text-xs font-semibold ${claseBadge(
                                cat
                              )}`}
                            >
                              {cat}
                            </span>
                          </div>
                          <p className="mt-3 text-sm leading-relaxed text-[#4a5546]">
                            {ev.descripcion || 'Sin descripción adicional.'}
                          </p>
                        </div>

                        <div className="flex items-center justify-end gap-2 border-t border-[#1a1f18]/10 pt-3">
                          <button
                            type="button"
                            onClick={() => {
                              setEditError('');
                              setEventoEditando({ ...ev });
                            }}
                            className="rounded-full border border-[#5c6b57]/25 bg-white/80 px-4 py-1.5 text-xs font-medium text-[#1a1f18] transition hover:bg-white"
                          >
                            Editar
                          </button>
                          <button
                            type="button"
                            onClick={() => eliminarEvento(ev.id)}
                            className="rounded-full border border-[#b42318]/25 bg-[#b42318]/10 px-4 py-1.5 text-xs font-medium text-[#b42318] transition hover:bg-[#b42318] hover:text-white"
                          >
                            Eliminar
                          </button>
                        </div>
                      </article>
                    );
                  })}
                </section>
              )}
            </main>
          )}

          {/* Vista Explorar (debounce + renderizado dinámico) */}
          {vista === 'explorar' && (
            <main>
              <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                  <h1 className="font-display text-3xl font-bold text-[#1a1f18]">
                    Explorar en tiempo real
                  </h1>
                  <p className="mt-1 text-sm text-[#4a5546]">
                    Búsqueda dinámica con debounce de 300 ms sobre tus eventos personales.
                  </p>
                </div>
                <button
                  type="button"
                  onClick={() => setVista('registrar')}
                  className="inline-flex items-center gap-1.5 rounded-full bg-[#2d5a1b] px-5 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#224614]"
                >
                  <Plus className="h-4 w-4" />
                  Nuevo evento
                </button>
              </div>

              <section className="glass-surface mb-5 rounded-[24px] p-4">
                <div className="flex flex-wrap items-center gap-3">
                  <div className="relative min-w-[240px] flex-1">
                    <Search className="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#697564]" />
                    <input
                      type="search"
                      value={exploreInput}
                      onChange={(e) => setExploreInput(e.target.value)}
                      placeholder="Escribe para filtrar instantáneamente..."
                      className="bosque-input w-full rounded-full py-2.5 pr-4 pl-10 text-sm text-[#1a1f18]"
                    />
                  </div>

                  <select
                    value={exploreCat}
                    onChange={(e) => setExploreCat(e.target.value)}
                    aria-label="Filtrar por categoría"
                    className="bosque-input rounded-full px-4 py-2.5 text-sm text-[#1a1f18]"
                  >
                    <option value="">Todas las categorías</option>
                    {CATEGORIAS_INICIALES.map((cat) => (
                      <option key={cat.id} value={String(cat.id)}>
                        {cat.nombre}
                      </option>
                    ))}
                  </select>
                </div>
              </section>

              <div className="mb-4 text-sm text-[#4a5546] tabular-nums">
                {exploreCargando
                  ? 'Buscando eventos...'
                  : eventosExplorar.length === 1
                    ? '1 evento encontrado'
                    : `${eventosExplorar.length} eventos encontrados`}
              </div>

              {eventosExplorar.length === 0 ? (
                <section className="glass-surface rounded-[24px] px-6 py-12 text-center text-sm text-[#4a5546]">
                  No se encontraron eventos con los criterios seleccionados.
                </section>
              ) : (
                <section className="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                  {eventosExplorar.map((ev) => {
                    const cat = nombreCategoria(ev.categoriaId);
                    return (
                      <article
                        key={ev.id}
                        className="glass-surface event-card-glow rounded-[24px] p-5"
                      >
                        <div className="flex items-start justify-between gap-3">
                          <div>
                            <h2 className="font-display text-lg font-semibold text-[#1a1f18]">
                              {ev.titulo}
                            </h2>
                            <p className="mt-1 text-xs text-[#697564] tabular-nums">
                              {ev.fecha} · {ev.hora}
                            </p>
                          </div>
                          <span
                            className={`shrink-0 rounded-full px-3 py-1 text-xs font-semibold ${claseBadge(
                              cat
                            )}`}
                          >
                            {cat}
                          </span>
                        </div>
                        <p className="mt-3 text-sm leading-relaxed text-[#4a5546]">
                          {ev.descripcion || 'Sin descripción adicional.'}
                        </p>
                      </article>
                    );
                  })}
                </section>
              )}
            </main>
          )}

          {/* Vista Registrar Evento */}
          {vista === 'registrar' && (
            <main>
              <section className="glass-surface mx-auto max-w-[640px] rounded-[24px] p-8">
                <h1 className="font-display text-2xl font-bold text-[#1a1f18]">
                  Registrar nuevo evento
                </h1>
                <p className="mt-1 mb-6 text-sm text-[#4a5546]">
                  Añade un compromiso, recordatorio o actividad a tu agenda personal.
                </p>

                {nuevoError && (
                  <div
                    role="alert"
                    className="mb-4 rounded-2xl border border-[#b42318]/25 bg-[#b42318]/10 px-4 py-3 text-sm font-medium text-[#b42318]"
                  >
                    {nuevoError}
                  </div>
                )}

                <form onSubmit={crearEvento} className="space-y-4">
                  <div>
                    <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-new-title">
                      Título del evento
                    </label>
                    <input
                      id="p-new-title"
                      type="text"
                      value={nuevoTitulo}
                      onChange={(e) => setNuevoTitulo(e.target.value)}
                      placeholder="Ej. Reunión de planificación trimestral"
                      className="bosque-input w-full rounded-[14px] px-3.5 py-2.5 text-sm text-[#1a1f18]"
                      required
                    />
                  </div>

                  <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                      <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-new-date">
                        Fecha
                      </label>
                      <input
                        id="p-new-date"
                        type="date"
                        value={nuevoFecha}
                        onChange={(e) => setNuevoFecha(e.target.value)}
                        className="bosque-input w-full rounded-[14px] px-3.5 py-2.5 text-sm text-[#1a1f18]"
                        required
                      />
                    </div>
                    <div>
                      <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-new-time">
                        Hora
                      </label>
                      <input
                        id="p-new-time"
                        type="time"
                        value={nuevoHora}
                        onChange={(e) => setNuevoHora(e.target.value)}
                        className="bosque-input w-full rounded-[14px] px-3.5 py-2.5 text-sm text-[#1a1f18]"
                        required
                      />
                    </div>
                  </div>

                  <div>
                    <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-new-cat">
                      Categoría
                    </label>
                    <select
                      id="p-new-cat"
                      value={nuevoCategoriaId}
                      onChange={(e) => setNuevoCategoriaId(Number(e.target.value))}
                      className="bosque-input w-full rounded-[14px] px-3.5 py-2.5 text-sm text-[#1a1f18]"
                    >
                      {CATEGORIAS_INICIALES.map((cat) => (
                        <option key={cat.id} value={cat.id}>
                          {cat.nombre}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-new-desc">
                      Descripción
                    </label>
                    <textarea
                      id="p-new-desc"
                      value={nuevoDescripcion}
                      onChange={(e) => setNuevoDescripcion(e.target.value)}
                      rows={4}
                      placeholder="Notas adicionales, enlaces o agenda de puntos..."
                      className="bosque-input w-full rounded-[14px] px-3.5 py-2.5 text-sm text-[#1a1f18]"
                    />
                  </div>

                  <div className="flex justify-end gap-3 pt-2">
                    <button
                      type="button"
                      onClick={() => setVista('dashboard')}
                      className="rounded-full border border-[#5c6b57]/25 bg-white/80 px-5 py-2.5 text-sm font-medium text-[#1a1f18] transition hover:bg-white"
                    >
                      Cancelar
                    </button>
                    <button
                      type="submit"
                      className="rounded-full bg-[#2d5a1b] px-6 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-[#224614]"
                    >
                      Guardar evento
                    </button>
                  </div>
                </form>
              </section>
            </main>
          )}
        </div>
      )}

      {/* Modal flotante de edición */}
      {eventoEditando && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-[#1a1f18]/35 p-4 backdrop-blur-md"
          role="dialog"
          aria-modal="true"
          onClick={(e) => {
            if (e.target === e.currentTarget) setEventoEditando(null);
          }}
        >
          <div className="glass-surface-strong w-full max-w-[520px] rounded-[24px] p-7">
            <div className="mb-5 flex items-center justify-between">
              <h2 className="font-display text-xl font-bold text-[#1a1f18]">Editar evento</h2>
              <button
                type="button"
                onClick={() => setEventoEditando(null)}
                aria-label="Cerrar modal"
                className="flex h-8 w-8 items-center justify-center rounded-full bg-[#1a1f18]/5 text-[#4a5546] hover:bg-[#1a1f18]/10"
              >
                <X className="h-4 w-4" />
              </button>
            </div>

            {editError && (
              <div className="mb-4 rounded-2xl border border-[#b42318]/25 bg-[#b42318]/10 px-4 py-3 text-sm font-medium text-[#b42318]">
                {editError}
              </div>
            )}

            <form onSubmit={guardarEdicion} className="space-y-4">
              <div>
                <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-edit-title">
                  Título
                </label>
                <input
                  id="p-edit-title"
                  type="text"
                  value={eventoEditando.titulo}
                  onChange={(e) =>
                    setEventoEditando({ ...eventoEditando, titulo: e.target.value })
                  }
                  className="bosque-input w-full rounded-[14px] px-3.5 py-2.5 text-sm text-[#1a1f18]"
                  required
                />
              </div>

              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-edit-date">
                    Fecha
                  </label>
                  <input
                    id="p-edit-date"
                    type="date"
                    value={eventoEditando.fecha}
                    onChange={(e) =>
                      setEventoEditando({ ...eventoEditando, fecha: e.target.value })
                    }
                    className="bosque-input w-full rounded-[14px] px-3.5 py-2.5 text-sm text-[#1a1f18]"
                    required
                  />
                </div>
                <div>
                  <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-edit-time">
                    Hora
                  </label>
                  <input
                    id="p-edit-time"
                    type="time"
                    value={eventoEditando.hora}
                    onChange={(e) =>
                      setEventoEditando({ ...eventoEditando, hora: e.target.value })
                    }
                    className="bosque-input w-full rounded-[14px] px-3.5 py-2.5 text-sm text-[#1a1f18]"
                    required
                  />
                </div>
              </div>

              <div>
                <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-edit-cat">
                  Categoría
                </label>
                <select
                  id="p-edit-cat"
                  value={eventoEditando.categoriaId}
                  onChange={(e) =>
                    setEventoEditando({
                      ...eventoEditando,
                      categoriaId: Number(e.target.value),
                    })
                  }
                  className="bosque-input w-full rounded-[14px] px-3.5 py-2.5 text-sm text-[#1a1f18]"
                >
                  {CATEGORIAS_INICIALES.map((cat) => (
                    <option key={cat.id} value={cat.id}>
                      {cat.nombre}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="mb-1.5 block text-sm font-medium text-[#4a5546]" htmlFor="p-edit-desc">
                  Descripción
                </label>
                <textarea
                  id="p-edit-desc"
                  rows={3}
                  value={eventoEditando.descripcion}
                  onChange={(e) =>
                    setEventoEditando({ ...eventoEditando, descripcion: e.target.value })
                  }
                  className="bosque-input w-full rounded-[14px] px-3.5 py-2.5 text-sm text-[#1a1f18]"
                />
              </div>

              <div className="flex justify-end gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={() => setEventoEditando(null)}
                  className="rounded-full border border-[#5c6b57]/25 bg-white/80 px-5 py-2 text-sm font-medium text-[#1a1f18]"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  className="rounded-full bg-[#2d5a1b] px-5 py-2 text-sm font-medium text-white hover:bg-[#224614]"
                >
                  Guardar cambios
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Modal de Confirmación */}
      {confirmacion && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-[#1a1f18]/35 p-4 backdrop-blur-md"
          role="dialog"
          aria-modal="true"
        >
          <div className="glass-surface-strong w-full max-w-[420px] rounded-[24px] p-6">
            <h2 className="font-display text-lg font-bold text-[#1a1f18]">
              {confirmacion.titulo}
            </h2>
            <p className="mt-2 text-sm text-[#4a5546]">{confirmacion.mensaje}</p>
            <div className="mt-5 flex justify-end gap-2.5">
              <button
                type="button"
                onClick={() => setConfirmacion(null)}
                className="rounded-full border border-[#5c6b57]/25 bg-white/80 px-4 py-2 text-sm font-medium text-[#1a1f18]"
              >
                Cancelar
              </button>
              <button
                type="button"
                onClick={confirmacion.onConfirmar}
                className="rounded-full bg-[#b42318] px-4 py-2 text-sm font-medium text-white hover:bg-[#911d13]"
              >
                Confirmar
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Pie discreto con acceso a los archivos PHP/SQL generados */}
      <footer className="mt-auto border-t border-[#1a1f18]/10 py-5 text-center text-xs text-[#697564]">
        <div className="mx-auto flex max-w-[1120px] flex-wrap items-center justify-between gap-3 px-6">
          <span>AgendaWeb · Gestión personal de eventos</span>
          <button
            type="button"
            onClick={() => setVisorCodigoAbierto(true)}
            className="inline-flex items-center gap-1.5 font-medium text-[#2d5a1b] hover:underline"
          >
            <FileCode2 className="h-3.5 w-3.5" />
            Ver código fuente PHP, MySQLi, CSS y JS
          </button>
        </div>
      </footer>

      {toast && (
        <div
          role="status"
          aria-live="polite"
          className="fixed right-6 bottom-6 z-50 rounded-full bg-[#1a1f18] px-5 py-2.5 text-sm font-medium text-white shadow-xl"
        >
          {toast}
        </div>
      )}

      <CodeViewerModal
        abierto={visorCodigoAbierto}
        onCerrar={() => setVisorCodigoAbierto(false)}
      />
    </div>
  );
}
