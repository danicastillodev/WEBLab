const botonClass =
    'rounded p-1 text-indigo-600 transition hover:bg-indigo-50 hover:text-indigo-900 ' +
    'disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:bg-transparent';

const IconoAgregar = () => (
    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" aria-hidden="true">
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
    </svg>
);

const IconoEditar = () => (
    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" aria-hidden="true">
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"
        />
    </svg>
);

const IconoEliminar = () => (
    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor" aria-hidden="true">
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.2v.916m7.5 0a48.667 48.667 0 00-7.5 0"
        />
    </svg>
);

/**
 * Acciones en línea de un campo de catálogo: agregar siempre, editar y eliminar
 * sólo cuando hay un registro seleccionado.
 */
export default function FieldActions({ onAdd, onEdit, onDelete, disabled = false, seleccionado = false, etiqueta }) {
    return (
        <div className="flex items-center gap-0.5">
            <button type="button" onClick={onAdd} disabled={disabled} className={botonClass} title={`Agregar ${etiqueta}`}>
                <span className="sr-only">{`Agregar ${etiqueta}`}</span>
                <IconoAgregar />
            </button>
            {seleccionado && (
                <>
                    <button type="button" onClick={onEdit} disabled={disabled} className={botonClass} title={`Editar ${etiqueta}`}>
                        <span className="sr-only">{`Editar ${etiqueta}`}</span>
                        <IconoEditar />
                    </button>
                    <button
                        type="button"
                        onClick={onDelete}
                        disabled={disabled}
                        className={botonClass + ' text-red-600 hover:bg-red-50 hover:text-red-900'}
                        title={`Eliminar ${etiqueta}`}
                    >
                        <span className="sr-only">{`Eliminar ${etiqueta}`}</span>
                        <IconoEliminar />
                    </button>
                </>
            )}
        </div>
    );
}
