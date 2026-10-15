<?php
/**
 * C.A.A.S. — Datos geográficos de Uruguay
 * Departamentos y principales localidades
 */

define('DEPARTAMENTOS_UY', [
    'Artigas'       => ['Artigas', 'Bella Unión', 'Tomás Gomensoro', 'Baltasar Brum'],
    'Canelones'     => ['Las Piedras', 'Canelones', 'La Paz', 'Pando', 'Santa Lucía', 'Atlántida', 'La Floresta', 'Parque del Plata', 'Salinas', 'Sauce', 'Toledo', 'Ciudad de la Costa'],
    'Cerro Largo'   => ['Melo', 'Río Branco', 'Fraile Muerto', 'Aceguá'],
    'Colonia'       => ['Colonia del Sacramento', 'Carmelo', 'Juan Lacaze', 'Nueva Helvecia', 'Nueva Palmira', 'Rosario', 'Tarariras'],
    'Durazno'       => ['Durazno', 'Sarandí del Yi', 'Villa del Carmen', 'Carlos Reyles'],
    'Flores'        => ['Trinidad', 'Ismael Cortinas'],
    'Florida'       => ['Florida', 'Sarandí Grande', 'Casupá', '25 de Mayo'],
    'Lavalleja'     => ['Minas', 'José Batlle y Ordóñez', 'Solís de Mataojo'],
    'Maldonado'     => ['Maldonado', 'Punta del Este', 'San Carlos', 'Pan de Azúcar', 'Piriápolis', 'Aiguá'],
    'Montevideo'    => ['Montevideo', 'Ciudad Vieja', 'Centro', 'Pocitos', 'Punta Carretas', 'Carrasco', 'Buceo', 'Cordón', 'Malvín', 'Parque Batlle'],
    'Paysandú'      => ['Paysandú', 'Guichón', 'Quebracho', 'Tambores'],
    'Río Negro'     => ['Fray Bentos', 'Young', 'Nuevo Berlín', 'San Javier'],
    'Rivera'        => ['Rivera', 'Tranqueras', 'Vichadero', 'Minas de Corrales'],
    'Rocha'         => ['Rocha', 'La Paloma', 'Chuy', 'La Pedrera', 'Lascano', 'Castillos'],
    'Salto'         => ['Salto', 'Constitución', 'Belén', 'Colonia Lavalleja'],
    'San José'      => ['San José de Mayo', 'Libertad', 'Ciudad del Plata', 'Ecilda Paullier'],
    'Soriano'       => ['Mercedes', 'Dolores', 'Cardona', 'José Enrique Rodó'],
    'Tacuarembó'    => ['Tacuarembó', 'Paso de los Toros', 'San Gregorio de Polanco'],
    'Treinta y Tres'=> ['Treinta y Tres', 'Vergara', 'Santa Clara de Olimar'],
]);

/**
 * Helper: genera los <option> para el select de departamentos
 */
function optsDepartamentos(string $seleccionado = ''): string {
    $html = '<option value="">— Seleccioná —</option>';
    foreach (array_keys(DEPARTAMENTOS_UY) as $depto) {
        $sel   = $seleccionado === $depto ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($depto) . '"' . $sel . '>' . htmlspecialchars($depto) . '</option>';
    }
    return $html;
}

/**
 * Helper: genera los <option> para el select de localidades de un departamento
 */
function optsLocalidades(string $departamento, string $seleccionada = ''): string {
    $localidades = DEPARTAMENTOS_UY[$departamento] ?? [];
    $html = '<option value="">— Seleccioná —</option>';
    foreach ($localidades as $loc) {
        $sel   = $seleccionada === $loc ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars($loc) . '"' . $sel . '>' . htmlspecialchars($loc) . '</option>';
    }
    return $html;
}
