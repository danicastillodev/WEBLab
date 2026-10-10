<?php

namespace Database\Seeders;

use App\Models\Producto;
use App\Models\Servicio;
use Illuminate\Database\Seeder;

class ProductosSeeder extends Seeder
{
    public function run(): void
    {
        // nombre, clave del servicio relacionado (null si no existe en el catálogo de servicios), precio unitario
        $productos = [
            ['ACTINOBACILLUS PP.', 'SEA09', 129.31],
            ['ANTIBIOGRAMA', 'BTA25', 172.41],
            ['APP PCR', 'PCR08', 646.55],
            ['BACTERIOLOGIA AGUA', 'BTA24', 344.83],
            ['BACTERIOLOGICO GRAL', 'BTG23', 344.83],
            ['BACTERIOLOGICO GRAL MIEL', 'BTGM', 1475.50],
            ['BRUCELA FIJACION DE COMPLEMENTO', 'SEB', 155.17],
            ['BRUCELA OVIS', 'SEB58', 129.31],
            ['BRUCELA RIVANOL', 'SEB13', 25.86],
            ['BRUCELA TARJETA', 'SEB12', 13.79],
            ['BRUCELA TARJETA (TUBO)', 'SEB12', 15.52],
            ['CENTRIFUGADO', 'CENT', 8.62],
            ['CIRCOVIRUS', 'SEE60', 104.31],
            ['CIRCOVIRUS PORCINO PCR', 'PCR05', 732.76],
            ['CITOLOGIA', 'PTC52', 215.52],
            ['CONTENEDOR PUNZO-CORTANTES', 'PTE71', 90.52],
            ['COPROPARASITOSCOPICO FLOT./SEDIM.', 'CLC63', 112.07],
            ['DIARREA EPIDEMICA PORCINA PCR', 'PCR04', 732.76],
            ['DIARREA VIRAL BOVINA', 'SEE10', 172.41],
            ['ELIMINACION DE CADAVER 10 A 20 KG.', 'PTE47', 336.21],
            ['ELIMINACION DE CADAVER 20 A 100 KG.', 'PTE48', 594.83],
            ['ELIMINACION DE CADAVER MAS DE 100 KG.', 'PTE49', 853.45],
            ['ELIMINACION DE CADAVER MENOR A 10 KG.', 'PTE46', 172.41],
            ['ELIMINACION DE RPBI (BIDONES)', 'PTE70', 517.24],
            ['ELIMINACION DE RPBI KG CAMPAÑAS', 'PTE70', 43.10],
            ['ERISIPELA PORCINA PCR', 'PCR07', 1120.69],
            ['ERYSIPELA', 'SEE08', 172.41],
            ['ERYSIPELA CAMPAÑA', 'SEE08', 517.24],
            ['EXTRACCION Y ENVIO DE ENCEFALO', 'EEE', 144.83],
            ['GASTROENTERITIS TRANS.', 'SEE50', 56.03],
            ['HAEMOPHILUS PARASUIS', 'SEE53', 163.79],
            ['HAEMOPHILUS PARASUIS PCR', 'PCR09', 646.55],
            ['HEMOGRAMA', 'CLH19', 129.31],
            ['HEMOPARASITOS', 'CLH20', 103.45],
            ['HISTOPATOLOGICO 1 A 3 ORGANOS', 'PTH29', 301.72],
            ['HISTOPATOLOGICO 7 A 9 ORGANOS', 'PTH50', 862.07],
            ['HISTOPATOLOGICO 10 A 12 ORGANOS', 'PTH51', 1206.90],
            ['HISTOPATOLOGICO 4 A 6 ORGANOS', 'PTH45', 603.45],
            ['ILEITIS/ELISA BOHERINGER', 'SEE63', 21.00],
            ['INFLUENZA AVIAR', 'SEI14', 34.48],
            ['INFLUENZA AVIAR AVES', null, 431.03],
            ['INFLUENZA AVIAR HISOPOS', null, 431.03],
            ['INFLUENZA AVIAR PCR', 'PCR10', 991.38],
            ['INFLUENZA PORCINA', 'SEE64', 86.21],
            ['INFLUENZA PORCINA PCR', 'PCR03', 560.34],
            ['LAMINILLAS EXTRAS', null, 86.21],
            ['LEPTOSPIRA', 'SEM06', 129.31],
            ['LEUCOSIS BOVINA', 'SEE56', 94.83],
            ['MEDIO DE TRANSPORTE', 'MTM01', 25.86],
            ['MEDIO DE TRANSPORTE PBS', 'MTPB', 50.00],
            ['MICROSCOPIA DIRECTA', 'PAO33', 86.21],
            ['MICROSCOPIA DIRECTA BACTER.', 'BTM69', 86.21],
            ['MYCOBACTERIUM AISLAMIENTO', 'TBA21', 508.62],
            ['MYCOBACTERIUM BOVIS TIPIFICACION', 'TBT57', 413.79],
            ['MYCOBACTERIUM HISTO/TB', 'PTT31', 318.97],
            ['MYCOPLASMA', 'SEE07', 68.97],
            ['NECROPSIA DE 1 A 10 KILOS', 'PTN42', 517.24],
            ['NECROPSIA DE 10 A 100 KILOS', 'PTN43', 732.76],
            ['NECROPSIA DE MAS DE 100 KILOS', 'PTN44', 1724.14],
            ['NEWCASTLE', 'SEE20', 60.00],
            ['NEWCASTLE AISL. AVES', null, 302.59],
            ['NEWCASTLE AISL. HISOPOS U ORGANOS', null, 211.21],
            ['OJO AZUL (EOA)/HI', 'SEP04', 172.41],
            ['OJO AZUL PCR', 'PCR02', 732.76],
            ['PARATUBERCULOSIS', 'SEE58', 137.93],
            ['PARVOVIRUS PORCINO', 'SEE05', 130.00],
            ['PRRS', 'SEE02', 172.41],
            ['PRRS FLUIDO ORAL', 'SEE4', 146.55],
            ['PRRS PCR', 'PCR01', 732.76],
            ['PRRS PROYECTO', 'SEE02', 137.94],
            ['RABIA', 'PTR56', 144.83],
            ['RINOTRAQUEITIS INFECCIOSA', 'SEE11', 172.41],
            ['SALMONELLA', 'SEE54', 71.55],
            ['SALMONELLA PORCINA PCR', 'PCR06', 732.76],
            ['TINCION ESPECIAL (LAMINILLAS)', 'PTLTE', 172.41],
            ['TUBO 15 ML CON MEDIO DE TRANSPORTE', 'MTM01', 50.00],
            ['TUBO 50 ML CON MEDIO DE TRANSPORTE', 'MTM01', 50.00],
            ['VARROSIS', 'PAS18', 103.45],
        ];

        foreach ($productos as [$nombre, $claveServicio, $precio]) {
            Producto::firstOrCreate(
                ['nombre' => $nombre],
                [
                    'servicio_id' => $claveServicio ? Servicio::where('clave', $claveServicio)->first()?->id : null,
                    'precio_unitario' => $precio,
                ]
            );
        }
    }
}
