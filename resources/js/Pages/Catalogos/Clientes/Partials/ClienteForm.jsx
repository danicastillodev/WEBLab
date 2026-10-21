import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';

const selectClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';

export const CLIENTE_VACIO = {
    nombre: '', rfc: '', telefono: '', calle: '', numero_exterior: '', numero_interior: '',
    colonia: '', codigo_postal: '', estado_id: '', municipio_id: '',
};

// La mayoría de los clientes del laboratorio son de Jalisco: el alta lo
// propone por defecto (si existe en el catálogo) y se puede cambiar.
export function clienteNuevo(estados) {
    const jalisco = estados.find((e) => e.nombre === 'Jalisco');
    return { ...CLIENTE_VACIO, estado_id: jalisco ? String(jalisco.id) : '' };
}

/**
 * Campos del cliente compartidos por Create y Edit. Sólo el nombre es
 * obligatorio; los demás datos pueden completarse después.
 */
export function formatDomicilio(cliente) {
    if (!cliente) return '';
    const calle = [cliente.calle, cliente.numero_exterior].filter(Boolean).join(' ');
    const interior = cliente.numero_interior ? `int. ${cliente.numero_interior}` : '';
    const cp = cliente.codigo_postal ? `C.P. ${cliente.codigo_postal}` : '';
    return [[calle, interior].filter(Boolean).join(' '), cliente.colonia, cp].filter(Boolean).join(', ');
}

export default function ClienteForm({ data, setData, errors, estados, municipios }) {
    const municipiosFiltrados = municipios.filter((m) => String(m.estado_id) === String(data.estado_id));

    return (
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div className="sm:col-span-2">
                <InputLabel htmlFor="nombre" value="Nombre o razón social" required />
                <TextInput
                    id="nombre"
                    value={data.nombre}
                    onChange={(e) => setData('nombre', e.target.value)}
                    className="mt-1 block w-full"
                    autoFocus
                />
                <InputError message={errors.nombre} className="mt-2" />
            </div>
            <div>
                <InputLabel htmlFor="rfc" value="RFC" />
                <TextInput
                    id="rfc"
                    value={data.rfc}
                    onChange={(e) => setData('rfc', e.target.value.toUpperCase())}
                    className="mt-1 block w-full font-mono uppercase"
                    maxLength={13}
                />
                <InputError message={errors.rfc} className="mt-2" />
            </div>
            <div>
                <InputLabel htmlFor="telefono" value="Teléfono" />
                <TextInput
                    id="telefono"
                    value={data.telefono}
                    onChange={(e) => setData('telefono', e.target.value.replace(/\D/g, ''))}
                    className="mt-1 block w-full"
                    inputMode="numeric"
                    maxLength={10}
                    placeholder="10 dígitos"
                />
                <InputError message={errors.telefono} className="mt-2" />
            </div>
            <div className="sm:col-span-2">
                <InputLabel htmlFor="calle" value="Calle" />
                <TextInput
                    id="calle"
                    value={data.calle}
                    onChange={(e) => setData('calle', e.target.value)}
                    className="mt-1 block w-full"
                />
                <InputError message={errors.calle} className="mt-2" />
            </div>
            <div>
                <InputLabel htmlFor="numero_exterior" value="Núm. exterior" />
                <TextInput
                    id="numero_exterior"
                    value={data.numero_exterior}
                    onChange={(e) => setData('numero_exterior', e.target.value)}
                    className="mt-1 block w-full"
                />
                <InputError message={errors.numero_exterior} className="mt-2" />
            </div>
            <div>
                <InputLabel htmlFor="numero_interior" value="Núm. interior" />
                <TextInput
                    id="numero_interior"
                    value={data.numero_interior}
                    onChange={(e) => setData('numero_interior', e.target.value)}
                    className="mt-1 block w-full"
                    placeholder="Opcional"
                />
                <InputError message={errors.numero_interior} className="mt-2" />
            </div>
            <div>
                <InputLabel htmlFor="colonia" value="Colonia" />
                <TextInput
                    id="colonia"
                    value={data.colonia}
                    onChange={(e) => setData('colonia', e.target.value)}
                    className="mt-1 block w-full"
                />
                <InputError message={errors.colonia} className="mt-2" />
            </div>
            <div>
                <InputLabel htmlFor="codigo_postal" value="Código postal" />
                <TextInput
                    id="codigo_postal"
                    value={data.codigo_postal}
                    onChange={(e) => setData('codigo_postal', e.target.value.replace(/\D/g, ''))}
                    className="mt-1 block w-full"
                    inputMode="numeric"
                    maxLength={5}
                />
                <InputError message={errors.codigo_postal} className="mt-2" />
            </div>
            <div>
                <InputLabel htmlFor="estado_id" value="Estado" />
                <select
                    id="estado_id"
                    value={data.estado_id}
                    onChange={(e) => setData((prev) => ({ ...prev, estado_id: e.target.value, municipio_id: '' }))}
                    className={selectClass}
                >
                    <option value="">— Selecciona —</option>
                    {estados.map((e) => <option key={e.id} value={e.id}>{e.nombre}</option>)}
                </select>
                <InputError message={errors.estado_id} className="mt-2" />
            </div>
            <div>
                <InputLabel htmlFor="municipio_id" value="Municipio" />
                <select
                    id="municipio_id"
                    value={data.municipio_id}
                    onChange={(e) => setData('municipio_id', e.target.value)}
                    disabled={!data.estado_id}
                    className={selectClass}
                >
                    <option value="">— Selecciona —</option>
                    {municipiosFiltrados.map((m) => <option key={m.id} value={m.id}>{m.nombre}</option>)}
                </select>
                <InputError message={errors.municipio_id} className="mt-2" />
            </div>
        </div>
    );
}
