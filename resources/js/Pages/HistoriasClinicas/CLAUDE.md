# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this directory.

## Create.jsx overview

The create form collects all fields needed to open a new `HistoriaClinica`. Field order (top to bottom):

1. Fecha de recepción
2. Propietario
3. Dirección
4. Especie
5. Raza
6. Edad (unidad + optional valor)
7. Cantidad

### Inline record creation

Two related records can be created without leaving the page via modals backed by dedicated endpoints:

| Modal | Endpoint | Route name |
|---|---|---|
| Nuevo propietario | `POST /historias-clinicas/propietario` | `historias-clinicas.store-propietario` |
| Nueva dirección | `POST /historias-clinicas/direccion` | `historias-clinicas.store-direccion` |

Both use `axios.post()` and update local React state on success so the new record appears immediately in the select without a page reload.

### Dirección depends on Propietario

- The dirección select is **disabled** until a propietario is selected.
- `direccionesPropietario` is derived by filtering all direcciones by `propietario_id`.
- Selecting a new propietario resets `direccion_id` to `''`.
- The "Nueva dirección" button is also disabled until a propietario is chosen; the modal payload includes `propietario_id` from the main form.

### Edad field logic

`edad_unidad` is one of `['Dias', 'Meses', 'Años', 'NR', 'NA']`. The numeric `edad_valor` input only renders when the unit is not `NR` or `NA`. On submit, the controller sets `edad_valor = null` for those two units.

### Estado default in dirección modal

The dirección modal pre-selects Jalisco as the default estado (looked up by name from the `estados` prop). Municipios are filtered client-side by the selected `estado_id`.
