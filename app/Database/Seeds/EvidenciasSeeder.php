<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class EvidenciasSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $evidencias = [
            [
                'Seccion'           => 'Bioplásticos',
                'Titular'           => 'Minciencias firma alianza para crear el Clúster Colombiano de Bioplásticos',
                'SubTitulo'         => 'Pionero en Latinoamérica con sector privado, público y académico',
                'Cuerpo'            => 'El Ministerio de Ciencia de Colombia firmó una alianza estratégica para desarrollar y lanzar el Clúster Colombiano de Bioplásticos, pionero en Latinoamérica. El objetivo es facilitar el intercambio de innovaciones y cooperación técnica entre el sector privado, público y académico para impulsar el mercado de bioplásticos alineados con la economía circular.',
                'Fuente'            => 'https://www.minciencias.gov.co/sala_de_prensa/minciencias-firma-alianza-para-desarrollar-y-lanzar-el-cluster-colombiano',
                'Imagen'            => null,
                'Fecha'             => '2022-01-15',
                'Estado'            => 1,
                'FechaCreacion'     => $now,
                'FechaActualizacion'=> $now,
            ],
            [
                'Seccion'           => 'Investigación',
                'Titular'           => 'Investigadores colombianos obtienen bioplásticos a partir de microorganismos en aguas residuales',
                'SubTitulo'         => 'Proyecto Minciencias con bacterias Pseudomonas para producir PHA',
                'Cuerpo'            => 'Investigadores colombianos avanzan en la producción de bioplásticos a partir de microorganismos presentes en aguas residuales. El proyecto financiado por Minciencias explora cómo bacterias como Pseudomonas pueden producir polihidroxialcanoatos (PHA), un bioplástico biodegradable en suelo y agua, reduciendo costos mediante cultivos microbianos mixtos.',
                'Fuente'            => 'https://www.aguasresiduales.info/revista/noticias/investigadores-de-colombia-avanzan-en-la-obtencion-kiruW',
                'Imagen'            => null,
                'Fecha'             => '2024-03-20',
                'Estado'            => 1,
                'FechaCreacion'     => $now,
                'FechaActualizacion'=> $now,
            ],
            [
                'Seccion'           => 'Reciclaje',
                'Titular'           => 'Industria plástica invierte más de US$150 millones en ampliar capacidad de reciclaje en Colombia',
                'SubTitulo'         => '90.000 toneladas adicionales anuales de procesamiento hasta 2025',
                'Cuerpo'            => 'La industria plástica colombiana invirtió más de US$150 millones entre 2021 y 2025 para ampliar la capacidad de reciclaje, añadiendo 90.000 toneladas anuales de procesamiento. El total nacional se eleva a cerca de 400.000 toneladas anuales. Esto incluye la planta piloto de reciclaje químico de Esenttia en Barrancabermeja y ampliación de capacidad de reciclaje mecánico por empresas como Enka.',
                'Fuente'            => 'https://forbes.co/2025/03/11/sostenibilidad/industria-plastica-ha-invertido-us-150-millones-en-reciclaje',
                'Imagen'            => null,
                'Fecha'             => '2024-06-05',
                'Estado'            => 1,
                'FechaCreacion'     => $now,
                'FechaActualizacion'=> $now,
            ],
            [
                'Seccion'           => 'Normativa',
                'Titular'           => 'Ley 2232: Colombia prohibe ocho productos plásticos de un solo uso desde julio 2024',
                'SubTitulo'         => 'El consumo formal de bolsas cayó 62% en ocho meses',
                'Cuerpo'            => 'La Ley 2232 de 2022 establece la reducción gradual de plásticos de un solo uso en Colombia. Desde julio de 2024 se prohibieron bolsas de punto de pago, pitillos, mezcladores, rollos de bolsas y otros. Los resultados son contundentes: el consumo formal de bolsas cayó 62% en ocho meses. Para 2030 se busca prohibir envases para líquidos, platos desechables y cubiertos plásticos.',
                'Fuente'            => 'https://www.minambiente.gov.co/documento-normativa/ley-2232-de-2022',
                'Imagen'            => null,
                'Fecha'             => '2024-07-07',
                'Estado'            => 1,
                'FechaCreacion'     => $now,
                'FechaActualizacion'=> $now,
            ],
            [
                'Seccion'           => 'Exportaciones',
                'Titular'           => 'Exportaciones de la industria plástica colombiana crecen 15,6% en 2024',
                'SubTitulo'         => 'Acoplásticos reporta US$1.120 millones y 250 mil empleos indirectos',
                'Cuerpo'            => 'Según Acoplásticos, las exportaciones de productos plásticos aumentaron 15,6% y las de materias primas 6,5% entre enero y septiembre de 2024, alcanzando US$1.120 millones. El sector genera 250 mil empleos indirectos con crecimiento del 3,9%. La industria crece en exportaciones y en inversiones en reciclaje, estableciendo retos ambiciosos para 2025.',
                'Fuente'            => 'https://www.ecosdelcombeima.com/economia/nota-160090-la-industria-plastica-crece-en-exportaciones-y-en-inversiones-en-reciclaje',
                'Imagen'            => null,
                'Fecha'             => '2024-10-15',
                'Estado'            => 1,
                'FechaCreacion'     => $now,
                'FechaActualizacion'=> $now,
            ],
            [
                'Seccion'           => 'Reciclaje químico',
                'Titular'           => 'Esenttia inaugura planta piloto de reciclaje químico en Barrancabermeja',
                'SubTitulo'         => 'Proyecto ganador del Premio Portafolio 2024 para plástico 100% circular',
                'Cuerpo'            => 'Esenttia, filial petroquímica del Grupo Ecopetrol, desarrolla el proyecto "Reciclaje químico avanzado: plástico 100% circular", ganador del Premio Portafolio 2024. Pyrcom convierte residuos plásticos en aceite pirolítico; la Refinería de Barrancabermeja produce propileno verde; Esenttia lo transforma en polipropileno circular. Reduce emisiones de CO2 en más del 40%. Alcance nacional e internacional hasta 2027.',
                'Fuente'            => 'https://www.portafolio.co/innovacion/reciclaje-quimico-solucion-sostenible-a-cargo-de-esenttia-617831',
                'Imagen'            => null,
                'Fecha'             => '2024-05-22',
                'Estado'            => 1,
                'FechaCreacion'     => $now,
                'FechaActualizacion'=> $now,
            ],
            [
                'Seccion'           => 'Reciclaje PET',
                'Titular'           => 'Enka recicla 6 millones de botellas PET diarias en Colombia',
                'SubTitulo'         => 'Líder en reciclaje botella a botella con marca EKO®',
                'Cuerpo'            => 'Enka, empresa colombiana con 60 años de trayectoria, opera una de las plantas de reciclaje botella a botella más grandes del mundo, transformando 6 millones de botellas PET diariamente en 4 plantas. Ofrece EKO®PET (resinas aprobadas por Invima y FDA) y EKO®Poliolefinas. Beneficios: ahorro de energía 92%, reducción de CO2 72%. Eko Red opera la red de recolección más grande del país en 32 departamentos, generando beneficios para más de 100.000 recicladores.',
                'Fuente'            => 'https://www.enka.com.co/noticias/enka-lanza-su-nueva-planta-de-reciclaje-de-pet-botella-a-botella-posicionandose-como-una-de-las-cinco-mas-grandes-del-mundo',
                'Imagen'            => null,
                'Fecha'             => '2024-08-10',
                'Estado'            => 1,
                'FechaCreacion'     => $now,
                'FechaActualizacion'=> $now,
            ],
            [
                'Seccion'           => 'Eventos',
                'Titular'           => 'Enka presenta soluciones sostenibles en ColombiaPlast 2024',
                'SubTitulo'         => 'Revolucionando envases y empaques con materiales reciclados',
                'Cuerpo'            => 'Enka participó en ColombiaPlast 2024 presentando soluciones sostenibles que transforman la industria de envases y empaques. Mostró líneas EKO®PET y EKO®Poliolefinas para aplicaciones de contacto con alimentos y empaques flexibles. La feria destacó la apuesta de la industria colombiana por la economía circular y el reciclaje mecánico de alto valor.',
                'Fuente'            => 'https://www.enka.com.co/noticias/revolucionando-el-futuro-de-los-envases-y-empaques-enka-presenta-en-colombiaplast-2024-soluciones-sostenibles-que-transforman-la-industria',
                'Imagen'            => null,
                'Fecha'             => '2024-09-18',
                'Estado'            => 1,
                'FechaCreacion'     => $now,
                'FechaActualizacion'=> $now,
            ],
            [
                'Seccion'           => 'Economía circular',
                'Titular'           => 'Economía circular del plástico en Colombia: avances, brechas y caminos posibles',
                'SubTitulo'         => 'Cempre analiza el panorama del reciclaje nacional',
                'Cuerpo'            => 'El Compromiso Empresarial para el Reciclaje (Cempre) publicó un análisis sobre la economía circular del plástico en Colombia, documentando avances en recolección, procesamiento e incorporación de material reciclado. Identifica brechas como la informalidad de 80.000 recicladores y plantea caminos para cerrar el ciclo con políticas, tecnología e inclusión del sector reciclador.',
                'Fuente'            => 'https://cempre.org.co/economia-circular-del-plastico-en-colombia-avances-brechas-y-caminos-posibles',
                'Imagen'            => null,
                'Fecha'             => '2024-04-12',
                'Estado'            => 1,
                'FechaCreacion'     => $now,
                'FechaActualizacion'=> $now,
            ],
            [
                'Seccion'           => 'Metas de reciclaje',
                'Titular'           => 'Ley 2232: Botellas PET deberán contener 50% material reciclado en 2025',
                'SubTitulo'         => 'Meta de 90% para 2030 y recolección del 50% de envases',
                'Cuerpo'            => 'La Ley 2232 de 2022 establece metas ambiciosas: las botellas PET de agua deben contener 50% de material reciclado para 2025, aumentando a 90% para 2030. Además, se requiere recolección del 50% de envases para líquidos para 2030. Las sanciones por incumplimiento incluyen multas de 100 a 50.000 salarios mínimos, decomiso de productos y clausura de establecimientos.',
                'Fuente'            => 'https://www.minambiente.gov.co/documento-normativa/ley-2232-de-2022',
                'Imagen'            => null,
                'Fecha'             => '2024-01-08',
                'Estado'            => 1,
                'FechaCreacion'     => $now,
                'FechaActualizacion'=> $now,
            ],
        ];

        $this->db->table('evidencias')->insertBatch($evidencias);
    }
}
