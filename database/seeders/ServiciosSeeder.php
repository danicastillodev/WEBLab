<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Metodo;
use App\Models\Servicio;
use Illuminate\Database\Seeder;

class ServiciosSeeder extends Seeder
{
    public function run(): void
    {
        // clave, siglas, descripcion, metodo, area, referencias
        $servicios = [
            ['BTA24', 'BAAG', 'BACTER. AGUAS', 'NUMERO MAS PROBABLE', 'Bacteriología', 'NUMERO MAS PROBABLE'],
            ['BTA25', 'ANTB', 'ANTIBIOGRAMA', 'KIRBY BAUER', 'Bacteriología', 'METODO DE KIRBY BAUER'],
            ['BTG23', 'BACTG', 'BACTER. GRAL.', 'BACTERIOLOGICO', 'Bacteriología', 'PROCEDIMIENTO ESTANDAR'],
            ['BTGM', 'BACTM', 'BACTER. GRAL MIEL', 'BACTERIOLOGICO', 'Bacteriología', 'PROCEDIMIENTO ESTANDAR'],
            ['BTM69', 'MDB', 'MICROSCOPIA DIRECTA BACTER.', 'MICROSCOPIA DIRECTA', 'Bacteriología', null],
            ['BTS', 'SALM', 'SALMONELLA AISLAMIENTO NOM-005-ZOO-1993', 'BACTERIOLOGICO', 'Bacteriología', null],
            ['BTS22', 'SALAB', 'SALMONELLA AVES', 'BACTERIOLOGICO', 'Bacteriología', null],
            ['BTS23', 'SALH', 'SALMONELLA HISOPOS', 'BACTERIOLOGICO', 'Bacteriología', null],
            ['BTS68', 'SALO', 'SALMONELLA ORGANOS', 'BACTERIOLOGICO', 'Bacteriología', null],
            ['CENT', 'CENT', 'CENTRIFUGADO', 'CENTRIFUGADO', 'Serología', null],
            ['CLC63', 'COPRO', 'COPROPARASITOSCOPICO', 'FLOTACION/SEDIMENTACION', 'Brucella', 'METODO CLINICO CONVENCIONAL'],
            ['CLH19', 'HEM', 'HEMOGRAMA', 'MICROSCOPIA DIRECTA', 'Brucella', 'TINCION DE WRIGHT GIEMSA'],
            ['CLH20', 'HEPW', 'HEMOPARASITOS', 'TINCION DE WRIGHT', 'Patología', null],
            ['EEE', 'EEE', 'EXTRACCION Y ENVIO DE ENCEFALO', 'NECROPSIA', 'Bacteriología', null],
            ['MTM01', 'TMS', 'TUBO CON MEDIO DE TRANSPORTE', 'N/A', 'PCR', null],
            ['MTPB', 'MT', 'MEDIO DE TRANSPORTE PBS', 'N/A', 'PCR', null],
            ['PAO33', 'MD', 'MICROSCOPIA DIRECTA', 'MICROSCOPIA DIRECTA', 'Brucella', 'MICROSCOPIA DIRECTA'],
            ['PAS18', 'VARRO', 'VARROASIS INSPECCION VISUAL', 'INSPECCION VISUAL', 'Brucella', null],
            ['PCR01', 'PRRPC', 'PRRS PCR', 'PCR', 'PCR', null],
            ['PCR02', 'EOAPC', 'OJO AZUL PCR', 'PCR', 'PCR', null],
            ['PCR03', 'INPPC', 'INFLUENZA PORCINA PCR', 'PCR', 'PCR', null],
            ['PCR04', 'PEDPC', 'DIARREA EPIDEMICA PORCINA PCR', 'PCR', 'PCR', null],
            ['PCR05', 'CIRPC', 'CIRCOVIRUS PORCINO PCR', 'PCR', 'PCR', null],
            ['PCR06', 'SALPC', 'SALMONELLA PORCINA PCR', 'PCR', 'PCR', null],
            ['PCR07', 'ERIPC', 'ERISIPELA PORCINA PCR', 'PCR', 'PCR', null],
            ['PCR08', 'APPCR', 'APP PCR', 'PCR', 'PCR', null],
            ['PCR09', 'HAEPC', 'HAEMOPHILUS PARASUIS PCR', 'PCR', 'PCR', null],
            ['PCR10', 'IAPCR', 'INFLUENZA AVIAR PCR', 'PCR', 'PCR', null],
            ['PCR60', 'PCRTB', 'PCR/M. BOVIS', 'PCR', 'PCR', null],
            ['PTC52', 'CIT', 'CITOLOGIA', 'TINCION DE WRIGHT', 'Patología', 'METODO CONVENCIONAL'],
            ['PTE46', 'EC1', 'ELIM. MENOR A 10 KG.', 'HIDROLISIS ALCALINA', 'Recepción', 'BIODIGESTOR'],
            ['PTE47', 'EC2', 'ELIM. 10 A 20 KG.', 'HIDROLISIS ALCALINA', 'Recepción', 'BIODIGESTOR'],
            ['PTE48', 'EC3', 'ELIM. 20 A 100 KG.', 'HIDROLISIS ALCALINA', 'Recepción', 'BIODIGESTOR'],
            ['PTE49', 'EC4', 'ELIM. MAS DE 100 KG', 'HIDROLISIS ALCALINA', 'Recepción', null],
            ['PTE70', 'RPBI', 'ELIMINACION DE RPBI KG CAMPAÑAS', 'RECOLECCION', 'Recepción', null],
            ['PTE71', 'CPC', 'CONTENEDOR PUNZO-CORTANTES', 'N/A', 'Recepción', null],
            ['PTH29', 'HIS-3', 'HISTO. 1 A 3 ORGANOS', 'TINCION H/E', 'Patología', 'METODO CLASICO'],
            ['PTH45', 'HIS-6', 'HISTO. 4 A 6 ORGANOS', 'TINCION H/E', 'Patología', 'METODO CLASICO'],
            ['PTH50', 'HIS-9', 'HISTO 7 A 9 ORGANOS', 'TINCION H/E', 'Patología', 'METODO CLASICO'],
            ['PTH51', 'HIS12', 'HISTO 10 A 12 ORGANOS', 'TINCION H/E', 'Patología', 'METODO CLASICO'],
            ['PTI30', 'FPCIF', 'FPC POR IF', 'INMUNOFLUORESCENCIA', 'Patología', 'CORTES CONGELADOS'],
            ['PTL49', 'LAM-3', 'LAMINILLAS MENOS DE 3 ORGANOS', 'TINCION H/E', 'Patología', 'METODO CLASICO'],
            ['PTL55', 'LAM+3', 'LAMINILLAS MAS DE 3 ORGANOS', 'TINCION H/E', 'Patología', 'METODO CLASICO'],
            ['PTLTE', 'TIN', 'TINCION ESPECIAL', 'TINCION ESPECIAL', 'Patología', null],
            ['PTN28', 'NECR1', 'NECR. AVES O MENORES', 'NECROPSIA-HISTOPATOLOGIA', 'Patología', 'DIAGNOSTICO ANATOMOPATOLOGICO'],
            ['PTN42', 'NECR2', 'NECR. DE 1 A 10 KG.', 'NECROPSIA-HISTOPATOLOGIA', 'Patología', 'DIAGNOSTICO ANATOMOPATOLOGICO'],
            ['PTN43', 'NECR3', 'NECR. DE 10 A 100 KG.', 'NECROPSIA-HISTOPATOLOGIA', 'Patología', 'DIAGNOSTICO ANATOMOPATOLOGICO'],
            ['PTN44', 'NECR4', 'NECR. DE MAS DE 100 KG.', 'NECROPSIA-HISTOPATOLOGIA', 'Patología', 'DIAGNOSTICO ANATOMOPATOLOGICO'],
            ['PTR56', 'RB', 'RABIA POR I.F. E HISTOPATOLOGIA', 'INMUNOFLUORESCENCIA', 'Patología', null],
            ['PTT31', 'TBH', 'HISTO. TB Acuerdo de operación Acreditado por ema', 'TINCIONES H/E Y Z/N', 'Patología', null],
            ['SEA09', 'APPE', 'APP/ELISA.', 'ELISA', 'Serología', null],
            ['SEB', 'BRFC', 'BRUCELLA FIJACION DE COMPLEMENTO NOM-041-ZOO-1995', 'FIJACION DE COMPLEMENTO', 'Brucella', null],
            ['SEB12', 'BRT', 'Brucella Tarjeta NOM-041-ZOO-1995 Acreditado ema', 'AGLUTINACION ROSA DE BENGALA', 'Brucella', null],
            ['SEB13', 'BRRV', 'Brucella Rivanol NOM-041-ZOO-1995 Acreditado ema', 'PRECIPITACION POR RIVANOL', 'Brucella', null],
            ['SEB58', 'IDBO', 'Brucella OVIS/IDG NOM-041-ZOO-1995 Acreditado ema', 'INMUNODIFUSION DOBLE', 'Brucella', null],
            ['SEB59', 'IDHN', 'BRUCELLA ABORTUS/IDR', 'INMUNODIFUSION RADIAL', 'Brucella', null],
            ['SEE01', 'EAE', 'AUJESZKY/ELISA NOM-007-ZOO-1994', 'ELISA', 'Serología', null],
            ['SEE02', 'PRRSE', 'PRRS X3/ELISA', 'ELISA', 'Serología', null],
            ['SEE03', 'FPCE', 'FPC/ELISA NOM-037-ZOO-1995', 'ELISA', 'Serología', null],
            ['SEE05', 'PVPE', 'PARVO/ELISA', 'ELISA', 'Serología', 'FABRICANTE DEL KIT'],
            ['SEE07', 'MYHE', 'MYCOPLASMA HYO./ELISA', 'ELISA', 'Serología', null],
            ['SEE08', 'ERIE', 'ERISIPELA/ELISA', 'ELISA', 'Serología', null],
            ['SEE10', 'DVBE', 'DVB/ELISA', 'ELISA', 'Serología', null],
            ['SEE11', 'IBRE', 'IBR/ELISA', 'ELISA', 'Serología', null],
            ['SEE15', 'MYGE', 'MYCO. GALLISEPTICUM', 'ELISA', 'Serología', 'FABRICANTE DEL KIT'],
            ['SEE16', 'MYSE', 'MYCO. SINOVIAE', 'ELISA', 'Serología', 'FABRICANTE DEL KIT'],
            ['SEE18', 'IBDE', 'GUMBORO/ELISA', 'ELISA', 'Serología', 'FABRICANTE DEL KIT'],
            ['SEE19', 'BRIE', 'IBD/ELISA', 'ELISA', 'Serología', 'FABRICANTE DEL KIT'],
            ['SEE20', 'NCE', 'NEWCASTLE/ELISA', 'ELISA', 'Serología', 'FABRICANTE DEL KIT'],
            ['SEE21', 'AIAE', 'ANEMIA INF. AVIAR/ELISA', 'ELISA', 'Serología', 'FABRICANTE DEL KIT'],
            ['SEE4', 'PRRSO', 'PRRS FLUIDOS ORALES/ELISA', 'ELISA', 'Serología', null],
            ['SEE50', 'GETE', 'GASTRO. TRANS./ELISA', 'ELISA', 'Serología', null],
            ['SEE51', 'IPH1N', 'INFL. H1N1/ELISA', 'ELISA', 'Serología', null],
            ['SEE52', 'IPH3N2', 'INFL. H3N2/ELISA', 'ELISA', 'Serología', null],
            ['SEE53', 'HPE', 'HAEMOPHILLUS P./ELISA', 'ELISA', 'Serología', null],
            ['SEE54', 'SAPE', 'SALMONELLA PORCINA/ELISA', 'ELISA', 'Serología', null],
            ['SEE55', 'EAEGB', 'AUJESZKY GB', 'ELISA', 'Serología', null],
            ['SEE56', 'LEUCB', 'LEUCOSIS BOVINA/ELISA', 'ELISA', 'Serología', null],
            ['SEE57', 'LENAZ', 'LENGUA AZUL/ELISA', 'ELISA', 'Serología', null],
            ['SEE58', 'PARTB', 'PARATUBERCULOSIS/ELISA', 'ELISA', 'Serología', null],
            ['SEE59', 'DVB', 'DIARREA VIRAL BOVINA', 'ELISA', 'Serología', null],
            ['SEE60', 'PCV2E', 'CIRCOVIRUS PORCINO PCV2/ELISA', 'ELISA', 'Serología', null],
            ['SEE63', 'ILEIT', 'ILEITIS/ELISA', 'ELISA', 'Serología', null],
            ['SEE64', 'IP', 'INFLUENZA PORCINA A', 'ELISA', 'Serología', null],
            ['SEI14', 'IAIH', 'INFL. AVIAR/HI ACUERDO DOF 21/06/2011', 'INHIBICION DE LA HEMOAGLUTINACION', 'Virología', null],
            ['SEI15', 'IAIH', 'H5N2 INFLUENZA AVIAR/HI ACUERDO DOF 21/06/2011', 'INHIBICION DE LA HEMOAGLUTINACION', 'Virología', null],
            ['SEI16', 'IAIH', 'H7N3 INFLUENZA AVIAR/HI ACUERDO DOF 21/06/2011', 'INHIBICION DE LA HEMOAGLUTINACION', 'Virología', null],
            ['SEM06', 'LEPTO', 'LEPTOSPIRA', 'MICROAGLUTINACION', 'Serología', null],
            ['SEP04', 'OAHI', 'OJO AZUL (EOA)/HI', 'INHIBICION DE LA HEMOAGLUTINACION', 'Serología', null],
            ['SEP61', 'FPCPX', 'FPC/IPX', 'INMUNOPEROXIDASA', 'Serología', null],
            ['TBA21', 'TBAB', 'BACTER. TB Acuerdo de operación Acreditado por ema', 'BACTERIOLOGICO', 'Tuberculosis', null],
            ['TBT57', 'TMBQ', 'Bioquimica M Bovis Acuerdo operacion Acreditado ema', 'BIOQUIMICO', 'Tuberculosis', null],
            ['TXC32', 'CLEN', 'CLENBUTEROL', 'ELISA', 'Toxicología', null],
            ['TXC62', 'CLAD', 'CLENBUTEROL ADICIONAL', 'ELISA', 'Toxicología', null],
        ];

        foreach ($servicios as [$clave, $siglas, $descripcion, $metodo, $area, $referencias]) {
            Servicio::firstOrCreate(
                ['clave' => $clave],
                [
                    'siglas' => $siglas,
                    'descripcion' => $descripcion,
                    'metodo_id' => $metodo ? Metodo::firstOrCreate(['nombre' => $metodo])->id : null,
                    'area_id' => Area::where('nombre', $area)->first()?->id,
                    'referencias' => $referencias,
                ]
            );
        }
    }
}
