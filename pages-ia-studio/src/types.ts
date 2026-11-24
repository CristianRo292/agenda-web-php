export type VistaActiva =
  | 'bienvenida'
  | 'login'
  | 'registro'
  | 'dashboard'
  | 'explorar'
  | 'registrar';

export interface Categoria {
  id: number;
  nombre: string;
}

export interface EventoItem {
  id: number;
  usuarioId: number;
  titulo: string;
  fecha: string;
  hora: string;
  categoriaId: number;
  descripcion: string;
}

export interface UsuarioPreview {
  id: number;
  nombre: string;
  correo: string;
  claveHashSimulada?: string;
}
