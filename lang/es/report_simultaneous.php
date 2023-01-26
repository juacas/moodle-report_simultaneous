<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Lang strings
 *
 * @package    report
 * @subpackage simultaneous
 * @copyright  2023 Juan Pablo de Castro  <juan.pablo.de.castro@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 $string['eventreportviewed'] = 'Informe de uso simultáneo visto';
 $string['nologreaderenabled'] = 'Lector de registro no habilitado';
 $string['simultaneous:view'] = 'Ver informe simultáneo del curso';
 $string['page-report-simultaneous-x'] = 'Cualquier informe simultáneo';
 $string['page-report-simultaneous-index'] = 'Informe de uso simultáneo';
 $string['pluginname'] = 'Actividades simultáneas';
 $string['refmodules'] = 'Actividades de referencia';
 $string['refmodules_help'] = 'Las actividades seleccionadas serán usadas como referencia para analizar si los usuarios tienen actividades simultáneas en OTRAS actividades. Si se seleccionan actividades de referencia se analizarán a los usuarios que han participado en estas actividades y se buscará en las OTRAS actividaes. Si no se selecciona ninguna actividad de referencia, se compararán todas las actividades del curso y todos los usuarios.';
 $string['reportfor'] = 'Informe de actividades simultáneas para {$a}';
 $string['reportfor_help'] = 'Este informe mostrará los usuarios que tienen actividades simultáneas con las actividades seleccionadas. Si sospechas que algunos usuarios están haciendo trampas, puedes comprobar los logs de las actividades seleccionadas para ver si están haciendo algo mal o solicitar al soporte un análisis más profundo.';
 $string['showokusers'] = 'Mostrar usuarios sin actividades simultáneas';
 $string['showokusers_help'] = 'Si se selecciona, se mostrarán todos los usuarios del curso incluyendo a los usuarios que no tienen ninguna actividad simultánea detectada.';
 $string['simultaneousreport'] = 'Actividades simultáneas';
 $string['status_column'] = 'Estado';
 $string['status_column_help'] = 'La combinación de las comprobaciones para este usuario. Si el usuario tiene actividades simultáneas, el estado será "AVISO". Si el usuario no tiene actividades simultáneas, el estado será "OK".';
 $string['incourse_column'] = 'En curso';
 $string['incourse_column_help'] = 'El número de vistas de actividades para este usuario en el curso. Este número es la suma de las vistas de las actividades distintas a las seleccionadas en el campo "Actividades de referencia".';
 $string['insite_column'] = 'En el sitio';
 $string['insite_column_help'] = 'El número de vistas de las actividades de este usuario en el sitio. Este número es la suma de las vistas de las actividades distintas a la seleccionada en el campo "Actividades de referencia".';
 $string['ips_column'] = 'IPs';
 $string['ips_column_help'] = 'La lista de IPs usadas por este usuario. Esta lista es el recuento de las IPs (si es mayor que una) utilizadas por el usuario en la ventana de tiempo de la consulta. Normalmente denota que el usuario utilizó más de un dispositivo para acceder al servidor, pero también puede significar que el usuario está utilizando un proxy dinámico o que su dispositivo cambió la dirección de Internet.';
 $string['mensajesenviados_columna'] = 'Mensajes enviados';
 $string['messagesent_column_help'] = 'El número de mensajes instantáneos enviados por este usuario. Esto incluye mensajes enviados a otros usuarios y mensajes enviados a grupos. Este número incluye los mensajes enviados al propio usuario.';
 $string['messageactions_column'] = 'Acciones de mensajes';
 $string['messageactions_column_help'] = 'El número de acciones de mensajes instantáneos realizadas por este usuario. Esto incluye leer, borrar, etc. Este número incluye las acciones realizadas por el usuario en sus propios mensajes.';
 $string['privacy:metadata'] = 'El plugin Curso simultáneo no almacena ningún dato personal';
