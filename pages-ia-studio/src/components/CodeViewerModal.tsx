import { useEffect, useState } from 'react';
import { Check, Copy, FileCode2, X } from 'lucide-react';

const ARCHIVOS_PROYECTO = import.meta.glob(
  ['/db.sql', '/*.php', '/auth/*.php', '/data/*.php', '/js/*.js', '/styles/*.css'],
  { query: '?raw', import: 'default' }
) as Record<string, () => Promise<string>>;

interface CodeViewerModalProps {
  abierto: boolean;
  onCerrar: () => void;
}

export function CodeViewerModal({ abierto, onCerrar }: CodeViewerModalProps) {
  const rutas = Object.keys(ARCHIVOS_PROYECTO).sort();
  const [rutaActiva, setRutaActiva] = useState<string>(rutas[0] ?? '/db.sql');
  const [contenido, setContenido] = useState<string>('');
  const [cargando, setCargando] = useState<boolean>(false);
  const [copiado, setCopiado] = useState<boolean>(false);

  useEffect(() => {
    if (!abierto || !rutaActiva) return;
    const cargador = ARCHIVOS_PROYECTO[rutaActiva];
    if (!cargador) return;

    let cancelado = false;
    setCargando(true);
    cargador()
      .then((texto) => {
        if (!cancelado) {
          setContenido(texto);
        }
      })
      .finally(() => {
        if (!cancelado) setCargando(false);
      });

    return () => {
      cancelado = true;
    };
  }, [abierto, rutaActiva]);

  useEffect(() => {
    if (!abierto) return;
    const handleKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onCerrar();
    };
    window.addEventListener('keydown', handleKey);
    return () => window.removeEventListener('keydown', handleKey);
  }, [abierto, onCerrar]);

  if (!abierto) return null;

  const copiarCodigo = async () => {
    try {
      await navigator.clipboard.writeText(contenido);
      setCopiado(true);
      window.setTimeout(() => setCopiado(false), 1800);
    } catch {
      // Silencioso si el navegador bloquea el portapapeles
    }
  };

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-[#1a1f18]/35 p-4 backdrop-blur-md"
      role="dialog"
      aria-modal="true"
      aria-labelledby="code-viewer-title"
      onClick={(e) => {
        if (e.target === e.currentTarget) onCerrar();
      }}
    >
      <div className="glass-surface-strong flex h-[82vh] w-full max-w-5xl flex-col overflow-hidden rounded-[24px]">
        <div className="flex items-center justify-between border-b border-[#1a1f18]/10 px-6 py-4">
          <div className="flex items-center gap-2.5">
            <FileCode2 className="h-5 w-5 text-[#2d5a1b]" />
            <h2 id="code-viewer-title" className="font-display text-lg font-semibold text-[#1a1f18]">
              Archivos fuente PHP, MySQLi, CSS y JS
            </h2>
          </div>
          <button
            type="button"
            onClick={onCerrar}
            aria-label="Cerrar visor de archivos"
            className="flex h-9 w-9 items-center justify-center rounded-full bg-[#1a1f18]/5 text-[#4a5546] transition hover:bg-[#1a1f18]/10"
          >
            <X className="h-4 w-4" />
          </button>
        </div>

        <div className="grid min-h-0 flex-1 grid-cols-1 md:grid-cols-[260px_1fr]">
          <aside className="overflow-y-auto border-b border-[#1a1f18]/10 bg-white/40 p-3 md:border-r md:border-b-0">
            <p className="mb-2 px-2 text-xs font-medium text-[#697564]">
              Estructura del proyecto ({rutas.length} archivos)
            </p>
            <div className="flex flex-col gap-1">
              {rutas.map((ruta) => {
                const activa = ruta === rutaActiva;
                return (
                  <button
                    key={ruta}
                    type="button"
                    onClick={() => setRutaActiva(ruta)}
                    className={`truncate rounded-xl px-3 py-2 text-left text-xs font-medium transition ${
                      activa
                        ? 'bg-[#2d5a1b] text-white'
                        : 'text-[#1a1f18] hover:bg-[#2d5a1b]/10'
                    }`}
                  >
                    {ruta.slice(1)}
                  </button>
                );
              })}
            </div>
          </aside>

          <div className="flex min-h-0 flex-col bg-[#1a1f18] text-[#f0f4ee]">
            <div className="flex items-center justify-between border-b border-white/10 px-4 py-2.5">
              <span className="font-mono text-xs text-[#b8c7b2]">
                {rutaActiva.slice(1)}
              </span>
              <button
                type="button"
                onClick={copiarCodigo}
                className="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white transition hover:bg-white/20"
              >
                {copiado ? (
                  <>
                    <Check className="h-3.5 w-3.5" />
                    Copiado
                  </>
                ) : (
                  <>
                    <Copy className="h-3.5 w-3.5" />
                    Copiar
                  </>
                )}
              </button>
            </div>
            <pre className="min-h-0 flex-1 overflow-auto p-4 font-mono text-xs leading-relaxed">
              {cargando ? 'Cargando archivo...' : contenido}
            </pre>
          </div>
        </div>
      </div>
    </div>
  );
}
